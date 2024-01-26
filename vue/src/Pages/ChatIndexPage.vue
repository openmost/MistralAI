<template>
  <div>
    <Chat/>
  </div>
</template>

<script lang="ts" setup>
import Chat from '../Components/Chat.vue';
</script>

<style lang="less">
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
