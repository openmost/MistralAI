document.addEventListener('DOMContentLoaded', function () {

  const messagesList = document.getElementById('mistral-messages-list');


  const ajaxHelper = window.ajaxHelper;
  const ajax = new ajaxHelper();
  const form = document.getElementById('mistral-form');
  const promptInput = document.getElementById('mistral-prompt')
  const submitButton = document.getElementById('mistral-submit-button');

  if (form && promptInput) {

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      submitButton.innerText = 'Loading';
      submitButton.disabled = true;

      const prompt = encodeURIComponent(promptInput.value);
      addUserResponse(promptInput.value);

      ajax.setUrl(`index.php?module=API&method=MistralAI.getResponse&format=json&idSite=${piwik.idSite}&prompt=${prompt}`);
      ajax.setCallback(function (response) {
        submitButton.innerText = 'Submit';
        submitButton.disabled = false;

        addMistralResponse(response.choices[0].message.content);
      });
      ajax.setFormat('json'); // the expected response format
      ajax.send();

    })
  }

  function addMistralResponse(message) {
    messagesList.insertAdjacentHTML('beforeend', generateResponseTemplate(false, message))
  }

  function addUserResponse(message) {
    messagesList.insertAdjacentHTML('beforeend', generateResponseTemplate(true, message))
    promptInput.value = '';
  }

  function generateResponseTemplate(isUser, message) {
    return `<li class="mistral-message">
              <img class="message-avatar" src="https://via.placeholder.com/100" alt="">
              <div class="message-body">
                <strong class="message-author">${isUser ? 'You' : 'Mistral AI'}</strong>
                <p>${message}</p>
              </div>
            </li>`;
  }

})
