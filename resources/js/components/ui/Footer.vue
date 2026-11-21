
<script setup>
import { useI18n } from '@/lib/i18n';

const currentYear = new Date().getFullYear()
const { t } = useI18n()
</script>

<template>
  <footer
    class="footer sm:footer-horizontal bg-neutral text-neutral-content items-center p-4 max-sm:hidden">
    <aside class="grid-flow-col items-center">
      <p>{{ t('Copyright © :year. All rights reserved.', { year: currentYear }) }}</p>
    </aside>

  </footer>
</template>
