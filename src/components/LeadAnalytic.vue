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
            <tr v-for="row in leadStatistic" v-if="row.leads || row.price">
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
  import DateSelect from './DateSelect.vue'
  import BX from '../services/BXService'

  export default {
    components: {DateSelect},
    name: 'LeadAnalytic',
    data () {
      return {
        date: '',
        list: {},
        info: [],
        leads: [],
        deals: [],
        inDeals: [],
        dealStatusList: [],
        leadStatusList: []
      }
    },
    methods: {
      calculate: function (key) {
        return Math.round(this.filteredInfo.filter(value => key ? value.sourceId === key : true).map(value => value.price).reduce((a, b) => a + b, 0))
      },
      getInfo: function () {
        BX.get('lists.element.get', {
          IBLOCK_TYPE_ID: 'lists',
          IBLOCK_ID: '30',
          ELEMENT_ORDER: {ID: 'DESC'}
        }).then((data) => {
          this.info = data
        })
      },
      getLeads: function () {
        this.leads = []
        BX.get('crm.deal.list', {
          filter: {
            '>=DATE_CREATE': this.date.dateFrom,
            '<=DATE_CREATE': this.date.dateTo,
            CATEGORY_ID: 0,
            '!UF_CRM_1512969036': 100
          },
          select: ['ID', 'LEAD_ID', 'STAGE_ID', 'UF_CRM_1512969036', 'DATE_CREATE']
        }).then((data) => {
          this.deals = data.filter(row => !row.LEAD_ID)
          this.deals.forEach(row => this.leads.push(row))
          this.inDeals = data.map(row => row.LEAD_ID)
          this.$forceUpdate()
        })
        BX.get('crm.lead.list', {
          order: {'ID': 'ASC'},
          filter: {'>=DATE_CREATE': this.date.dateFrom, '<=DATE_CREATE': this.date.dateTo},
          select: ['ID', 'STATUS_ID', 'SOURCE_ID']
        }).then((data) => {
          data.forEach(row => this.leads.push(row))
          this.$forceUpdate()
        })
      }
    },
    computed: {
      filteredInfo: function () {
        if (!this.info) return []

        this.getLeads()
        const baseDates = [this.date.dateFrom, this.date.dateTo].map(value => Date.parse(value) / 86400000)
        return this.info.map((row) => {
          let dates = [row.ACTIVE_FROM, row.ACTIVE_TO].map(value => Date.parse(value.split(' ')[0].split('.').reverse().join('-')) / 86400000)
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
//        for (let key in this.list) {
//          let value = this.list[key]
//          let data = {}
//          data['sourceId'] = key
//          data['name'] = value
//          data['price'] = this.calculate(key)
//          Object.keys(this.counts).forEach((value) => {
//            data[value] = this[value].filter(row => row.SOURCE_ID === key || row.UF_CRM_1512969036 === key).length
//            data[value + 'Percent'] = Math.round(data[value] * 100 / this.counts[value])
//          })
//          data['priceDeal'] = data['leadDeals'] ? Math.round(data['price'] / data['leadDeals']) : data['price'] ? '***' : 0
//          result.push(data)
//        }
        this.leadStatusList.forEach(status => {
          let data = {}
          data['sourceId'] = status.STATUS_ID
          data['name'] = status.NAME
          data['price'] = 0
          for (let key in this.list) {
            if (this.list[key] === status.NAME) {
              data['price'] = this.calculate(key)
            }
          }
          Object.keys(this.counts).forEach((value) => {
            data[value] = this[value].filter(row => row.SOURCE_ID === data['sourceId'] || (
              row.UF_CRM_1512969036 && this.dealStatusList.filter(status => status.ID === row.UF_CRM_1512969036)[0]['VALUE'] === data['name']
            )).length
            data[value + 'Percent'] = Math.round(data[value] * 100 / this.counts[value])
          })
          data['priceDeal'] = data['leadDeals'] ? Math.round(data['price'] / data['leadDeals']) : data['price'] ? '***' : ''
          result.push(data)
        })
        return result
      },
      leadCanceled: function () {
        return this.leads.filter(row => row.STATUS_ID === 'JUNK')
      },
      leadDeals: function () {
        return this.leads.filter(row => row.STAGE_ID || (row.STATUS_ID === 'CONVERTED' && this.inDeals.indexOf(row.ID) !== -1))
      },
      counts: function () {
        if (this.leadDeals.length && this.date.interval === 'month') {
          let params = {
            IBLOCK_TYPE_ID: 'lists',
            IBLOCK_ID: '80',
            ELEMENT_CODE: this.date.dateFrom,
            FIELDS: {
              NAME: this.date.dateFrom,
              PROPERTY_394: Math.round(this.calculate(false) / this.leadDeals.length)
            }
          }
          BX.get('lists.element.add', params).catch(_ => BX.get('lists.element.update', params))
        }

        return {
          leads: this.leads.length,
          leadDeals: this.leadDeals.length,
          leadCanceled: this.leadCanceled.length
        }
      }
    },
    created () {
      BX.get('lists.field.get', {
        IBLOCK_TYPE_ID: 'lists',
        IBLOCK_ID: '30',
        FIELD_ID: 'PROPERTY_112'
      }).then((data) => {
        this.list = data[0].L.DISPLAY_VALUES_FORM
        this.list[null] = '*** без источника'
      })
      BX.get('crm.deal.userfield.list', {filter: {FIELD_NAME:'UF_CRM_1512969036'}}).then((data) => {
        this.dealStatusList = data[0].LIST
      })

      BX.get('crm.status.list', {
        filter: {'ENTITY_ID': 'SOURCE'},
        select: ['STATUS_ID', 'NAME']
      }).then((data) => {
        this.leadStatusList = data
        this.leadStatusList.push({
          NAME: '*** без источника',
          STATUS_ID: null
        })
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
</style>
