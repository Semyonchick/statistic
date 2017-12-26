<template>
    <div>
        <date-select></date-select>

        <table v-if=" date" width="100%" cellspacing="0" cellpadding="0">
            <thead>
            <tr>
                <th>Источники</th>
                <th>Расходы</th>
                <th>Цена сделки</th>
                <th>Заявки</th>
                <th>Сделки</th>
                <th>Брак</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="row in leadStatistic">
                <th>{{row.name}}</th>
                <td>{{row.price}}</td>
                <td>{{row.priceDeal}}</td>
                <td class="primary">{{row.leads || '-'}}
                    <span class="opacity" v-if="row.leadsPercent">/ {{row.leadsPercent}}%</span>
                </td>
                <td class="success">{{row.leadDeals || '-'}}
                    <span class="opacity" v-if="row.leadDealsPercent">/ {{row.leadDealsPercent}}%</span>
                </td>
                <td class="bad">{{row.leadCanceled || '-'}}
                    <span class="opacity" v-if="row.leadCanceledPercent">/ {{row.leadCanceledPercent}}%</span></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>ИТОГО:</th>
                <td>{{calculate(false)}}</td>
                <td>{{counts.leadDeals ? Math.round(calculate(false) / counts.leadDeals) : '***'}}</td>
                <td class="primary">{{counts.leads}}</td>
                <td class="success">{{counts.leadDeals}}</td>
                <td class="bad">{{counts.leadCanceled}}</td>
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
    name: 'LeadAnalytic',
    data () {
      return {
        date: '',
        list: {},
        info: [],
        leads: [],
        leadStatusList: []
      }
    },
    methods: {
      calculate: function (key) {
        return Math.round(this.filteredInfo.filter(value => key ? value.sourceId === key : true).map(value => value.price).reduce((a, b) => a + b, 0))
      },
      getInfo: function () {
        let info = []
        BX24.callMethod('lists.element.get', {
          IBLOCK_TYPE_ID: 'lists',
          IBLOCK_ID: '30',
          ELEMENT_ORDER: {ID: 'DESC'}
        }, (data) => {
          if (info.length === data.answer.next) return
          info = info.concat(data.answer.result)
          if (data.answer.next) data.next()
          else this.info = info
        })
      },
      getLeads: function () {
        let leads = []
        BX24.callMethod('crm.lead.list', {
          order: {'ID': 'ASC'},
          filter: {'>DATE_CREATE': this.date.dateFrom, '<DATE_CREATE': this.date.dateTo},
          select: ['ID', 'STATUS_ID', 'SOURCE_ID']
        }, (data) => {
          if (leads.length === data.answer.next) return
          leads = leads.concat(data.answer.result)
          if (data.answer.next) data.next()
          else this.leads = leads
        })
      }
    },
    computed: {
      filteredInfo: function () {
        if (!this.info) return []

        this.getLeads()
        const baseDates = [this.date.dateFrom, this.date.dateTo].map(value => +value.replace(/-/g, ''))
        return this.info.map((row) => {
          let dates = [row.ACTIVE_FROM, row.ACTIVE_TO].map(value => +value.split(' ')[0].split('.').reverse().join(''))
          let price = Object.values(row.PROPERTY_114)[0] / (dates[1] - dates[0] + 1)
          if (baseDates[0] <= dates[1] && baseDates[1] >= dates[0]) {
            price = price * (Math.min(baseDates[1], dates[1]) - Math.max(baseDates[0], dates[0]) + 1)
          } else price = 0
          return {
            sourceId: Object.values(row.PROPERTY_112)[0],
            price: price
          }
        })
      },
      leadStatistic: function () {
        let result = []
        for (let key in this.list) {
          let value = this.list[key]
          let data = {}
          let filter = value ? this.leadStatusList.filter(row => row.NAME === value) : value

          data['sourceId'] = filter.length ? filter[0].STATUS_ID : null
          data['name'] = value
          data['price'] = this.calculate(key)
          Object.keys(this.counts).forEach((value) => {
            data[value] = this[value].filter(row => row.SOURCE_ID === data['sourceId']).length
            data[value + 'Percent'] = Math.round(data[value] * 100 / this.counts[value])
          })
          data['priceDeal'] = data['leadDeals'] ? Math.round(data['price'] / data['leadDeals']) : data['price'] ? '***' : 0
          result.push(data)
        }
        return result
      },
      leadCanceled: function () {
        return this.leads.filter(row => row.STATUS_ID === 'JUNK')
      },
      leadDeals: function () {
        return this.leads.filter(row => row.STATUS_ID === 'CONVERTED')
      },
      counts: function () {
        return {
          leads: this.leads.length,
          leadDeals: this.leadDeals.length,
          leadCanceled: this.leadCanceled.length
        }
      }
    },
    created () {
      BX24.callMethod('lists.field.get', {
        IBLOCK_TYPE_ID: 'lists',
        IBLOCK_ID: '30',
        FIELD_ID: 'PROPERTY_112'
      }, (data) => {
        this.list = data.answer.result.L.DISPLAY_VALUES_FORM
        this.list[null] = '*** без источника'
      })

      BX24.callMethod('crm.status.list', {
        filter: {'ENTITY_ID': 'SOURCE'},
        select: ['STATUS_ID', 'NAME']
      }, (data) => {
        this.leadStatusList = data.answer.result
      })

      this.getInfo()

      this.$nextTick(_ => {
        this.date = this.$children[0]
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
        width: 40%;
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
