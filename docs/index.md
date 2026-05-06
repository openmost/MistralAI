## Documentation

Integrate AI-powered analytics insights and chat functionality into your Matomo instance using MistralAI or any MistralAI-compatible API.

## Features

### AI-Powered Report Insights

Get instant AI-generated insights for any Matomo report. The plugin adds an "Insights" button to all report widgets that analyzes your data and provides actionable recommendations.

- Works with all report types (visitors, actions, referrers, goals, custom dimensions, custom reports, etc.)
- Supports data tables, evolution graphs, and series visualizations
- Conversation mode: ask follow-up questions about your report data

### Dedicated AI Chat

A full-featured chat interface for asking questions about your analytics data.

- Accessible from the main menu under "MistralAI"
- Real-time streaming responses (with automatic fallback for unsupported servers)

### Flexible Model Configuration

Choose from preset models or specify custom model names:

**Preset Models** (curated for chat — code-completion, audio and vision-only specialists are excluded):
- Mistral Large 3
- Mistral Medium 3.5 *(default)*
- Mistral Small 4
- Magistral Medium 1.2 *(reasoning)*
- Ministral 8B
- Ministral 3B
- Open Mistral Nemo *(legacy multilingual)*

**Custom Models:**
Specify any model name to use models not in the preset list, perfect for newer Mistral releases, code/audio/vision specialists (Codestral, Devstral, Voxtral, Pixtral, …), self-hosted LLMs, or other Mistral-compatible providers.

### Custom Host Support

Connect to any MistralAI-compatible API endpoint:
- MistralAI (default)
- Azure MistralAI
- Self-hosted solutions (Ollama, LocalAI, vLLM, etc.)
- Other providers (Anthropic via proxy, Mistral, etc.)

## Installation

### From Marketplace

1. Go to the Administration panel as a super user
2. Navigate to the Marketplace section and select "Plugins"
3. Search for "**MistralAI**"
4. Install and activate the plugin

### Manual Installation

1. Download the plugin from [GitHub](https://github.com/openmost/MistralAI)
2. Extract to your `/plugins` folder
3. Activate the plugin in Matomo's Plugin settings

## Configuration

### System Settings (Global)

Navigate to **Administration > General Settings > MistralAI** to configure:

| Setting | Description |
|---------|-------------|
| **Host** | API endpoint URL. Default: `https://api.mistral.ai/v1/chat/completions` |
| **API Key** | Your MistralAI API key (required for MistralAI, optional for custom hosts) |
| **Model (Preset)** | Select from available model presets |
| **Model (Custom)** | Override preset with a custom model name |
| **Chat Base Prompt** | System prompt for chat conversations |
| **Insight Base Prompt** | System prompt for report insights |

### Measurable Settings (Per-Site)

All system settings can be overridden per website in the site's Measurable Settings. Leave fields empty to use system defaults.

This is useful for:
- Using different models for different sites
- Customizing prompts for specific website contexts
- Using separate API keys per site

## Usage

### Getting Report Insights

1. Navigate to any report in Matomo
2. Click the "Insights" button (sparkle icon) in the report header
3. View AI-generated insights in the side panel
4. Ask follow-up questions to dive deeper into the data

### Using the Chat

1. Go to **MistralAI** in the main menu
2. Type your question about analytics
3. Receive AI-powered responses with streaming support
4. Continue the conversation with follow-up questions

## API Reference

The plugin provides the following API methods:

| Method | Description |
|--------|-------------|
| `MistralAI.getResponse` | Get AI response for messages (non-streaming) |
| `MistralAI.getStreamingResponse` | Get AI response with SSE streaming |
| `MistralAI.getInsight` | Get AI insights for report data |
| `MistralAI.getModels` | Get list of available preset models |

### Parameters

**MistralAI.getResponse**
- `idSite` - Site ID
- `period` - Period (day, week, month, year)
- `date` - Date string
- `messages` - Conversation messages in MistralAI format

**MistralAI.getInsight**
- `idSite` - Site ID
- `period` - Period
- `date` - Date string
- `reportId` - Report identifier
- `messages` - Conversation messages

All API methods require appropriate view permissions for the requested site.

## Multi-Language Support

The plugin interface is available in:
- English
- German (Deutsch)
- Spanish (Español)
- French (Français)
- Italian (Italiano)
- Dutch (Nederlands)
- Swedish (Svenska)

## Requirements

- Matomo 5.0.0 or higher
- PHP 7.4 or higher
- Valid API key (for MistralAI) or accessible custom host

## Support

- **Issues**: [GitHub Issues](https://github.com/openmost/MistralAI/issues)
- **Documentation**: [Plugin Homepage](https://openmost.io/products/mistral-ai)
- **Email**: ronan@openmost.io
