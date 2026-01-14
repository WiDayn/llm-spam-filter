<?php
if (!defined('ABSPATH')) exit;

// 建表函数
function lsf_create_log_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lsf_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        comment_id bigint(20) NOT NULL,
        post_title text NOT NULL,
        author_name tinytext NOT NULL,
        comment_excerpt text NOT NULL,
        llm_response text,
        verdict varchar(20),
        status varchar(20) DEFAULT 'pending',
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// 拦截新评论，推入队列
function lsf_schedule_spam_check($comment_id, $comment_object) {
    if (!empty($comment_object->user_id)) {
        $user = get_userdata($comment_object->user_id);
        if (in_array('administrator', (array) $user->roles)) return;
    }
    if ($comment_object->comment_approved === 'spam' || $comment_object->comment_approved === 'trash') return;

    wp_schedule_single_event(time(), 'lsf_process_comment_job', array($comment_id));

    // Insert log entry - custom table requires direct query
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'lsf_logs', [ // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        'comment_id' => $comment_id,
        'post_title' => get_the_title($comment_object->comment_post_ID),
        'author_name' => $comment_object->comment_author,
        'comment_excerpt' => substr($comment_object->comment_content, 0, 100),
        'llm_response' => 'Waiting for WP-Cron...',
        'verdict' => 'PENDING',
        'status' => 'queued',
        'time' => current_time('mysql')
    ]);

    if ($comment_object->comment_approved == 1) {
        wp_set_comment_status($comment_id, 'hold');
    }
}
add_action('wp_insert_comment', 'lsf_schedule_spam_check', 10, 2);

// 拦截邮件
function lsf_suppress_moderation_email($notify_user, $comment_id) {
    if (wp_next_scheduled('lsf_process_comment_job', array($comment_id))) {
        return false;
    }
    return $notify_user;
}
add_filter('notify_moderator', 'lsf_suppress_moderation_email', 10, 2);

// 核心 Worker：调用 API
function lsf_process_comment_worker($comment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lsf_logs';
    
    $log_update = function($response_text, $verdict, $status) use ($wpdb, $table_name, $comment_id) {
        // Update log entry - custom table requires direct query
        $wpdb->update($table_name, ['llm_response' => $response_text, 'verdict' => $verdict, 'status' => $status], ['comment_id' => $comment_id]); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
    };

    $comment = get_comment($comment_id);
    if (!$comment) { $log_update("Comment not found", "ERROR", "failed"); return; }

    $api_key = get_option('lsf_api_key');
    if (empty($api_key)) { $log_update("API Key missing", "ERROR", "failed"); return; }

    $provider = get_option('lsf_provider', 'openai');
    $api_base = rtrim(get_option('lsf_api_base', 'https://api.openai.com/v1'), '/');
    $model = get_option('lsf_model', 'gpt-4o-mini');
    $final_system_prompt = (get_option('lsf_prompt') ?: LSF_DEFAULT_USER_PROMPT) . LSF_MANDATORY_SUFFIX;
    
    $user_content_data = [
        "context" => ["post_title" => get_the_title($comment->comment_post_ID) ?: "Unknown"],
        "comment" => [
            "author" => $comment->comment_author,
            "email" => $comment->comment_author_email,
            "content" => $comment->comment_content,
            "ip" => $comment->comment_author_IP
        ]
    ];
    $user_content_json = json_encode($user_content_data);

    // 构建 HTTP 请求
    $args = [
        'timeout' => 45,
        'blocking' => true,
        'headers' => []
    ];

    if ($provider === 'claude') {
        $url = $api_base . '/messages';
        $args['headers'] = [
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json'
        ];
        $args['body'] = json_encode([
            'model' => $model,
            'max_tokens' => 1024,
            'system' => $final_system_prompt,
            'messages' => [['role' => 'user', 'content' => $user_content_json]],
            'temperature' => 0.1
        ]);
    } else {
        $url = $api_base . '/chat/completions';
        $args['headers'] = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ];
        $args['body'] = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $final_system_prompt],
                ['role' => 'user', 'content' => $user_content_json]
            ],
            'temperature' => 0.1
        ]);
    }

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        $log_update("Request Error: " . $response->get_error_message(), "ERROR", "failed");
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($data['error'])) {
        $msg = is_array($data['error']) ? ($data['error']['message'] ?? json_encode($data['error'])) : $data['error'];
        $log_update("API Error: " . $msg, "ERROR", "failed");
        return;
    }

    $reply = '';
    if ($provider === 'claude') {
        if (isset($data['content'][0]['text'])) $reply = $data['content'][0]['text'];
    } else {
        if (isset($data['choices'][0]['message']['content'])) $reply = $data['choices'][0]['message']['content'];
    }

    if (empty($reply)) {
        $log_update("Empty Response Body", "ERROR", "failed");
        return;
    }

    $is_spam = false;
    if (preg_match('/\{.*\}/s', $reply, $matches)) {
        $json_result = json_decode($matches[0], true);
    } else {
        $json_result = json_decode($reply, true);
    }
    
    if (json_last_error() === JSON_ERROR_NONE && isset($json_result['spam'])) {
        $is_spam = (bool) $json_result['spam'];
    } else {
        if (stripos($reply, 'true') !== false) $is_spam = true;
    }

    if ($is_spam) {
        wp_set_comment_status($comment_id, 'spam');
        clean_comment_cache($comment_id);
        update_comment_meta($comment_id, '_lsf_reason', 'Flagged by LLM');
        $log_update($reply, "SPAM", "moved to spam");
    } else {
        $action = get_option('lsf_legit_action', 'approve');
        if ($action === 'approve') {
            wp_set_comment_status($comment_id, 'approve');
            clean_comment_cache($comment_id);
            $log_update($reply, "LEGIT", "auto approved");
        } else {
            wp_set_comment_status($comment_id, 'hold');
            clean_comment_cache($comment_id);
            $notify = get_option('lsf_notify_on_hold', 'no');
            if ($notify === 'yes') {
                remove_filter('notify_moderator', 'lsf_suppress_moderation_email', 10);
                wp_notify_moderator($comment_id);
                add_filter('notify_moderator', 'lsf_suppress_moderation_email', 10, 2);
                $log_update($reply, "LEGIT", "held & email sent");
            } else {
                $log_update($reply, "LEGIT", "held (silent)");
            }
        }
    }
}
add_action('lsf_process_comment_job', 'lsf_process_comment_worker');