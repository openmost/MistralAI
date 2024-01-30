<template>
  <li :class="chatResponseClasses">
    <div class="ai-chat-response-avatar"></div>
    <div class="ai-chat-response-content-wrapper">
      <span class="ai-chat-response-username">{{ message.author === 'user' ? 'You' : 'AI' }}</span>
      <div class="ai-chat-response-body">{{ message.body }}</div>
    </div>
  </li>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

export default defineComponent({
  props: {
    message: {
      type: Object,
      required: true,
    },
  },
  computed: {
    chatResponseClasses() {
      return [
        'ai-chat-response',
        `ai-chat-${this.message.author}-response`,
      ];
    },
  },
});
</script>

<style lang="less" scoped>
.ai-chat-response {
  width: 100%;
  display: flex;
  gap: .5rem;

  &.ai-chat-ai-response .ai-chat-response-avatar {
    background-color: #fd6f00;
  }

  &.ai-chat-user-response .ai-chat-response-avatar {
    background-color: #3450a3;
  }

  .ai-chat-response-avatar {
    display: block;
    width: 1.5rem;
    min-width: 1.5rem;
    height: 1.5rem;
    aspect-ratio: 1;
    border-radius: 50%;
    overflow: hidden;
  }

  .ai-chat-response-content-wrapper {
    flex-grow: 1;

    .ai-chat-response-username {
      font-weight: 700;
      font-size: 1rem;
    }

    .ai-chat-response-body {
      white-space: pre-line;
      word-break: break-word;
    }
  }
}
</style>
