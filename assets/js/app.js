window.addEventListener('widget:loaded', function (e) {
  var parameters = e.detail[0].parameters;
  var element = e.detail[0].element[0];
  var titleWrapper = element.querySelector('.enrichedHeadline');

  if (!titleWrapper) {
    return;
  }

  var insightTrigger = document.createElement('div');
  insightTrigger.classList.add('ai-chat-insight-trigger-vue-wrapper');
  insightTrigger.setAttribute('vue-entry', 'MistralAI.InsightTrigger');
  insightTrigger.setAttribute('widget-params', JSON.stringify(parameters));
  insightTrigger.setAttribute('ai-name', 'mistral-ai');
  insightTrigger.setAttribute('ai-label', 'Mistral AI');
  insightTrigger.setAttribute('ai-color', '#fd6f00');
  insightTrigger.setAttribute('api-method', 'MistralAI.getInsights');
  titleWrapper.append(insightTrigger);

  piwikHelper.compileVueEntryComponents(insightTrigger);
});
