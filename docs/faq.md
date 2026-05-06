## FAQ

__How do I install this plugin?__

This plugin is available in the official Matomo Marketplace:

1. Go to the Administration panel
2. Navigate to the Marketplace section and select "Plugins"
3. Search for "**MistralAI**"
4. Install and activate the plugin
5. Configure your API settings in **Administration > General Settings > MistralAI**

Alternatively, download the plugin from GitHub and extract it to your `/plugins` folder.

__What do I need to make it work?__

You need an MistralAI API key, which you can obtain at https://docs.mistral.ai/api. If you're using a custom host (like a self-hosted LLM), an API key may be optional.

__Can I use models other than MistralAI's?__

Yes! The plugin supports any MistralAI-compatible API endpoint. You can connect to:

- Azure MistralAI
- Self-hosted solutions (Ollama, LocalAI, vLLM)
- Other providers (Mistral, Anthropic via proxy, etc.)

Simply configure the custom host URL in the plugin settings.

__Which models are supported?__

The plugin includes presets curated for back-and-forth chat:

- Mistral Large 3 (`mistral-large-latest`)
- Mistral Medium 3.5 (`mistral-medium-latest`) — default
- Mistral Small 4 (`mistral-small-latest`)
- Magistral Medium 1.2 (`magistral-medium-latest`) — reasoning
- Ministral 8B (`ministral-8b-latest`)
- Ministral 3B (`ministral-3b-latest`)
- Open Mistral Nemo (`open-mistral-nemo`) — legacy multilingual

Code-completion specialists (Codestral, Devstral), audio (Voxtral) and vision-only models (Pixtral) were intentionally left out of the dropdown because they are tuned for one-shot tasks rather than conversation. You can still use them — or any other model — through the **Model (Custom)** field.

__Is the plugin available to all users in my Matomo instance?__

Yes, once activated, all users with view permissions can access the AI features for their permitted sites.

__Can I configure different settings per website?__

Yes! Use Measurable Settings to override the system-wide host, API key, model, and prompts for specific websites. Leave fields empty to use system defaults.

__How do I get insights for a report?__

1. Navigate to any report in Matomo
2. Click the "Insights" button (AI icon) in the report header
3. View AI-generated insights in the side panel
4. Ask follow-up questions to dive deeper into the data

__Does the plugin support streaming responses?__

Yes, real-time streaming responses are supported. The plugin automatically falls back to non-streaming mode if your server doesn't support Server-Sent Events (SSE).

__Can I customize the AI's behavior?__

Yes, you can customize:

- **Chat Base Prompt**: Controls how the AI responds in chat conversations
- **Insight Base Prompt**: Controls how the AI analyzes report data

These can be set globally or per website.

__What languages are supported?__

The plugin interface is translated into:

- English
- German (Deutsch)
- Spanish (Español)
- French (Français)
- Italian (Italiano)
- Dutch (Nederlands)
- Swedish (Svenska)

__What are the requirements?__

- Matomo 5.0.0 or higher
- PHP 7.4 or higher
- Valid API key (for MistralAI) or accessible custom host

__Is my data sent to MistralAI?__

When you use the Insights feature or Chat, the relevant report data and your messages are sent to the configured API endpoint (MistralAI by default). If you have data privacy concerns, consider using a self-hosted LLM solution.

__How can I contribute to this plugin?__

You can contribute by:

- Reporting issues on [GitHub](https://github.com/openmost/MistralAI/issues)
- Forking the project and submitting pull requests
- Contacting the developer at ronan@openmost.io

__How long will this plugin be maintained?__

The plugin is actively maintained. The developer uses Matomo on many projects and will continue to patch and improve the plugin.
