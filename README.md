# Mistral AI for Matomo

Chat with your Matomo data and get one-click AI insights on any report, powered by Mistral AI or any OpenAI-compatible endpoint, plus an optional agent that queries your live reports through the Matomo MCP tools.

## Features

### Insights on any report

- A square AI button, styled like the Matomo 5 selectors, in the header of each report opens an insight panel that analyses the report you are looking at, then lets you ask follow-up questions.
- The whole report is analysed as Matomo serves it, not only the visible rows: a compact payload with the rows, the totals, the metric names and units, and the exact API request behind the report.
- The analysis follows what you see: the active segment, the period and the compared periods or segments.
- Works with tables, evolution graphs, goals, custom reports and the other widgets backed by a Matomo report.
- When a widget cannot be analysed, the panel shows a clear message instead of a raw error.
- The panel is an accessible dialog: keyboard navigation, focus kept inside the panel, Escape to close.

### Chat

- A dedicated *Mistral AI* page in the main menu, with suggested questions to get started.
- Answers stream in real time, with an automatic fallback for servers that do not support streaming.
- The conversation follows the latest message while it is written, and stops scrolling as soon as you scroll up to read.
- Answers are rendered as Markdown, with readable tables, highlighted code and copy buttons for an answer or a code block.

### Agent mode, with MCP Server

When the MCP Server plugin, available on the Matomo Marketplace, is installed, activated and enabled, the chat and the insights switch to agent mode:

- The assistant queries your real reports through the Matomo tools to answer, for example to compare periods or fetch a subtable.
- Each tool call is listed in a step timeline under the answer.
- Tools run inside Matomo with the access of the current user: no public URL, OAuth client or extra token is needed.
- Write actions, such as creating a goal or an annotation, are only available when write mode is enabled in MCP Server. The assistant then always describes the change and waits for your explicit confirmation before it creates, modifies or deletes anything. This rule is added by the plugin and cannot be removed by editing the prompts.
- When agent mode is not available, the chat recommends the next step: install MCP Server, activate it, enable MCP or enable write mode. Super users get a direct link, other users are asked to contact their administrator.
- Agent mode has its own *Agent mode model* setting, because calling tools needs a larger model than chatting.

The agent mode is optional. Without MCP Server, the chat and the insights keep working with the plugin settings.

### Connection and models

- **One key is enough**: the key of the website is used first, then the general key. The plugin never uses the Matomo AI Providers plugin, which has no Mistral AI provider, so the credential of another provider is never sent to Mistral AI.
- Works with Mistral AI (default) and any OpenAI-compatible chat completions endpoint served over HTTPS, including self-hosted models. The API key is optional on a custom host.
- The general API key is only sent to the general host: a website using its own host never receives it.
- Presets curated for conversation: Latest recommended (default, currently Ministral 3 14B), Mistral Medium 3.5, Mistral Large 3, Mistral Small 4, Ministral 3 14B, 8B and 3B. A custom model field accepts any other model name.
- When the configured model is deprecated, retired or not included in your Mistral AI plan, the chat explains it and links to the settings instead of showing the raw API error.

### Prompts

- Default chat and insight prompts written for analytics: the chat answers as a senior analytics consultant, the insights follow a fixed structure (summary, key figures, notable patterns, recommendations).
- Default prompts of previous versions are upgraded automatically, custom prompts are kept.
- A prompt equal to the default is not stored, so future improvements of the defaults reach you. A *Reset to default* button restores the default prompts.

### More

- Interface and default prompts translated into 13 languages: English, Arabic, Chinese (Simplified and Traditional), Dutch, French, German, Italian, Japanese, Polish, Portuguese, Spanish and Swedish.
- Follows the Matomo light and dark themes.
- Rate limit of 30 AI requests per hour, per user and per website.
- HTTP API: `MistralAI.getResponse`, `MistralAI.getStreamingResponse`, `MistralAI.getInsights`, `MistralAI.getSiteSettings`, `MistralAI.setSiteSettings` and `MistralAI.setSystemSettings`.

## Requirements

- Matomo 5.0.0 or higher, below 6 (`>=5.0.0,<6.0.0-b1`).
- PHP: the version required by your Matomo 5 (PHP 8.1 or higher for the agent mode, required by MCP Server)
- A Mistral AI API key, or an OpenAI-compatible HTTPS endpoint
- Optional, for the agent mode: the **MCP Server** plugin from the Marketplace (Matomo 5.8 or higher, PHP 8.1 or higher). Write actions also require write mode to be enabled in the MCP Server settings.

## Installation / Configuration

1. Install and activate **Mistral AI** from **Administration > Platform > Marketplace**.
2. As a super user, open **Administration > System > Mistral AI**:
   - **Connection** card: host (an HTTPS URL), API key, chat model and agent mode model. Each card is saved on its own. The saved key is never displayed, and a **Delete key** button removes it. Create a key in the [Mistral AI console](https://console.mistral.ai/).
   - **Privacy** card: check **Allow sending Matomo data to the AI provider**, off by default. Until a super user checks it, nothing is sent to the provider. The card shows where the data goes, and masks e-mail and IP addresses, removes URL query strings and excludes visitor-level data, all three on by default.
   - **Prompts** card: chat and insight base prompts, with a **Reset to default** button.
3. Optionally, override the settings for a website in **Administration > Websites > Mistral AI** (website admin access): host, API key, model and prompts, with a **Delete key** button and a **Use the general prompts** button. Empty fields use the general settings.
4. For the agent mode, install, activate and enable **MCP Server**, and enable write mode in its settings if you want the assistant to perform actions. The chat guides you through each missing step.

Then open the **Mistral AI** page in the main menu, or click the AI button in the header of a report.

## Privacy and data

- Insights send the data of the report you are looking at (labels, metrics and totals), its period, segment and comparisons, and the conversation, to the configured endpoint: Mistral AI by default, or your custom host.
- The chat sends your messages and the prompts. In agent mode, the results of the Matomo tools called by the assistant are also sent to the endpoint.
- Nothing is sent to the AI provider until a super user checks **Allow sending Matomo data to the AI provider** in **Administration > System > Mistral AI**, **Privacy** card. It is off by default: until then, the chat and the insights tell users to ask a super user to allow it.
- Before sending, e-mail and IP addresses are replaced with `[email]` and `[ip]`, and URL query strings are removed, in the report data and in the results of the Matomo tools. Both are on by default and can be turned off in the same **Privacy** card.
- Visitor-level data (Visits Log, visitor profiles, real-time and User ID reports) is excluded by default: the insights and the agent cannot read it until a super user unchecks **Exclude visitor-level data**.
- Report labels can hold values set by visitors (page titles, URLs, referrers, campaign names, custom dimensions). Check the retention and privacy terms of the provider before connecting it, and prefer a self-hosted or EU-hosted endpoint when your data policy requires it.
- The chat shows every user a notice stating that their questions, and the Matomo data read to answer them, are sent to the AI provider configured by the Matomo administrator.
- When a Matomo tool fails in agent mode, the model only receives a generic error with a reference: the details stay in the Matomo logs.
- Insight requests are restricted to Matomo report and data methods, never to an arbitrary API method, and run with the access of the current user.
- API keys are stored in the Matomo settings and are never sent back to the browser. Conversations are not stored by the plugin.
- To keep all data on your infrastructure, point the host to a self-hosted model.

## Need help with Matomo?

Openmost is an official Matomo Implementation Partner. We connect Matomo to AI assistants, BI tools and the rest of your stack with [Matomo integrations](https://openmost.com/matomo/services/integration?utm_source=matomo_marketplace&utm_medium=referral&utm_campaign=services&utm_content=mistralai) built on official APIs, with documented and privacy-checked data flows.

## Support

- Homepage: https://openmost.com/matomo/extensions/mistral-ai
- Email: ronan@openmost.com
- Issues: https://github.com/openmost/MistralAI/issues

## Screenshots

See the screenshots on the Marketplace page of the plugin.

## License

GPL v3+, developed by [Openmost](https://openmost.com).
