## FAQ

__How do I install and configure the plugin?__

1. Install the plugin from the Matomo Marketplace and activate it.
2. Go to *Administration > System > Mistral AI* as a super user.
3. Paste your Mistral AI API key in the *Connection* card and save.

You can create an API key in the [Mistral AI console](https://console.mistral.ai/).

__Where are the settings?__

The general settings are in *Administration > System > Mistral AI* (super user). The settings of a website are in *Administration > Websites > Mistral AI* (website admin). They are no longer in *Administration > General settings* nor in the website edit form.

__Which API key is used?__

The key of the website first, then the general key. The general key is only sent to the general host: a website that uses another host must set its own key. The plugin never uses the Matomo AI Providers plugin, which has no Mistral AI provider, so the key of another provider is never sent to Mistral AI.

__How do I remove a saved API key?__

Click *Delete key* next to the API key field, on the general settings or on the website settings. A saved key is never displayed again.

__What is agent mode?__

With the MCP Server plugin (Matomo Marketplace) installed, activated and enabled, the assistant queries your real reports through the Matomo tools to answer, and lists each tool call in a step timeline. Without it, the chat answers from the prompt and, for insights, from the report data. The chat recommends the missing step to unlock agent mode.

__Can the assistant change things in Matomo?__

Only when write mode is enabled in the MCP Server settings. The assistant then always describes the change and waits for your explicit confirmation in the conversation before it creates, modifies or deletes anything. This rule is added by the plugin and cannot be removed by editing the prompts.

__Which model does agent mode use?__

The *Agent mode model* of the general settings. Calling tools needs a larger model than chatting: the default, "Latest recommended" (currently Ministral 3 14B), works with the free Mistral AI plan. "Same as the chat model" reuses the chat model, except the 3B models, which are replaced by the recommended one.

__Which models are supported?__

The presets are curated for conversation: Latest recommended (default, currently Ministral 3 14B), Mistral Medium 3.5, Mistral Large 3, Mistral Small 4, Ministral 3 14B, 8B and 3B. Any other model, including Codestral, Devstral, Pixtral or a self-hosted model, can be set in the *Model (Custom)* field.

__The chat says my model is not available or not included in my plan__

Mistral AI retires old models, and some plans do not include every model (Mistral AI then answers "Rate limit exceeded" with a limit of 0 requests per minute). Choose another model in the settings, for example "Latest recommended", which follows the model recommended by each plugin release.

__Can I use another endpoint than Mistral AI?__

Yes. Set the host to any OpenAI-compatible chat completions endpoint served over HTTPS, such as a self-hosted model served by vLLM, Ollama or LocalAI. The API key is optional for a custom host.

__What do the insights analyse?__

The full report as Matomo serves it, not only the visible rows: the rows, the totals, the metric names and units, the active segment, the period and the compared periods or segments. Evolution graphs, goals and custom reports are supported. When a widget cannot be analysed, the panel shows a clear message.

__I changed the default prompts in a previous version. Are they kept?__

Yes. Custom prompts are kept. Default prompts saved by previous versions are upgraded automatically to the new defaults. *Reset to default* restores the defaults at any time.

__Is my data sent to Mistral AI?__

Your messages, the prompts and, for insights, the data of the report you are looking at are sent to the configured endpoint. Raw visitor data is never sent, unless the report itself contains it, such as the Visits Log. In agent mode, the results of the Matomo tools called by the assistant are sent too. To keep all data on your infrastructure, use a self-hosted model.

__Who can use the plugin?__

Every user with view access to a website can use the chat and the insights for that website. Each user can send 30 requests per hour and per website.

__Does the plugin support the dark theme?__

Yes, the chat and the insights follow the light and dark themes of Matomo.

__Which languages are supported?__

Arabic, Chinese (Simplified and Traditional), Dutch, English, French, German, Italian, Japanese, Polish, Portuguese, Spanish and Swedish. The default prompts are translated too.

__What are the requirements?__

Matomo 5.10.0 or later, below 6.0.0. The MCP Server plugin is optional, for agent mode: MCP Server 5.x requires Matomo 5.8 and PHP 8.1 or later.

__How do I get support?__

Report issues on [GitHub](https://github.com/openmost/MistralAI/issues) or write to ronan@openmost.com.
