<template>
  <div class="ai-chat-form-wrapper">
    <form id="ai-chat-form" @submit.prevent="onFormSubmit">

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

<script lang="ts">
import { defineComponent } from 'vue';
import { AjaxHelper } from 'CoreHome';

interface DataState {
  prompt: string;
  loading: boolean;
}

export default defineComponent({
  data(): DataState {
    return {
      prompt: '',
      loading: false,
    };
  },
  methods: {
    onFormSubmit() {
      this.$emit('formSubmit', this.prompt);
      this.loading = true;
      AjaxHelper
        .fetch({
          method: 'MistralAI.getResponse',
          prompt: this.prompt,
        })
        .then((response) => {
          this.prompt = '';
          this.$emit('success', response.choices[0].message.content);
        })
        .catch((error) => this.$emit('error', error))
        .finally(() => {
          this.loading = false;
        });
    },
  },
});
</script>

<style lang="less" scoped>
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
