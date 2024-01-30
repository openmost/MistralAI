<template>
  <div class="ai-chat-interface-wrapper">
    <ChatMessagesList :loading="loading" :errored="errored" :messages="messages"/>
    <ChatForm @formSubmit="onFormSubmit" @success="onSuccess" @error="onError"/>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import ChatForm from './ChatForm.vue';
import ChatMessagesList from './ChatMessagesList.vue';

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
    ChatMessagesList,
    ChatForm,
  },
  data(): DataState {
    return {
      errored: false,
      loading: false,
      messages: [
        {
          author: 'user',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'ai',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'user',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'ai',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'user',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'ai',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'user',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
        {
          author: 'ai',
          body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.',
        },
      ],
    };
  },
  methods: {
    onFormSubmit(prompt: string) {
      this.messages.push({
        author: 'user',
        body: prompt,
      });
      this.loading = true;
      this.scrollDown();
    },
    onSuccess(response: string) {
      this.messages.push({
        author: 'ai',
        body: response,
      });
      this.loading = false;
      this.scrollDown();
    },
    onError() {
      this.errored = true;
    },
    scrollDown() {
      console.log('SCROLL DOWN', window, document);
      setTimeout(() => window.scrollTo(0, document.body.scrollHeight), 1);
    },
  },
});
</script>

<style lang="less" scoped>
.ai-chat-conversation-wrapper {
  position: relative;
}
</style>
