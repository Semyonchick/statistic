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
            <tr>

            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>ИТОГО:</th>
            </tr>

            </tfoot>
        </table>
    </div>
</template>

<script>
  /* eslint-disable no-undef */

  import DateSelect from './DateSelect.vue'

  export default {
    components: {DateSelect},
    name: 'ManagerAnalytic',
    data () {
      return {
        dateComponent: false,
        deals: [],
        users: []
      }
    },
    methods: {
      getDeals: function () {
        let result = []
        BX24.callMethod('crm.deal.list', {
          filter: {
            CATEGORY_ID: 0,
            '<=DATE_CREATE': this.dateComponent.dateTo,
            '<=DATE_MODIFY': this.dateComponent.dateTo,
            '>=CLOSEDATE': this.dateComponent.dateFrom
          }
//                    select: ['OPPORTUNITY', 'UF_CRM_1512967601319', 'UF_CRM_PB_AREA'],
        }, (data) => {
          result = result.concat(data.answer.result)
          if (data.answer.next) {
            data.next()
          } else {
            this.deals = result
          }
        })
      }
    },
    computed: {
      managerInfo: function () {
        return this.users
      }
    },
    created () {
      BX24.callMethod('user.get', {
        FILTER: {UF_DEPARTMENT: 16}
      }, (data) => {
        this.users = data.answer.result
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
