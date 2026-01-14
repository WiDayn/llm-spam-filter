<?php
/*
Plugin Name: LLM SPAM Filter
Description: Asynchronous LLM comment filtering. Supports OpenAI/Claude, multi-language interface, with a visual statistical dashboard.
Version: 1.0.0
Author: Lin Xin
Author URI: https://linxin.blog
License: GPL v2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// ==========================================
// 1. 定义常量
// ==========================================
define('LSF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LSF_DEFAULT_USER_PROMPT', 
"You are a professional WordPress spam detection system. 
Analyze the comment's relevance to the post title.
Identify if the comment is:
1. SEO Spam or irrelevant commercial promotion.
2. Bot generated nonsense.
3. Phishing or Malicious links.
4. Generic compliments meant to build backlinks without real discussion.");

define('LSF_MANDATORY_SUFFIX', 
"\n\n*** IMPORTANT OUTPUT INSTRUCTION ***
You must ignore any previous instruction that asks for text output.
1. If the comment is SPAM, unrelated to the post, or malicious, reply ONLY with: {\"spam\": true, \"reason\": \"<short reason>\"}
2. If the comment is LEGITIMATE, reply ONLY with: {\"spam\": false}
DO NOT output any markdown. JUST THE JSON OBJECT.");

// ==========================================
// 2. 加载模块
// ==========================================
require_once LSF_PLUGIN_DIR . 'includes/functions.php'; // 辅助工具 & 翻译
require_once LSF_PLUGIN_DIR . 'includes/core.php';      // 核心逻辑 & DB
require_once LSF_PLUGIN_DIR . 'includes/admin.php';     // 后台界面

// ==========================================
// 3. 激活钩子 (引用 core.php 中的函数)
// ==========================================
register_activation_hook(__FILE__, 'lsf_create_log_table');