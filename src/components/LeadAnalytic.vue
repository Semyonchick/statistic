<template>
    <div>
        <date-select></date-select>

        <table v-if="getLeads && date" width="100%" cellspacing="0" cellpadding="0">
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
                <td class="primary"><a href="#" @click="goTo('all', row)">{{row.leads || '-'}}</a>
                    <span class="opacity" v-if="row.leadsPercent">/ {{row.leadsPercent}}%</span>
                </td>
                <td class="success"><a href="#" @click="goTo('success', row)">{{row.leadDeals || '-'}}</a>
                    <span class="opacity" v-if="row.leadDealsPercent">/ {{row.leadDealsPercent}}%</span>
                </td>
                <td class="bad"><a href="#" @click="goTo('bad', row)">{{row.leadCanceled || '-'}}</a>
                    <span class="opacity" v-if="row.leadCanceledPercent">/ {{row.leadCanceledPercent}}%</span></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>ИТОГО:</th>
                <td>{{calculate(false)}}</td>
                <td>{{counts.leadDeals ? Math.round(calculate(false) / counts.leadDeals) : '***'}}</td>
                <td class="primary"><a href="#" @click="goTo('all')">{{counts.leads}}</a></td>
                <td class="success"><a href="#" @click="goTo('success')">{{counts.leadDeals}}</a></td>
                <td class="bad"><a href="#" @click="goTo('bad')">{{counts.leadCanceled}}</a></td>
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
        wait: 0,
        date: '',
        list: {},
        info: [],
        leads: [],
        deals: [],
        inDeals: [],
        users: [],
        dealStatusList: [],
        leadStatusList: []
      }
    },
    methods: {
      goTo (type, row) {
        let dates = '-from-' + this.date.dateFrom.split('-').reverse().join('.') + '-to-' + this.date.dateTo.split('-').reverse().join('.')
        let params = []
        let path
        if (type === 'success') {
          path = '/crm/deal/category/0/'
        } else {
          path = '/crm/lead/list/'
        }

        params.push('DATE_CREATE' + dates)
        if (type === 'success') {
          let deals = this.dealStatusList
          if (row) {
            deals = deals.filter(value => value.VALUE === row.name)
          }
          params.push('UF_CRM_1512969036-is-' + deals.map(value => +value.ID).filter(value => value !== 100).join('-or-'))
        } else {
          if (type === 'bad') {
            params.push('STATUS_ID-is-JUNK')
          }
          if (row) {
            params.push('SOURCE_ID-is-' + row.sourceId)
          }
        }

        let url = '//holding-gel.bitrix24.ru' + path + '#/f/' + params.join('/') + '/'

        window.top.location.href = url
      },
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
      }
    },
    computed: {
      filteredInfo: function () {
        if (!this.info) return []

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
      getLeads: function () {
        this.leads = []
        BX.batch([
          ['crm.deal.list', {
            filter: {
              '>=DATE_CREATE': this.date.dateFrom,
              '<=DATE_CREATE': this.date.dateTo,
              CATEGORY_ID: 0,
              '!UF_CRM_1512969036': 100
            },
            select: ['ID', 'LEAD_ID', 'STAGE_ID', 'UF_CRM_1512969036', 'DATE_CREATE']
          }],
          ['crm.lead.list', {
            order: {'ID': 'ASC'},
            filter: {
              '>=DATE_CREATE': this.date.dateFrom,
              '<=DATE_CREATE': this.date.dateTo,
              ASSIGNED_BY_ID: this.users.map(user => user.ID)
            },
            select: ['ID', 'STATUS_ID', 'SOURCE_ID']
          }]
        ]).then(data => {
          if (data[0].length || data[1].length) {
            this.leads = []

            this.deals = data[0].filter(row => !row.LEAD_ID)
            this.deals.forEach(row => this.leads.push(row))
            this.inDeals = data[0].map(row => row.LEAD_ID)

            data[1].forEach(row => this.leads.push(row))

            this.$forceUpdate()
          }
        })
        return true
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
              row.UF_CRM_1512969036 && this.dealStatusList.filter(status => status.ID === row.UF_CRM_1512969036).length && this.dealStatusList.filter(status => status.ID === row.UF_CRM_1512969036)[0]['VALUE'] === data['name']
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
      BX.get('crm.deal.userfield.list', {filter: {FIELD_NAME: 'UF_CRM_1512969036'}}).then((data) => {
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

      BX.get('user.get', {
        FILTER: {UF_DEPARTMENT: 114}
      }).then(data => {
        this.users = data
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
