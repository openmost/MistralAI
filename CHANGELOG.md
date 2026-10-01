## Changelog

### 5.11.0

> **No action required.** The settings of your websites, your custom prompts and the model chosen with a previous version are kept.

**Agent mode with the MCP Server plugin**

- When the MCP Server plugin is installed, activated and enabled, the chat and the insights query your real reports through the Matomo tools. Each tool call is shown in a step timeline under the answer.
- Write actions, such as creating a goal or an annotation, need write mode in MCP Server, and the assistant always waits for the explicit confirmation of the user before it creates, modifies or deletes anything. This rule cannot be removed by editing the prompts.
- The chat recommends the next step to unlock agent mode: install MCP Server, activate it, enable MCP or enable write mode. Super users get a direct link.
- New *Agent mode model* general setting, "Latest recommended" by default, as calling tools needs a larger model than chatting.

**Settings**

- One key is enough: the API key of the website, then the general API key. The plugin never uses the Matomo AI Providers plugin, which has no Mistral AI provider.
- The general settings moved from *Administration > General settings* to a dedicated *Administration > System > Mistral AI* page, with a *Connection* card and a *Prompts* card saved separately, and a *Delete key* button. New API method `MistralAI.setSystemSettings`.
- The website settings moved from the website edit form to *Administration > Websites > Mistral AI*. New API methods `MistralAI.getSiteSettings` and `MistralAI.setSiteSettings`.
- Saved API keys are never sent back to the browser, and the general key is only sent to the general host.

**Prompts**

- New default chat and insight prompts written for analytics, translated in every language of the plugin.
- Default prompts saved by previous versions are upgraded automatically, custom prompts are kept. A *Reset to default* button restores the defaults, and *Use the general prompts* makes a website follow the general prompts again.

**Models**

- New "Latest recommended" model, the default, currently Ministral 3 14B. Updated list: Mistral Medium 3.5, Mistral Large 3, Mistral Small 4, Ministral 3 14B, 8B and 3B. Magistral Medium and Open Mistral Nemo, deprecated by Mistral AI, were removed.
- When the model is deprecated, retired or not included in the Mistral AI plan, the chat explains it and links to the settings instead of showing the raw API error.

**Chat and insights**

- Redesigned chat: one accessible, keyboard friendly insight panel, readable tables, highlighted code, copy buttons, auto-scroll that stops when you scroll up, and a dedicated chat page with suggested questions.
- Insights analyse the full report: a compact payload with the totals, the active segment, the period and the comparisons. Evolution graphs, goals, custom reports and the other report widgets are supported, and errors are shown as clean messages.
- Security: insight requests are restricted to Matomo report and data methods.
- Security: AI answers are sanitized before being displayed, which fixes links that could run JavaScript when clicked.
- Errors returned by the model API, including while streaming, are shown as a notice in the chat instead of failing silently.
- Translated into 6 more languages: Arabic, Chinese (Simplified and Traditional), Japanese, Polish and Portuguese.
- The rate limit message and the description of the host setting are translated and name Mistral AI instead of GPT.
- Openmost messages can appear in Matomo, for example on the Events page, once whatever the number of Openmost plugins activated. Banners can be dismissed and link to the Openmost website in the language of the user.

**Compatibility**

- Requires Matomo 5.10.0 or higher, for the theme variables used by the chat. The agent mode needs MCP Server (Matomo 5.8 or higher, PHP 8.1 or higher).

### 5.10.1

- Security: restrict insight requests to Matomo reports.

### 5.10.0

**Refreshed model list and dark theme support**

#### New Features
- **Refreshed preset model list**: Added the latest generations, Mistral Large 3, Mistral Medium 3.5 and Mistral Small 4, plus the new Magistral reasoning model (`magistral-medium-latest`). Default model is still `mistral-medium-latest`, which now points to Mistral Medium 3.5.
- **Curated for chat**: The preset list now only contains models suited for back-and-forth discussion of report data. Code-completion specialists (Codestral, Devstral), audio models (Voxtral) and vision-only models (Pixtral) have been removed because they target one-shot tasks rather than conversation.
- **Dark theme support**: All chat and insight components use Matomo's native CSS theme variables (`--theme-color-background-contrast`, `--theme-color-border`, `--theme-color-background-tinyContrast`), so the UI automatically follows Matomo's light/dark theme without any extra configuration. Fallback values are preserved for Matomo < 5.10.

#### Notes for Custom Models
The "Model (Custom)" field still accepts any model name, including code/audio/vision specialists or self-hosted ones (Codestral, Devstral, Voxtral, Pixtral, etc.). Use it whenever you need a model that is no longer in the preset dropdown.

### 5.9.3

- Fix upgrade scenario issue (only when upgrade from v5.8.0)

### 5.9.0

**Major Update: Settings Refactoring, Streaming & UI Improvements**

#### New Features
- **Custom Model Support**: Added ability to specify custom model names to override presets
- **Optional API Key**: API key is now optional when using custom hosts (self-hosted LLMs)
- **Unified Streaming**: Both Chat and Insights now use the same streaming endpoint with automatic mode detection
- **Automatic Streaming Fallback**: Streaming auto-detects and falls back to non-streaming if unsupported
- **Escape Key Support**: Close Insights offcanvas panel by pressing Escape

#### Improvements
- Refactored model selection: split into "Model (Preset)" dropdown and "Model (Custom)" text field
- Centralized model definitions in main plugin file for consistency
- Improved settings architecture with shared `SettingsBase` trait for system and measurable settings
- Better chat UI layout with proper flexbox sizing
- Updated default model to mistral-medium-latest
- Added translations for all new settings in 7 languages (EN, DE, ES, FR, IT, NL, SV)
- Improved POST parameter parsing for messages and widgetParams
- Unified `getStreamingResponse` API handles both chat and insight modes based on widgetParams
- Cleaner API with removed unused methods

#### Bug Fixes
- Fixed user messages not being sent to AI in streaming mode
- Fixed Insights not using correct prompt and report data
- Fixed chat messages list height not filling container
- Fixed streaming fallback behavior
- Fixed TypeScript errors in Vue components

#### Breaking Changes
- Removed `model` setting, replaced with `modelPreset` and `modelCustom`
- Removed `enableStreaming` setting (streaming is now automatic)
- Removed `getAvailableModels` API method (now using static model list)
- Removed `clearModelsCache` API method
- Removed `getRateLimitStatus` API method
- Removed `getSettings` API method

### v5.8.0

Major update:

- Add streaming support with Server-Sent Events
- Add rate limiting (30 requests per hour per user/site)
- Add dynamic model fetching from API with caching
- Add translation support (en, fr, de, es, it, nl, sv)
- Improve error handling and validation
- Add widget params support for insights
- Improve security with input sanitization
- Add SSL verification for API calls

### v5.7.0

Update: Support more Mistral AI models.

- open-mistral-nemo
- open-mixtral-8x22b
- ministral-3b-latest
- ministral-8b-latest

### v5.6.4

Update: plugin category and _cover.png

### v5.6.2

Update: Handle errors to chat UI

### v5.6.1

Update: Refactor Chat.vue component with AJAX Helper

### v5.6.0

Update : Logger and MeasurableSettings

### v5.5.2

Fix Ai label in form placeholder

### v5.5.0

Update messages continuity and insight conversation

### v5.4.3

Update documentation URL

### v5.4.2

Add "Open" to Mistral 7B configuration

### v5.4.1

Change default model to mistral-medium-latest

### v5.4.0

Support multiples models

### v5.3.13

Handle API errors and display messages

### v5.3.12

Fix security issue in API methods

### v5.3.11

Fix security issue about privileges in API

### v5.3.10

Update insight trigger positioning

### v5.3.9

Add scoped style

### v5.3.8

Support base prompts

### v5.3.4

Add screenshots for documentation

### v5.3.3

Fix report ID in vuejs file

### v5.3.2

Enable insight for all reports

### v5.3.0

Support insights

### v5.2.1

Add style for supported markdown components
Remove "embedded" model in settings pages

### v5.2.0

Support Markdown syntax for response

### v5.1.4

Fix default messages

### v5.1.3

Auto scroll down when in chat responses

### v5.1.2

Support VueJS

### v5.1.0

Change language to VueJS

### v5.0.3

Add settings

### v5.0.2

Add documentation

### v5.0.1

Edit chat UI

### v5.0.0

Create Mistral AI support
