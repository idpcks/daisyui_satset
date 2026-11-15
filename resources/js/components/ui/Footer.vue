
<script setup>
const currentYear = new Date().getFullYear()
</script>

<template>
  <footer
    class="footer sm:footer-horizontal bg-neutral text-neutral-content items-center p-4">
    <aside class="grid-flow-col items-center">
      <p>Copyright © {{ currentYear }}. All rights reserved.</p>
    </aside>

  </footer>
</template>
