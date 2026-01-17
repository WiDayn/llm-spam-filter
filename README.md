# LLM SPAM Filter

![Banner](banner.png)

> Intelligent WordPress spam comment filtering powered by Large Language Models

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org)
[![License](https://img.shields.io/badge/license-GPL%20v2-blue.svg)](LICENSE)

[English](README.md) | [简体中文](README_zh.md)

## Overview

LLM SPAM Filter is a WordPress plugin that uses Large Language Model (LLM) APIs to automatically detect and filter spam comments. Unlike traditional rule-based or keyword filtering methods, this plugin leverages AI to understand comment semantics and accurately identify SEO spam, bot-generated nonsense, phishing links, and other malicious content.

## Features

- **Smart AI Detection** - Leverages LLMs to understand comment semantics and accurately identify spam
- **Asynchronous Processing** - Uses WP-Cron for non-blocking API calls, no impact on user experience
- **Multi-Platform Support** - Supports OpenAI-compatible APIs (DeepSeek, GPT, Kimi, etc.) and Anthropic Claude
- **Customizable Prompts** - Flexible system prompt configuration for different scenarios
- **Multilingual Interface** - Supports English and Simplified Chinese
- **Visual Dashboard** - Intuitive statistics and audit logs
- **Flexible Actions** - Auto-approve or hold legitimate comments with optional email notifications

## Installation

### Method 1: Manual Installation

1. Upload plugin files to `wp-content/plugins/llm-comment-filter/`
2. Activate "LLM SPAM Filter" in WordPress admin "Plugins" menu
3. Go to "Settings > LLM SPAM Filter" to configure

### Method 2: GitHub Clone

```bash
cd wp-content/plugins/
git clone https://github.com/yourusername/llm-comment-filter.git
```

Then activate the plugin in WordPress admin.

## Configuration

### 1. Get API Key

**OpenAI-Compatible Platforms** (e.g., DeepSeek):
- Visit [SiliconFlow](https://cloud.siliconflow.cn/) to register
- Get your API Key

**Anthropic Claude**:
- Visit [Anthropic Console](https://console.anthropic.com/)
- Create an API Key

### 2. Plugin Settings

Configure in "Settings > LLM SPAM Filter":

| Option | Description |
|--------|-------------|
| API Provider | Choose OpenAI-compatible or Claude |
| API Base URL | API endpoint URL |
| API Key | Enter your API key |
| Model Name | Model name (e.g., gpt-4o-mini) |
| Action for Legit | How to handle comments identified as legitimate |
| System Prompt | Custom spam detection prompt |

### Recommended Model Configurations

| Provider | API Base URL | Model Name |
|----------|-------------|------------|
| OpenAI | https://api.openai.com/v1 | gpt-4o-mini |
| DeepSeek | https://api.siliconflow.cn/v1 | deepseek-ai/DeepSeek-V3 |
| Claude | https://api.anthropic.com/v1 | claude-3-5-sonnet-20241022 |

## How It Works

```
User submits comment
    ↓
Plugin intercepts (except admins)
    ↓
Create log entry with "pending" status
    ↓
Schedule async task via WP-Cron
    ↓
Send comment to LLM API for analysis
    ↓
Based on AI result:
  - Spam → Mark as spam
  - Legitimate → Approve / Hold for review
    ↓
Update log entry
```

## Statistics

The plugin displays statistics on the "Audit Logs" page:

- **Total Analyzed** - Total comments checked by AI
- **Spam Blocked** - Comments identified as spam
- **Legitimate** - Comments identified as legitimate
- **Block Rate** - Percentage of spam comments

## FAQ

### Q: Why aren't comments processed immediately?

A: The plugin uses WP-Cron for asynchronous processing. If your site's cron is not running properly, consider using system crontab or an external cron service.

### Q: How to improve detection accuracy?

A: Customize the System Prompt in settings to adjust detection rules based on your site's characteristics.

### Q: Which LLM providers are supported?

A: Any provider with OpenAI-compatible API format, including OpenAI, DeepSeek, Qwen, Kimi, etc., plus Anthropic Claude.

### Q: Will API calls incur costs?

A: Depends on your API provider.

### Q: Do I need a large model for spam detection?

A: Not at all. Spam detection is a relatively simple classification task that doesn't require large models. Smaller models around 6B parameters (like Qwen2-6B, DeepSeek-V3, etc.) can handle this task excellently while providing:
- **Faster response times** - Typically 1-2 seconds vs 5+ seconds for larger models
- **Lower costs** - Significantly reduced API costs
- **Better performance** - Quick processing means less wait time for comment moderation

For spam detection, we recommend lightweight models over flagship ones. They're more than capable of understanding comment semantics and identifying spam patterns.

## Screenshots

### Settings Page

![Settings](images/settings.png)

### Audit Logs and Statistics Dashboard

![Audit Logs](images/logs.png)

## Changelog

### 1.0.0
- Initial release as LLM SPAM Filter
- Optimized ad display style
- Collapsible ad section by default

## Contributing

Issues and Pull Requests are welcome!

## License

GPL v2 or later

## Author

Lin Xin

## Acknowledgments

- [OpenAI](https://openai.com/) - GPT models
- [Anthropic](https://www.anthropic.com/) - Claude models
- [SiliconFlow](https://cloud.siliconflow.cn/) - API service support
