(function () {
  var OVERLAY_HOST_ID = 'ai-chat-insight-overlay-host-mistralai';

  // A single insights panel for the whole page, every report trigger drives it through a shared store
  function mountInsightOverlay() {
    if (document.getElementById(OVERLAY_HOST_ID)) {
      return;
    }

    var host = document.createElement('div');
    host.id = OVERLAY_HOST_ID;
    host.setAttribute('vue-entry', 'MistralAI.InsightOverlay');
    host.setAttribute('ai-name', 'mistral-ai');
    host.setAttribute('ai-label', 'MistralAI');
    host.setAttribute('ai-color', '#FA500F');
    host.setAttribute('api-method', 'MistralAI.getInsights');
    document.body.appendChild(host);

    piwikHelper.compileVueEntryComponents(host);
  }

  window.addEventListener('widget:loaded', function (e) {
    var parameters = e.detail[0].parameters;
    var element = e.detail[0].element[0];
    // The report header toolbar holds the annotations and export buttons, the trigger goes first in it so it gets
    // the same size and the toolbar's own spacing. Older widget headers only have the title.
    var toolbar = element.querySelector('.reportHeader__toolbar');
    var titleWrapper = element.querySelector('.enrichedHeadline');

    if (!toolbar && !titleWrapper) {
      return;
    }

    // the enriched headline also holds the help and feedback texts, only its .title is the name
    var titleElement = element.querySelector('.enrichedHeadline .title')
      || element.querySelector('.reportHeader__title')
      || element.querySelector('.widgetName');
    var reportTitle = titleElement ? titleElement.textContent.trim() : '';

    mountInsightOverlay();

    var insightTrigger = document.createElement('div');
    insightTrigger.classList.add('ai-chat-insight-trigger-vue-wrapper');
    insightTrigger.setAttribute('vue-entry', 'MistralAI.InsightTrigger');
    insightTrigger.setAttribute('widget-params', JSON.stringify(parameters));
    insightTrigger.setAttribute('ai-name', 'mistral-ai');
    insightTrigger.setAttribute('ai-label', 'MistralAI');
    insightTrigger.setAttribute('ai-color', '#FA500F');
    insightTrigger.setAttribute('report-title', reportTitle);
    if (toolbar) {
      toolbar.prepend(insightTrigger);
    } else {
      titleWrapper.append(insightTrigger);
    }

    piwikHelper.compileVueEntryComponents(insightTrigger);
  });
})();
