<template>
  <div class="ai-chat-wrapper">
    <div class="ai-chat-conversation-wrapper">
      <ul class="ai-chat-conversation">
        <ChatMessage v-for="(message, index) in messages" :message="message" :key="index"/>
        <ChatLoading v-if="loading && !errored"/>

        <Alert v-if="errored" severity="danger">Ooops, AI have encountered an error.</Alert>

      </ul>
      <ChatForm @formSubmit="onFormSubmit" @success="onSuccess" @error="onError"/>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import ChatForm from './ChatForm.vue';
import ChatMessage from './ChatMessage.vue';
import ChatLoading from './ChatLoading.vue';
import Alert from '../../../../CoreHome/vue/src/Alert/Alert.vue';

interface MessageState {
  author: string,
  body: string,
}

interface DataState {
  errored: boolean;
  loading: boolean;
  messages: Array<MessageState>;
}

export default defineComponent({
  components: {
    Alert,
    ChatForm,
    ChatMessage,
    ChatLoading,
  },
  data(): DataState {
    return {
      errored: false,
      loading: false,
      messages: [],
    };
  },
  methods: {
    onFormSubmit(prompt: string) {
      this.messages.push({
        author: 'user',
        body: prompt,
      });
      this.loading = true;
    },
    onSuccess(response: string) {
      this.messages.push({
        author: 'ai',
        body: response,
      });
      this.loading = false;
    },
    onError() {
      this.errored = true;
    },
  },
});
</script>

<style lang="less" scoped>
.ai-chat-conversation-wrapper {
  overflow: auto;
  flex: 1 1 0;

  .ai-chat-conversation {
    padding-left: 0;
    margin-bottom: 0;
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    min-height: calc(100vh - 275px)
  }
}
</style>
