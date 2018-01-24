<template>
    <div id="app">
        <!--<h1>Система статистики</h1>-->
        <div class="float-right">
            <button @click="refresh()">Обновить</button>
        </div>
        <ul class="menu simple">
            <li><b>Аналитика</b>:</li>
            <li><a href="/#/leads">Каналы</a></li>
            <li><a href="/#/manager">Продажи</a></li>
        </ul>
        <div class="clearfix"></div>
        <router-view/>
    </div>
</template>

<script>
  export default {
    name: 'app',
    updated () {
      BX24.fitWindow()
    },
    methods: {
      refresh () {
        sessionStorage.clear()
        location.reload()
      }
    }
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
    @include foundation-menu;
    @include foundation-float-classes;
</style>
