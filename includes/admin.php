<?php
if (!defined('ABSPATH')) exit;

// Sanitization callbacks
function lsf_sanitize_language($value) {
    $allowed = array('en', 'zh');
    return in_array($value, $allowed, true) ? $value : 'en';
}

function lsf_sanitize_provider($value) {
    $allowed = array('openai', 'claude');
    return in_array($value, $allowed, true) ? $value : 'openai';
}

function lsf_sanitize_url($value) {
    return esc_url_raw($value);
}

function lsf_sanitize_api_key($value) {
    return sanitize_text_field($value);
}

function lsf_sanitize_model($value) {
    return sanitize_text_field($value);
}

function lsf_sanitize_prompt($value) {
    return wp_kses_post($value);
}

function lsf_sanitize_legit_action($value) {
    $allowed = array('approve', 'hold');
    return in_array($value, $allowed, true) ? $value : 'approve';
}

function lsf_sanitize_notify($value) {
    $allowed = array('yes', 'no');
    return in_array($value, $allowed, true) ? $value : 'no';
}

// 注册菜单
function lsf_add_admin_menu() {
    add_options_page('LLM SPAM Filter', 'LLM SPAM Filter', 'manage_options', 'llm-spam-filter', 'lsf_render_admin_page');
}
add_action('admin_menu', 'lsf_add_admin_menu');

// 注册设置项
function lsf_settings_init() {
    register_setting('lsfPlugin', 'lsf_language', array('sanitize_callback' => 'lsf_sanitize_language'));
    register_setting('lsfPlugin', 'lsf_provider', array('sanitize_callback' => 'lsf_sanitize_provider'));
    register_setting('lsfPlugin', 'lsf_api_base', array('sanitize_callback' => 'lsf_sanitize_url'));
    register_setting('lsfPlugin', 'lsf_api_key', array('sanitize_callback' => 'lsf_sanitize_api_key'));
    register_setting('lsfPlugin', 'lsf_model', array('sanitize_callback' => 'lsf_sanitize_model'));
    register_setting('lsfPlugin', 'lsf_prompt', array('sanitize_callback' => 'lsf_sanitize_prompt'));
    register_setting('lsfPlugin', 'lsf_legit_action', array('sanitize_callback' => 'lsf_sanitize_legit_action'));
    register_setting('lsfPlugin', 'lsf_notify_on_hold', array('sanitize_callback' => 'lsf_sanitize_notify'));
}
add_action('admin_init', 'lsf_settings_init');

// 渲染主框架
function lsf_render_admin_page() {
    // Sanitize and validate tab parameter - no nonce needed for read-only navigation
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'settings';
    $active_tab = in_array($tab, array('settings', 'logs'), true) ? $tab : 'settings';
    ?>
    <div class="wrap">
        <h2>LLM SPAM Filter</h2>
        <h2 class="nav-tab-wrapper">
            <a href="?page=llm-spam-filter&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html(lsf_text('tab_settings')); ?></a>
            <a href="?page=llm-spam-filter&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html(lsf_text('tab_logs')); ?></a>
        </h2>
        <?php if ($active_tab == 'settings') lsf_options_page_content(); else lsf_logs_page_content(); ?>
    </div>
    <?php
}

// 设置页内容
function lsf_options_page_content() {
    $current_prompt = get_option('lsf_prompt') ?: LSF_DEFAULT_USER_PROMPT;
    $lang = get_option('lsf_language', 'en');
    $provider = get_option('lsf_provider', 'openai');
    $action = get_option('lsf_legit_action', 'approve');
    $notify = get_option('lsf_notify_on_hold', 'no');

    $default_base_url = ($provider === 'claude') ? 'https://api.anthropic.com/v1' : 'https://api.openai.com/v1';
    $default_model = ($provider === 'claude') ? 'claude-3-5-sonnet-20240620' : 'gpt-4o-mini';
    $api_base = get_option('lsf_api_base', $default_base_url);
    $model_val = get_option('lsf_model', $default_model);

    ?>
    <form action="options.php" method="post">
        <?php settings_fields('lsfPlugin'); ?>
        <?php do_settings_sections('lsfPlugin'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('lang_label')); ?></th>
                <td>
                    <select name="lsf_language" onchange="this.form.submit()">
                        <option value="en" <?php selected($lang, 'en'); ?>>English</option>
                        <option value="zh" <?php selected($lang, 'zh'); ?>>简体中文</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('api_provider')); ?></th>
                <td>
                    <select name="lsf_provider" id="lsf_provider_select">
                        <option value="openai" <?php selected($provider, 'openai'); ?>><?php echo esc_html(lsf_text('provider_openai')); ?></option>
                        <option value="claude" <?php selected($provider, 'claude'); ?>><?php echo esc_html(lsf_text('provider_claude')); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('api_base')); ?></th>
                <td><input type="text" name="lsf_api_base" value="<?php echo esc_attr($api_base); ?>" class="regular-text"><p class="description"><?php echo esc_html(lsf_text('api_base_desc')); ?></p></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('api_key')); ?></th>
                <td><input type="password" name="lsf_api_key" value="<?php echo esc_attr(get_option('lsf_api_key')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('model_name')); ?></th>
                <td><input type="text" name="lsf_model" value="<?php echo esc_attr($model_val); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('action_label')); ?></th>
                <td>
                    <select name="lsf_legit_action" id="lsf_legit_action">
                        <option value="approve" <?php selected($action, 'approve'); ?>><?php echo esc_html(lsf_text('action_approve')); ?></option>
                        <option value="hold" <?php selected($action, 'hold'); ?>><?php echo esc_html(lsf_text('action_hold')); ?></option>
                    </select>
                    <p class="description"><?php echo esc_html(lsf_text('action_desc')); ?></p>
                </td>
            </tr>
            <tr id="notify_row" style="<?php echo ($action === 'approve') ? 'display:none;' : ''; ?>">
                <th scope="row"><?php echo esc_html(lsf_text('notify_label')); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="lsf_notify_on_hold" value="yes" <?php checked($notify, 'yes'); ?>>
                        <?php echo esc_html(lsf_text('notify_text')); ?>
                    </label>
                    <p class="description"><?php echo esc_html(lsf_text('notify_desc')); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html(lsf_text('prompt_label')); ?></th>
                <td>
                    <details>
                        <summary style="cursor: pointer; color: #2271b1;"><?php echo esc_html(lsf_text('prompt_desc')); ?></summary>
                        <br>
                        <textarea id="lsf_prompt_field" name="lsf_prompt" rows="8" class="large-text code"><?php echo esc_textarea($current_prompt); ?></textarea>
                        <br><br>
                        <button type="button" class="button" id="lsf_reset_btn"><?php echo esc_html(lsf_text('reset_btn')); ?></button>
                    </details>
                </td>
            </tr>
        </table>
        <?php submit_button(esc_html(lsf_text('save_btn'))); ?>
    </form>

    <script>
    document.getElementById('lsf_legit_action').addEventListener('change', function() {
        document.getElementById('notify_row').style.display = (this.value === 'hold') ? 'table-row' : 'none';
    });
    document.getElementById('lsf_reset_btn').addEventListener('click', function() {
        if(confirm('<?php echo esc_js(lsf_text('reset_confirm')); ?>')) {
            document.getElementById('lsf_prompt_field').value = <?php echo json_encode(LSF_DEFAULT_USER_PROMPT); ?>;
        }
    });
    </script>
    <?php
}

// 日志页内容 (包含统计仪表盘)
function lsf_logs_page_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'lsf_logs';
    $cache_key = 'lsf_stats_' . md5($table_name);
    $cache_group = 'lsf_stats';

    // 1. 统计 - with caching
    $stats = wp_cache_get($cache_key, $cache_group);
    if (false === $stats) {
        // Table name is safe (constructed from $wpdb->prefix + static string)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_checked = (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->prepare("SELECT COUNT(*) FROM `{$table_name}`")
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_spam = (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE verdict = %s", 'SPAM')
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total_legit = (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->prepare("SELECT COUNT(*) FROM `{$table_name}` WHERE verdict = %s", 'LEGIT')
        );
        $stats = compact('total_checked', 'total_spam', 'total_legit');
        wp_cache_set($cache_key, $stats, $cache_group, 5 * MINUTE_IN_SECONDS);
    } else {
        $total_checked = $stats['total_checked'];
        $total_spam = $stats['total_spam'];
        $total_legit = $stats['total_legit'];
    }
    $spam_rate = ($total_checked > 0) ? round(($total_spam / $total_checked) * 100, 1) : 0;

    // 2. 仪表盘
    ?>
    <div style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin: 20px 0; display: flex; justify-content: space-around; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <div style="text-align: center;">
            <h3 style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html(lsf_text('stat_total')); ?></h3>
            <span style="font-size: 32px; font-weight: bold; color: #2271b1; display: block; margin-top: 5px;"><?php echo esc_html(number_format($total_checked)); ?></span>
        </div>
        <div style="border-right: 1px solid #eee;"></div>
        <div style="text-align: center;">
            <h3 style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html(lsf_text('stat_spam')); ?></h3>
            <span style="font-size: 32px; font-weight: bold; color: #d63638; display: block; margin-top: 5px;"><?php echo esc_html(number_format($total_spam)); ?></span>
        </div>
        <div style="border-right: 1px solid #eee;"></div>
        <div style="text-align: center;">
            <h3 style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html(lsf_text('stat_legit')); ?></h3>
            <span style="font-size: 32px; font-weight: bold; color: #008a20; display: block; margin-top: 5px;"><?php echo esc_html(number_format($total_legit)); ?></span>
        </div>
        <div style="border-right: 1px solid #eee;"></div>
        <div style="text-align: center;">
            <h3 style="margin: 0; color: #646970; font-size: 13px; text-transform: uppercase;"><?php echo esc_html(lsf_text('stat_rate')); ?></h3>
            <span style="font-size: 32px; font-weight: bold; color: #1d2327; display: block; margin-top: 5px;"><?php echo esc_html($spam_rate); ?>%</span>
        </div>
    </div>
    <?php

    // 3. 列表
    $per_page = 20;
    // No nonce needed for read-only pagination
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($page - 1) * $per_page;

    // Cache key for logs
    $logs_cache_key = "lsf_logs_{$page}_{$per_page}";
    $logs = wp_cache_get($logs_cache_key, $cache_group);

    if (false === $logs) {
        // Table name is safe (constructed from $wpdb->prefix + static string)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->prepare("SELECT * FROM `{$table_name}` ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset)
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $total = (int) $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
            $wpdb->prepare("SELECT COUNT(*) FROM `{$table_name}`")
        );
        $logs = compact('results', 'total');
        wp_cache_set($logs_cache_key, $logs, $cache_group, 5 * MINUTE_IN_SECONDS);
    } else {
        $results = $logs['results'];
        $total = $logs['total'];
    }
    $total_pages = ceil($total / $per_page);

    echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
    echo '<th width="5%">' . esc_html(lsf_text('log_id')) . '</th>';
    echo '<th width="15%">' . esc_html(lsf_text('log_time')) . '</th>';
    echo '<th width="15%">' . esc_html(lsf_text('log_author')) . '</th>';
    echo '<th width="20%">' . esc_html(lsf_text('log_excerpt')) . '</th>';
    echo '<th width="30%">' . esc_html(lsf_text('log_response')) . '</th>';
    echo '<th width="10%">' . esc_html(lsf_text('log_result')) . '</th>';
    echo '</tr></thead><tbody>';

    if ($results) {
        foreach ($results as $row) {
            $color = ($row->verdict == 'SPAM') ? 'red' : (($row->verdict == 'LEGIT') ? 'green' : 'gray');
            echo "<tr>";
            echo "<td>" . esc_html($row->comment_id) . "</td>";
            echo "<td>" . esc_html($row->time) . "</td>";
            echo "<td><strong>" . esc_html($row->author_name) . "</strong><br><span style='font-size:10px; color:#666;'>" . esc_html($row->post_title) . "</span></td>";
            echo "<td>" . esc_html(mb_substr($row->comment_excerpt, 0, 50)) . "...</td>";
            echo "<td><code style='font-size:10px; display:block; max-height:100px; overflow-y:auto;'>" . esc_html($row->llm_response) . "</code></td>";
            echo "<td style='color:" . esc_attr($color) . "; font-weight:bold;'>" . esc_html($row->verdict) . "<br><span style='font-size:10px; color:#999'>" . esc_html($row->status) . "</span></td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="6">' . esc_html(lsf_text('log_empty')) . '</td></tr>';
    }
    echo '</tbody></table>';

    if ($total_pages > 1) {
        echo '<div class="tablenav bottom"><div class="tablenav-pages">';
        for ($i=1; $i<=$total_pages; $i++) {
            $url = add_query_arg(['paged' => $i]);
            $is_current = ($page == $i) ? 'current' : '';
            echo "<a class='page-numbers " . esc_attr($is_current) . "' href='" . esc_url($url) . "'>" . esc_html($i) . "</a> ";
        }
        echo '</div></div>';
    }
}
