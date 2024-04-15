window.addEventListener('widget:loaded', function (e) {
  let parameters = e.detail[0].parameters;
  let element = e.detail[0].element[0];
  let reportId = parameters.module + '.' + parameters.action;

  let titleWrapper = element.querySelector('.enrichedHeadline');

  let insightTrigger = document.createElement('div');
  insightTrigger.classList.add('ai-chat-insight-trigger-vue-wrapper');
  insightTrigger.setAttribute('vue-entry', 'MistralAI.InsightTrigger');
  insightTrigger.setAttribute('report-id', reportId);
  insightTrigger.setAttribute('ai-name', 'mistral-ai');
  insightTrigger.setAttribute('ai-label', 'Mistral AI');
  insightTrigger.setAttribute('ai-color', '#fd6f00');
  insightTrigger.setAttribute('api-method', 'MistralAI.getInsights');
  titleWrapper.append(insightTrigger);

  piwikHelper.compileVueEntryComponents(insightTrigger);
});
