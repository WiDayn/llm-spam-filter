<?php
if (!defined('ABSPATH')) exit;

function lsf_get_translations() {
    return [
        'en' => [
            'tab_settings' => 'Settings',
            'tab_logs' => 'Audit Logs',
            'lang_label' => 'Interface Language',
            'api_provider' => 'API Provider',
            'api_base' => 'API Base URL',
            'api_base_desc' => 'e.g., https://api.openai.com/v1 or https://api.anthropic.com/v1',
            'api_key' => 'API Key',
            'model_name' => 'Model Name',
            'action_label' => 'Action for Legit Comments',
            'action_approve' => 'Approve Immediately',
            'action_hold' => 'Hold for Review',
            'action_desc' => 'What to do if AI thinks the comment is legitimate?',
            'notify_label' => 'Email Notification',
            'notify_text' => 'Send email to admin',
            'notify_desc' => 'Sends standard WP moderation email only if comments are held.',
            'prompt_label' => 'System Prompt',
            'prompt_desc' => 'Click to expand/collapse prompt settings',
            'reset_btn' => 'Reset to Default',
            'save_btn' => 'Save Changes',
            'log_id' => 'ID',
            'log_time' => 'Time',
            'log_author' => 'Author/Post',
            'log_excerpt' => 'Excerpt',
            'log_response' => 'LLM Response',
            'log_result' => 'Result',
            'log_empty' => 'No logs found.',
            'reset_confirm' => 'Reset Prompt to default?',
            'provider_openai' => 'OpenAI Compatible',
            'provider_claude' => 'Anthropic Claude',
            'stat_total' => 'Total Analyzed',
            'stat_spam' => 'Spam Blocked',
            'stat_rate' => 'Block Rate',
            'stat_legit' => 'Legitimate',
        ],
        'zh' => [
            'tab_settings' => '插件设置',
            'tab_logs' => '审核日志',
            'lang_label' => '界面语言',
            'api_provider' => 'API 提供商',
            'api_base' => 'API 接口地址 (Base URL)',
            'api_base_desc' => 'OpenAI 填 https://api.openai.com/v1，Claude 填 https://api.anthropic.com/v1',
            'api_key' => 'API 密钥 (Key)',
            'model_name' => '模型名称 (Model)',
            'action_label' => 'AI 审核通过后的操作',
            'action_approve' => '直接批准发布',
            'action_hold' => '保留在待审区',
            'action_desc' => '如果 AI 认为评论是正常的，应该怎么做？',
            'notify_label' => '待审邮件通知',
            'notify_text' => '发送邮件通知管理员',
            'notify_desc' => '仅当评论被保留在“待审区”时，补发一封标准 WP 审核通知邮件。',
            'prompt_label' => '系统提示词 (System Prompt)',
            'prompt_desc' => '点击展开/折叠 Prompt 设置',
            'reset_btn' => '重置为默认值',
            'save_btn' => '保存更改',
            'log_id' => 'ID',
            'log_time' => '时间',
            'log_author' => '作者/文章',
            'log_excerpt' => '评论摘要',
            'log_response' => 'LLM 原始响应',
            'log_result' => '处理结果',
            'log_empty' => '暂无日志。',
            'reset_confirm' => '确定要重置 Prompt 吗？',
            'provider_openai' => 'OpenAI 兼容模式 (DeepSeek, GPT, Kimi 等)',
            'provider_claude' => 'Anthropic Claude 模式',
            'stat_total' => 'AI 分析总量',
            'stat_spam' => '拦截垃圾评论',
            'stat_rate' => '垃圾拦截率',
            'stat_legit' => '正常通过',
        ]
    ];
}

function lsf_text($key) {
    $lang = get_option('lsf_language', 'en');
    $dict = lsf_get_translations();
    return isset($dict[$lang][$key]) ? $dict[$lang][$key] : (isset($dict['en'][$key]) ? $dict['en'][$key] : $key);
}