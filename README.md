# Matomo MistralAI Plugin

Integrate AI-powered analytics insights and chat functionality into your Matomo instance using Mistral AI or any OpenAI-compatible API.

## Description

### AI-Powered Report Insights
Get instant AI-generated insights for any Matomo report. The plugin adds an "Insights" button to all report widgets that analyzes your data and provides actionable recommendations.

- Works with all report types (visitors, actions, referrers, goals, custom dimensions, custom reports, etc.)
- Supports data tables, evolution graphs, and series visualizations
- Conversation mode: ask follow-up questions about your report data

### Dedicated AI Chat
A full-featured chat interface for asking questions about your analytics data.

- Accessible from the main menu under "MistralAI"
- Real-time streaming responses (with automatic fallback for unsupported servers)
- Errors returned by the model API are displayed as a notice directly in the chat, so misconfiguration is easy to spot

### Flexible Model Configuration
Choose from preset models or specify custom model names.

**Preset Models** (curated for chat — code-completion, audio and vision-only specialists are excluded):
- Mistral Large 3
- Mistral Medium 3.5 *(default)*
- Mistral Small 4
- Magistral Medium 1.2 *(reasoning)*
- Ministral 8B
- Ministral 3B
- Open Mistral Nemo *(legacy multilingual)*

**Custom Models:**
Specify any model name to use models not in the preset list, perfect for:
- Newer Mistral releases not yet in the preset
- Code/audio/vision specialists (Codestral, Devstral, Voxtral, Pixtral, …)
- Self-hosted LLMs (Mistral, LLaMA, etc.)
- Other OpenAI-compatible providers

### Multi-Site Configuration
Configure different AI settings per website using Measurable Settings:
- Override system-wide host, API key, and model per site
- Customize prompts for specific websites
- Leave empty to use system defaults

### Custom Host Support
Connect to any OpenAI-compatible chat completions endpoint:
- Mistral AI (default)
- Self-hosted Mistral models (vLLM, Ollama, LocalAI, etc.)
- Other OpenAI-compatible providers

**Note:** API key is optional when using custom hosts, making it easy to connect to local LLM instances.

### Customizable Prompts
Tailor the AI's behavior with custom prompts:
- **Chat Base Prompt**: Customize how the AI responds in conversations
- **Insight Base Prompt**: Customize how the AI analyzes report data

### Safe and Theme-Aware
- AI answers are sanitized before being displayed in Matomo
- The chat and insight components follow Matomo's light and dark themes

### Multi-Language Support
Full translations available in:
- English
- German (Deutsch)
- Spanish (Español)
- French (Français)
- Italian (Italiano)
- Dutch (Nederlands)
- Swedish (Svenska)

## Installation

1. Download the plugin from the [Matomo Marketplace](https://plugins.matomo.org/MistralAI)
2. Extract to your `plugins/` directory
3. Activate the plugin in Matomo's Plugin settings
4. Configure your API settings in **Administration > General Settings > MistralAI**

## Configuration

### System Settings (Global)

| Setting | Description |
|---------|-------------|
| **Host** | API endpoint URL. Default: `https://api.mistral.ai/v1/chat/completions` |
| **API Key** | Your Mistral AI API key (required for Mistral AI, optional for custom hosts) |
| **Model (Preset)** | Select from available model presets |
| **Model (Custom)** | Override preset with a custom model name |
| **Chat Base Prompt** | System prompt for chat conversations |
| **Insight Base Prompt** | System prompt for report insights |

### Measurable Settings (Per-Site)

All system settings can be overridden per website. Leave fields empty to use system defaults.

## Usage

### Getting Report Insights

1. Navigate to any report in Matomo
2. Click the "Insights" button (Mistral icon) in the report header
3. View AI-generated insights in the side panel
4. Ask follow-up questions to dive deeper into the data

### Using the Chat

1. Go to **MistralAI** in the main menu
2. Type your question about analytics
3. Receive AI-powered responses with streaming support
4. Continue the conversation with follow-up questions

## Requirements

- Matomo 6.0.0 or higher
- PHP 8.1 or higher
- Valid API key (for Mistral AI) or accessible custom host

## API Methods

The plugin provides the following API methods:

| Method | Description |
|--------|-------------|
| `MistralAI.getResponse` | Get AI response for messages (non-streaming) |
| `MistralAI.getStreamingResponse` | Get AI response with SSE streaming |
| `MistralAI.getInsights` | Get AI insights for report data |

All API methods require appropriate view permissions for the requested site.

## Support

- **Issues**: [GitHub Issues](https://github.com/openmost/MistralAI/issues)
- **Documentation**: [Plugin Homepage](https://openmost.com/matomo/extensions/mistral-ai)
- **Email**: ronan@openmost.com

## License

GPL v3+

## Credits

Developed by [Openmost](https://openmost.com)
