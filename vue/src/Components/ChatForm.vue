<template>
  <div class="ai-chat-form-wrapper">
    <form id="ai-chat-form" @submit.prevent="onSubmit">

      <div class="ai-chat-form-group">
        <input v-model="prompt"
               type="text"
               name="mistral-prompt"
               id="ai-chat-prompt"
               placeholder="Ask Mistral AI..."
               aria-label="Mistral AI Prompt"
               minlength="1"
               required
               autofocus
        >
        <button type="submit" class="btn" id="ai-chat-submit-button" :disabled="loading">
          {{ loading ? 'Loading' : 'Submit' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script lang="ts" setup>
import { ref, defineEmits } from 'vue';
import { AjaxHelper } from 'CoreHome';

const emit = defineEmits(['submit', 'success', 'error']);
const prompt = ref('');
const loading = ref(false);

function onSubmit() {
  emit('submit', prompt.value);
  loading.value = true;
  AjaxHelper
    .fetch({
      method: 'MistralAI.getResponse',
      prompt: prompt.value,
    })
    .then((response) => {
      prompt.value = '';
      emit('success', response);
    })
    .catch((error) => emit('error', error))
    .finally(() => {
      loading.value = false;
    });
}
</script>

<style lang="less">
.ai-chat-form-wrapper {
  padding: 1rem;
  border: 1px solid #9e9e9e;
  border-radius: .5rem;
}

.ai-chat-form-group {
  display: flex;
  gap: 1rem;
  align-items: center;
}
</style>
