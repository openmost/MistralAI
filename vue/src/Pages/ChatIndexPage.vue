<template>
  <div>
    <Chat/>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import Chat from '../Components/Chat.vue';

export default defineComponent({
  components: {
    Chat,
  },
});
</script>

<style lang="less" scoped>
body {
  min-height: 100vh;
}

.ai-chat-wrapper {
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  min-height: 726px; //calc(100vh - 175);
}
</style>
