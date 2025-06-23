<script setup>
const props = defineProps(['crackedHash'])
</script>

<template>
  <div class="results--container">
    <p v-if="props.crackedHash.found">
      Cracked hash: {{ props.crackedHash.hash }}, Password: {{ props.crackedHash.password }}. Time tacken: {{ props.crackedHash.time_taken }}
    </p>
    <p v-else>Can't crack</p>
  </div>
</template>

<style scoped>

</style>