<template>
  <li :class="chatResponseClasses">
    <div class="ai-chat-response-avatar"></div>
    <div class="ai-chat-response-content-wrapper">
      <span class="ai-chat-response-username">{{ chatAuthorName }}</span>
      <div class="ai-chat-response-body" v-html="markdownToHtml"></div>
    </div>
  </li>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { Converter } from 'showdown';

const converter = new Converter();

export default defineComponent({
  props: {
    message: {
      type: Object,
      required: true,
    },
  },
  computed: {
    chatResponseClasses(): Array<string> {
      return [
        'ai-chat-response',
        `ai-chat-${this.message.author}-response`,
      ];
    },
    chatAuthorName(): string {
      return this.message.author === 'user' ? 'You' : 'AI';
    },
    markdownToHtml(): string {
      return converter.makeHtml(this.message.body);
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
      display: block;
      font-weight: 700;
      font-size: 1.125rem;
      margin-bottom: 4px;
    }

    .ai-chat-response-body {
      font-size: 1rem;

      h1 {
        margin-top: 0;
        margin-bottom: 1rem;
        padding: 0;
        font-size: calc(1.375rem + 1.5vw);
      }

      h2 {
        margin-top: 0;
        margin-bottom: 1rem;
        padding: 0;
        font-size: calc(1.325rem + .9vw);
      }

      h3 {
        margin-top: 0;
        margin-bottom: 1rem;
        padding: 0;
        font-size: calc(1.3rem + .6vw);
      }

      h4 {
        margin-top: 0;
        margin-bottom: 1rem;
        padding: 0;
        font-size: calc(1.275rem + .3vw);
      }

      h5 {
        margin-top: 0;
        margin-bottom: 1rem;
        padding: 0;
        font-size: 1.25rem;
      }

      h6 {
        margin-top: 0;
        margin-bottom: 1rem;
        padding: 0;
        font-size: 1.125rem;
      }

      p {
        margin-top: 0;
        margin-bottom: 1rem;
      }

      hr {
        height: unset;
        margin-top: unset;
        margin-bottom: unset;
        border-top: unset;
      }

      p code {
        font-family: monospace;
        color: #e261a1;
        padding: 0;
        font-size: .875em;
        background-color: unset;
        border-radius: unset;
      }

      pre {
        overflow: auto;
        margin-top: 0;
        margin-bottom: 1rem;

        code {
          color: #fff !important;
        }
      }

      ul {
        margin-top: 0;
        margin-bottom: 1rem;
        list-style: inside;

        li {
          &:not(:last-child) {
            margin-bottom: .5rem;
          }
        }
      }

      ol {
        margin-top: 0;
        margin-bottom: 1rem;
        list-style: number;

        li {
          &:not(:last-child) {
            margin-bottom: .5rem;
          }
        }
      }
    }
  }
}
</style>
