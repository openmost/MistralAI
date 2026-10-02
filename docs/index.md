## Documentation

Chat with your Matomo data and get one-click AI insights on any report, powered by Mistral AI or any OpenAI-compatible endpoint.

## Getting started

1. Install and activate the plugin.
2. Go to *Administration > System > Mistral AI* as a super user.
3. In the *Connection* card, paste your Mistral AI API key, created in the [Mistral AI console](https://console.mistral.ai/), and save.
4. Open a report and click the AI button in its header, or open the *Mistral AI* page from the main menu.

## Report insights

The square AI button in the header of each report, styled like the Matomo 5 selectors, opens an insight panel. The analysis uses the full report as Matomo serves it: a compact payload with the rows, the totals, the metric names and units, and the exact API request behind the report. The active segment, the period and the compared periods or segments are taken into account. Tables, evolution graphs, goals, custom reports and the other widgets backed by a Matomo report are supported. You can ask follow-up questions in the same panel.

The panel is an accessible dialog: keyboard navigation, focus kept inside the panel, Escape to close.

## Chat

The *Mistral AI* page of the main menu is a full chat with suggested questions. Answers stream in real time and are rendered as Markdown, with readable tables, highlighted code and copy buttons. The conversation follows the latest message while it is written, unless you scroll up.

## Agent mode

With the MCP Server plugin (Matomo Marketplace) installed, activated and enabled, the assistant queries your real reports through the Matomo tools to answer. Each tool call appears in a step timeline under the answer.

- Read tools are available as soon as MCP is enabled.
- Write actions, such as creating a goal or an annotation, need write mode in the MCP Server settings. The assistant then always describes the change and waits for your explicit confirmation before it creates, modifies or deletes anything.
- When a step is missing, the chat recommends it: install MCP Server, activate it, enable MCP or enable write mode. Super users get a direct link, other users are asked to contact their administrator.
- Agent mode uses the *Agent mode model* of the general settings. Its default, "Latest recommended", works with the free Mistral AI plan. "Same as the chat model" reuses the chat model, except the 3B models, which are replaced by the recommended one.

## Settings

### General settings

*Administration > System > Mistral AI* (super user):

| Card | Setting | Description |
|---|---|---|
| Connection | Host | Chat completions endpoint, an HTTPS URL. Default: `https://api.mistral.ai/v1/chat/completions` |
| Connection | API key | Required for Mistral AI, optional for a custom host. Never displayed once saved, removed with *Delete key* |
| Connection | Model (Preset) | Latest recommended (default), Mistral Medium 3.5, Mistral Large 3, Mistral Small 4, Ministral 3 14B, 8B or 3B |
| Connection | Model (Custom) | Any model name, overrides the preset |
| Connection | Agent mode model | Model used with the Matomo tools |
| Prompts | Chat base prompt | Instructions of the chat |
| Prompts | Insight base prompt | Instructions of the insights |

Each card is saved on its own. *Reset to default* fills in the default prompts in your language. A prompt equal to its default is not stored, so it follows the updates of the plugin. Default prompts saved by previous versions are upgraded automatically, custom prompts are kept.

### Website settings

*Administration > Websites > Mistral AI* (website admin) overrides the host, API key, model and prompts for one website. Leave a field empty to use the general value. *Use the general prompts* makes the website follow the general prompts again.

### API key cascade

The key of the website is used first, then the general key. The general key is only sent to the general host, so a website using another host must set its own key. The plugin never uses the Matomo AI Providers plugin, which has no Mistral AI provider.

## API

| Method | Access | Description |
|---|---|---|
| `MistralAI.getResponse` | view | Chat answer, not streamed |
| `MistralAI.getStreamingResponse` | view | Chat or insight answer, streamed as Server-Sent Events |
| `MistralAI.getInsights` | view | Insight answer for a report widget |
| `MistralAI.getSiteSettings` | website admin | Settings of a website, the API key replaced by a placeholder |
| `MistralAI.setSiteSettings` | website admin | Saves the settings of a website |
| `MistralAI.setSystemSettings` | super user | Saves the general settings |

Each user can send 30 requests per hour and per website.

## Requirements

- Matomo 5.0.0 or later, below 6.0.0.
- PHP: the minimum required by Matomo.
- A Mistral AI API key, or the URL of an OpenAI-compatible endpoint.
- Optional: the MCP Server plugin for agent mode. MCP Server 5.x requires Matomo 5.8 and PHP 8.1 or later.

## Support

- Homepage: https://openmost.com/matomo/extensions/mistral-ai
- Issues: https://github.com/openmost/MistralAI/issues
- Email: ronan@openmost.com
