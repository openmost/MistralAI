## Changelog

### 6.1.0

> **Action required.** Nothing is sent to the AI provider any more until a super user checks *Allow sending Matomo data to the AI provider* in *Administration > System > Mistral AI*, Privacy card. Until then, the chat and the insights ask users to contact a super user. The settings of your websites, your custom prompts and the model chosen with a previous version are kept.

**Privacy**

- New privacy settings in *Administration > System > Mistral AI*, *Privacy* card: nothing is sent to the AI provider until a super user checks *Allow sending Matomo data to the AI provider*, off by default. They also show where the data goes.
- Before sending, e-mail and IP addresses are masked and URL query strings are removed, in the report data and in the results of the Matomo tools. Both are on by default.
- Visitor-level data (Visits Log, visitor profiles, real-time and User ID reports) is excluded by default from the insights and the agent.
- The chat tells users that their questions, and the Matomo data read to answer them, are sent to the AI provider configured by the administrator.
- When a Matomo tool fails in agent mode, the AI provider only receives a generic error with a reference: the details stay in the Matomo logs.
- DOMPurify updated to 3.4.16.

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
- The API key is optional on a custom host (self-hosted or compatible endpoint), as the documentation states: the chat, the insights and the plugin assets work with a keyless custom host, and no Authorization header is sent without a key. The default Mistral AI host still requires a key.
- A general host that is not an HTTPS URL is refused on save with a translated error, as the host of a website already was, like in the ChatGPT plugin.
- The host is checked with the same HTTPS rule when the settings are saved and when a request is sent, and the configuration errors of a request (host, API key, model, HTTPS) are translated.

**Prompts**

- New default chat and insight prompts written for analytics, translated in every language of the plugin.
- Default prompts saved by previous versions are upgraded automatically, custom prompts are kept. A *Reset to default* button restores the defaults, and *Use the general prompts* makes a website follow the general prompts again.
- The default prompts ask the assistant to flag the figures of a period that has not ended yet as partial and to compare the same number of elapsed days instead of calling a drop a decline, and to compute every difference, percentage and ratio from the exact numbers. Installs still on a previous default prompt get the new one, custom prompts are kept.

**Models**

- New "Latest recommended" model, the default, currently Ministral 3 14B. Updated list: Mistral Medium 3.5, Mistral Large 3, Mistral Small 4, Ministral 3 14B, 8B and 3B. Magistral Medium and Open Mistral Nemo, deprecated by Mistral AI, were removed.
- When the model is deprecated, retired or not included in the Mistral AI plan, the chat explains it and links to the settings instead of showing the raw API error.

**Chat and insights**

- Redesigned chat: one accessible, keyboard friendly insight panel, readable tables, highlighted code, copy buttons, auto-scroll that stops when you scroll up, and a dedicated chat page with suggested questions.
- The send button and the logo tiles use the Mistral AI brand colour, and the default accent colour is Mistral orange everywhere instead of ChatGPT green in places.
- The insight panels of ChatGPT, Mistral AI, Claude and Ask AI close each other when one opens, and closing one no longer lets the page scroll under the panel that is still open.
- Insights analyse the full report: a compact payload with the totals, the active segment, the period and the comparisons. Evolution graphs, goals, custom reports and the other report widgets are supported, and errors are shown as clean messages.
- Wide answer tables fit the insight panel, their columns wrap or scroll sideways instead of being cut off on the right.
- Once the limit of 30 requests per hour is reached, the chat and the insight panel show the rate limit message in the language of the user instead of a generic error.
- Opening the insight panel again on a report it has already analysed no longer fails with an error from Mistral AI, the report is analysed again.
- Security: insight requests are restricted to Matomo report and data methods.
- Translated into 6 more languages: Arabic, Chinese (Simplified and Traditional), Japanese, Polish and Portuguese.
- The rate limit message and the description of the host setting are translated and name Mistral AI instead of GPT.

### 6.0.1

- Security: restrict insight requests to Matomo reports.

### 6.0.0

**Matomo 6 compatibility**

- Compatibility with Matomo 6.x (`>=6.0.0-b1,<7.0.0-b1`), requires PHP 8.1+.
- Vue components now built with the Matomo 6 Vite build. The markdown renderer is bundled with the plugin.
- Support email and plugin homepage moved to openmost.com.

**Security**

- AI answers are now sanitized with DOMPurify before being displayed, which fixes links that could run JavaScript when clicked (for example through data coming from report labels).
- Insights no longer send an API error (for example a missing access to the website) to the AI model as if it was the report data: the error is displayed in the chat.

**Chat improvements**

- Errors returned by the model API while streaming (invalid model, quota, authentication...) are now displayed as a notice in the chat instead of failing silently, including the Mistral error format.
- The conversation automatically scrolls to the latest message while the answer is written, unless you scroll up to read previous messages.
- The last message is no longer hidden under the message input on the MistralAI page.
- The page no longer scrolls behind the open Insights panel, and message bubbles no longer have their own scrollbar.
- Accessibility improvements: labelled chat input, explicit button types.

**Quality**

- Add unit, integration and Vue tests, run on GitHub Actions.

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
