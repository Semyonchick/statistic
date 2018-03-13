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
                <td v-for="user in managerInfo"><a href="#" @click="goTo('all', user)">{{user.st.dealsCount}}</a></td>
            </tr>
            <tr>
                <th>Новых</th>
                <td v-for="user in managerInfo"><a href="#" @click="goTo('new', user)">{{user.st.dealsNewCount}}</a>
                </td>
            </tr>
            <tr>
                <th>Потеряно</th>
                <td v-for="user in managerInfo"><a href="#" @click="goTo('fail', user)">{{user.st.dealsLoseCount}}</a></td>
            </tr>
            <!--<tr>
                <th>Показы</th>
                <td v-for="user in managerInfo">-</td>
            </tr>-->
            <tr>
                <th>Авансы</th>
                <td v-for="user in managerInfo"><a href="#" @click="goTo('prepay', user)">{{user.st.prepaid}}</a>
                    <span class="opacity" v-if="user.st.prepaidSum">/ {{user.st.prepaidSum}} руб.</span>
                </td>
            </tr>
            <tr>
                <th>Зарегистрировано ДДУ</th>
                <td v-for="user in managerInfo"><a href="#" @click="goTo('register', user)">{{user.st.registered}}</a>
                    <span class="opacity" v-if="user.st.registeredSum">/ {{user.st.registeredSum}} руб.</span></td>
            </tr>
            </tbody>

            <tfoot>
            <tr>
                <th>Эффективность:</th>
                <td v-for="user in managerInfo">
                    <template v-if="user.st.dealsCount">{{Math.round(user.st.registered / user.st.dealsCount * 100)}}%</template>
                </td>
            </tr>
            </tfoot>
        </table>
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
        dateComponent: false,
        deals: [],
        baseFilter: {
          CATEGORY_ID: 0,
          'UF_CRM_1512969036': 100
        },
        users: []
      }
    },
    methods: {
      goTo (type, row) {
        let path = '/crm/deal/category/0/'
        let dates = '-from-' + this.$children[0].dateFrom.split('-').reverse().join('.') + '-to-' + this.$children[0].dateTo.split('-').reverse().join('.')
        let params = []
        params.push('UF_CRM_1512969036-is-100')
        if (row) {
          params.push('ASSIGNED_BY_ID-is-' + row.ID)
          params.push('ASSIGNED_BY_ID_label-is-' + (row.LAST_NAME + ' ' + row.NAME))
        }

        if (type === 'new') {
          params.push('BEGINDATE' + dates)
        } else if (type === 'fail') {
          params.push('CLOSEDATE' + dates)
          params.push('STAGE_ID-is-LOSE')
        } else if (type === 'prepay') {
          params.push('UF_CRM_1517221718' + dates)
        } else if (type === 'register') {
          params.push('UF_CRM_1512978954235' + dates)
        } else {
          params.push('DATE_CREATE-to-' + this.$children[0].dateTo.split('-').reverse().join('.'))
          params.push('DATE_MODIFY-from-' + this.$children[0].dateFrom.split('-').reverse().join('.'))
        }

        let url = '//holding-gel.bitrix24.ru' + path + '#/f/' + params.join('/') + '/'

        window.top.location.href = url
      },
      inDates (date) {
        return date ? date >= this.dateComponent.dateFrom && date <= this.dateComponent.dateTo : false
      }
    },
    computed: {
      getDeals: function () {
        let select = ['*', 'UF_CRM_1512978954235', 'UF_CRM_1517221718', 'UF_CRM_1512967601319']
        return BX.deals(this.dateComponent.dateFrom, this.dateComponent.dateTo, this.baseFilter, select)
      },
      getStatus: function () {
        return BX.get('entity.item.get', {
          ENTITY: 'dealStatus',
          FILTER: {
            '><DATE_ACTIVE_FROM': [this.dateComponent.dateFrom, this.dateComponent.dateTo]
          }
        })
      },
      getDealPrice: function () {
        return BX.get('lists.element.get', {
          IBLOCK_TYPE_ID: 'lists',
          IBLOCK_ID: '80',
          ELEMENT_ORDER: {ID: 'DESC'}
        })
      },
      managerInfo () {
        return this.users.map(user => {
          user.st = {}
          this.getDeals.then(deals => deals.filter(row => row.ASSIGNED_BY_ID === user.ID)).then(deals => {
            user.st.dealsCount = deals.length
            user.st.dealsNewCount = deals.filter(deal => this.inDates(deal.BEGINDATE)).length
            user.st.dealsLoseCount = deals.filter(deal => this.inDates(deal.CLOSEDATE) && deal.STAGE_ID === 'LOSE').length

            let registered = deals.filter(deal => this.inDates(deal.UF_CRM_1512978954235))
            user.st.registered = registered.length
            if (user.st.registered && registered.length) {
              user.st.registeredSum = registered.map(deal => +deal.OPPORTUNITY || 0).reduce((a, b) => {
                return a + b
              })
            }
            let prepaid = deals.filter(deal => this.inDates(deal.UF_CRM_1517221718))
            user.st.prepaid = prepaid.length
            if (user.st.prepaid) {
              user.st.prepaidSum = prepaid.map(deal => +deal.UF_CRM_1512967601319 || 0).reduce((a, b) => {
                return a + b
              })
            }

            this.$forceUpdate()
          })

//          this.getStatus.then(statuses => {
//            return statuses.filter(row => row.PROPERTY_VALUES && row.PROPERTY_VALUES.user === user.ID && +row.PROPERTY_VALUES.price)
//          }).then(statuses => {
//            this.getDeals.then(deals => deals.filter(row => row.ASSIGNED_BY_ID === user.ID)).then(deals => {
//              user.st.dealsCount = deals.length
//              user.st.dealsNewCount = deals.filter(row => row.BEGINDATE >= this.dateComponent.dateFrom).length
//              user.st.dealsLoseCount = deals.filter(row => row.CLOSEDATE <= this.dateComponent.dateTo && row.STAGE_ID === 'LOSE').length
//
//              statuses = deals.filter(status => deals.filter(deal => deal.ID === status.PROPERTY_VALUES.deal).length)
//              let registered = statuses.filter(row => row.PROPERTY_VALUES.status === 'register')
//              user.st.registered = registered.length
//              if (user.st.registered) {
//                user.st.registeredSum = registered.map(row => +row.PROPERTY_VALUES.price).reduce((a, b) => {
//                  return a + b
//                })
//              }
//              let prepaid = statuses.filter(row => row.PROPERTY_VALUES.status === 'prepaid')
//              user.st.prepaid = prepaid.length
//              if (user.st.prepaid) {
//                user.st.prepaidSum = prepaid.map(row => +row.PROPERTY_VALUES.price).reduce((a, b) => {
//                  return a + b
//                })
//              }
//
//              user.st.total = deals.filter(deal => registered.filter(status => deal.ID === status.PROPERTY_VALUES.deal).length || deal.STAGE_ID === 'LOSE').map(deal => deal.price).reduce((a, b) => +a + +b)
//
//              this.$forceUpdate()
//            })
//          })
          return user
        })
      }
    },
    created () {
      BX.get('user.get', {
        FILTER: {UF_DEPARTMENT: 114}
      }).then(data => {
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
