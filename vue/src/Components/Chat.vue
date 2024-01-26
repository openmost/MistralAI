<template>
  <div class="ai-chat-wrapper">
    <div class="ai-chat-conversation-wrapper">
      <ul id="ai-chat-conversation">
        <ChatMessage v-for="(message, index) in messages" :message="message" :key="index"/>
        <ChatLoading v-if="loading && !errored"/>
      </ul>
      <ChatForm @submit="onSubmit" @success="onSuccess" @error="onError"/>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ChatForm from './ChatForm.vue';
import ChatMessage from './ChatMessage.vue';
import ChatLoading from './ChatLoading.vue';

const errored = ref(false);
const loading = ref(false);
const messages = ref([]);

function onSubmit(prompt) {
  messages.value.push({
    author: 'user',
    body: prompt,
  });
  loading.value = true;
}

function onSuccess(response) {
  loading.value = false;
  response.choices.forEach((choice) => {
    messages.value.push({
      author: 'ai',
      body: choice.message.content,
    });
  });
}

function onError(error) {
  errored.value = true;
}
</script>

<style lang="less">
.ai-chat-conversation-wrapper {
  overflow: auto;
  flex: 1 1 0;
}

#ai-chat-conversation {
  padding-left: 0;
  margin-bottom: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-height: calc(100vh - 275px)
}
</style>
