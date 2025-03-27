<template>
  <div class="container container-alt" id="app">
    <Navbar/>
    <router-view v-if="$root.meta" />
  </div>
</template>

<script>
import Navbar from '@/components/Navbar.vue'

export default {
  components: {
    Navbar
  },
}
</script>