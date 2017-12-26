<template>
  <div id="app">
    <!--<h1>Система статистики</h1>-->
    <router-view/>
  </div>
</template>

<script>
export default {
  name: 'app'
}
</script>

<style lang="scss">
  @import "~foundation-sites/scss/foundation.scss";
  $base-font-size: 14px;
  @include normalize();
  @include foundation-table;
  @include foundation-button;
  @include foundation-forms;
  @include foundation-xy-grid-classes;
  @include foundation-text-alignment;

  tr.dataField td span {
    opacity: 0.5;
    margin-left: 3px;
  }
</style>
