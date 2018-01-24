<template>
    <div id="app">
        <!--<h1>Система статистики</h1>-->
        <div class="float-right">
            <button @click="refresh()" class="button hollow small">&#x21bb;</button>
        </div>
        <ul class="menu simple">
            <li><b>Аналитика</b>:</li>
            <li><a href="/#/leads">Каналы</a></li>
            <li><a href="/#/manager">Продажи</a></li>
            <li v-if="admin">
                <a href="#" @click.prevent="remove()">delete</a>
            </li>
            <li v-if="admin">
                <a href="#" @click.prevent="install()">install</a>
            </li>
            <li v-if="admin">
                <a href="#" @click.prevent="move()">update</a>
            </li>
        </ul>
        <div class="clearfix"></div>
        <router-view/>
    </div>
</template>

<script>
  import BX from './services/BXService'

  export default {
    name: 'app',
    data () {
      return {
        admin: false
      }
    },
    updated () {
      BX24.fitWindow()
    },
    methods: {
      refresh () {
        sessionStorage.clear()
        location.reload()
      },
      move () {
        BX.get('lists.element.get', {
          IBLOCK_TYPE_ID: 'lists',
          IBLOCK_ID: 32,
          ELEMENT_ORDER: {ID: 'DESC'}
        }).then((data) => {
          let addList = []
          data.forEach(row => {
            BX.get('entity.item.get', {ENTITY: 'dealStatus', FILTER: {'NAME': row.NAME.trim()}}).then(list => {
              if (list.length === 0 && row.PROPERTY_120 && row.PROPERTY_118 && addList.indexOf(row.NAME) === -1) {
                addList.push(row.NAME)
                let params = {
                  ENTITY: 'dealStatus',
                  DATE_ACTIVE_FROM: row.DATE_CREATE,
                  NAME: row.NAME,
                  PROPERTY_VALUES: {
                    status: Object.values(row.PROPERTY_122 || {})[0],
                    deal: Object.values(row.PROPERTY_120 || {})[0],
                    user: Object.values(row.PROPERTY_118 || {})[0],
                    price: Object.values(row.PROPERTY_126 || {})[0].replace(/[^\d\\.]/g, '') || 0
                  }
                }
                BX.get('entity.item.add', params)
              }
            })
          })
        })
      },
      install () {
        return BX.batch([
          ['entity.add', {ENTITY: 'dealStatus', NAME: 'Статусы сделок'}],
          ['entity.item.property.add', {ENTITY: 'dealStatus', PROPERTY: 'status', NAME: 'Статус', TYPE: 'S'}],
          ['entity.item.property.add', {ENTITY: 'dealStatus', PROPERTY: 'deal', NAME: 'Сделка', TYPE: 'N'}],
          ['entity.item.property.add', {ENTITY: 'dealStatus', PROPERTY: 'user', NAME: 'Сотрудник', TYPE: 'N'}],
          ['entity.item.property.add', {ENTITY: 'dealStatus', PROPERTY: 'price', NAME: 'Сумма', TYPE: 'N'}]
        ]).then(_ => console.log('data is installed'))
      },
      remove () {
        return BX.get('entity.delete', {ENTITY: 'dealStatus'}).then(_ => console.log('data is deleted'))
      }
    },
    created () {
      this.move()
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
