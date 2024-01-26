document.addEventListener("DOMContentLoaded", function () {

  var messagesList = document.getElementById("ai-chat-conversation");
  var form = document.getElementById("ai-chat-form");
  var promptInput = document.getElementById("ai-chat-prompt");
  var submitButton = document.getElementById("ai-chat-submit-button");

  var ajaxHelper = window.ajaxHelper;
  var ajax = new ajaxHelper();


  if (form && promptInput) {

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      submitButton.innerText = "Loading";
      submitButton.disabled = true;

      var prompt = encodeURIComponent(promptInput.value);
      addUserResponse(promptInput.value);

      ajax.setUrl(`index.php?module=API&method=MistralAI.getResponse&format=json&idSite=${piwik.idSite}&prompt=${prompt}`);
      ajax.setCallback(function (response) {
        submitButton.innerText = "Submit";
        submitButton.disabled = false;

        addMistralResponse(response.choices[0].message.content);
      });
      ajax.setFormat("json"); // the expected response format
      ajax.send();

    });
  }

  function addMistralResponse(message) {
    messagesList.insertAdjacentHTML("beforeend", generateResponseTemplate(false, message));
    scrollToLastMessage();
  }

  function addUserResponse(message) {
    messagesList.insertAdjacentHTML("beforeend", generateResponseTemplate(true, message));
    scrollToLastMessage();
    promptInput.value = "";
  }

  function scrollToLastMessage() {
    messagesList.scrollTop = messagesList.scrollHeight;
  }

  function generateResponseTemplate(isUser, message) {
    return `<li class="ai-chat-response ai-chat-${isUser ? 'user' : 'ai'}-response">
            <div class="ai-chat-response-avatar"></div>
            <div class="ai-chat-response-content-wrapper">
                <span class="ai-chat-response-username">${isUser ? 'You' : 'Mistral AI'}</span>
                 <div class="ai-chat-response-body">${message}</div>
            </div>
        </li>`;
  }

})
