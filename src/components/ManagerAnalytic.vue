<template>
    <div>
        <date-select></date-select>

        <table v-if="users.length && dateComponent" width="100%" cellspacing="0" cellpadding="0">
            <thead>
            <tr>
                <th>Пользователи</th>
                <th v-for="user in managerInfo">{{user.NAME}} {{user.LAST_NAME[0]}}.</th>
            </tr>
            </thead>
            <tbody>
            <!--<tr>
                <th>Звонки </th>
                <td v-for="user in managerInfo">{{user.ID}}</td>
            </tr>-->
            <tr>
                <th>Заявок в работе</th>
                <td v-for="user in managerInfo">{{user.st.dealsCount}}</td>
            </tr>
            <tr>
                <th>Новых</th>
                <td v-for="user in managerInfo">{{user.st.dealsNewCount}}</td>
            </tr>
            <tr>
                <th>Потеряно</th>
                <td v-for="user in managerInfo">{{user.st.dealsLoseCount}}</td>
            </tr>
            <!--<tr>
                <th>Показы</th>
                <td v-for="user in managerInfo">-</td>
            </tr>-->
            <tr>
                <th>Авансы</th>
                <td v-for="user in managerInfo">{{user.st.prepaid}}
                    <span class="opacity" v-if="user.st.prepaidSum">/ {{user.st.prepaidSum}} руб.</span>
                </td>
            </tr>
            <tr>
                <th>Зарегистрировано ДДУ,</th>
                <td v-for="user in managerInfo">{{user.st.registered}}
                    <span class="opacity" v-if="user.st.registeredSum">/ {{user.st.registeredSum}} руб.</span></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>Эффективность:</th>
                <td v-for="user in managerInfo" v-if="user.st.dealsCount">{{Math.round(user.st.registered/user.st.dealsCount*100)}}%</td>
            </tr>

            </tfoot>
        </table>

        <div v-if="admin">
            <button @click="remove()">delete</button>
            <button @click="install()">install</button>
            <button @click="move()">update</button>
        </div>
    </div>
</template>

<script>
  import BX from '../services/BXService'
  import DateSelect from './DateSelect.vue'

  export default {
    components: {DateSelect},
    name: 'ManagerAnalytic',
    data () {
      return {
        admin: false,
        dateComponent: false,
        deals: [],
        users: []
      }
    },
    methods: {
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
    computed: {
      getDeals: function () {
        let select = ['*']
        return new Promise((resolve, reject) => {
          BX.get('crm.deal.list', {
            filter: {
              CATEGORY_ID: 0,
              '<=DATE_CREATE': this.dateComponent.dateTo,
              '>=CLOSEDATE': this.dateComponent.dateFrom
            },
            select: select
          }).then(result => {
            BX.get('crm.deal.list', {
              filter: {
                CATEGORY_ID: 0,
                '<=DATE_CREATE': this.dateComponent.dateTo,
                'CLOSED': 'N'
              },
              select: select
            }).then(result2 => {
              resolve(result.concat(result2))
            })
          })
        })
      },
      getStatus: function () {
        return BX.get('entity.item.get', {
          ENTITY: 'dealStatus',
          FILTER: {
            '><DATE_ACTIVE_FROM': [this.dateComponent.dateFrom, this.dateComponent.dateTo]
          }
        })
      },
      managerInfo: function () {
        this.users = this.users.map(user => {
          user.st = {}
          this.getDeals.then(deals => deals.filter(row => row.ASSIGNED_BY_ID === user.ID)).then(deals => {
            user.st.dealsCount = deals.length
            user.st.dealsNewCount = deals.filter(row => row.BEGINDATE >= this.dateComponent.dateFrom).length
            user.st.dealsLoseCount = deals.filter(row => row.CLOSEDATE <= this.dateComponent.dateTo && row.STAGE_ID === 'LOSE').length
            this.$forceUpdate()
          })
          this.getStatus.then(statuses => statuses.filter(row => row.PROPERTY_VALUES.user === user.ID && +row.PROPERTY_VALUES.price)).then(statuses => {
            let registered = statuses.filter(row => row.PROPERTY_VALUES.status === 'register')
            user.st.registered = registered.length
            if (user.st.registered) {
              user.st.registeredSum = registered.map(row => +row.PROPERTY_VALUES.price).reduce((a, b) => {
                return a + b
              })
            }
            let prepaid = statuses.filter(row => row.PROPERTY_VALUES.status === 'prepaid')
            console.log(prepaid)
            user.st.prepaid = prepaid.length
            if (user.st.prepaid) {
              user.st.prepaidSum = prepaid.map(row => +row.PROPERTY_VALUES.price).reduce((a, b) => {
                return a + b
              })
            }
            this.$forceUpdate()
          })
          return user
        })
        return this.users
      }
    },
    created () {
      this.move()

      BX.get('user.get', {
        FILTER: {UF_DEPARTMENT: 114}
      }).then((data) => {
        this.users = data
      })

      this.$nextTick(_ => {
        this.dateComponent = this.$children[0]
      })
    }
  }
</script>

<style scoped>
    .radioList label {
        font-size: 14px;
    }

    table {
        font-size: 14px;
        table-layout: fixed;
    }

    tr th:first-child {
        text-align: left;
        width: 25%;
    }

    tbody tr:hover {
        background: #ffebfb;
    }

    .primary {
        color: blue;
    }

    .success {
        color: green;
    }

    .bad {
        color: red;
    }

    .opacity {
        opacity: 0.5;
    }
</style>
