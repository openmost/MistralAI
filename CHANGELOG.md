## Changelog

### 5.10.0

**Refreshed model list and dark theme support**

#### New Features
- **Refreshed preset model list**: Added the latest generations — Mistral Large 3, Mistral Medium 3.5, Mistral Small 4 — plus the new Magistral reasoning model (`magistral-medium-latest`). Default model is still `mistral-medium-latest`, which now points to Mistral Medium 3.5.
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
