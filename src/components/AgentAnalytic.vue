<template>
    <div>
        <date-select></date-select>

        <table v-if="date() && leadsList.length" width="100%" cellspacing="0" cellpadding="0">
            <thead>
            <tr>
                <th>Агенства</th>
                <th>В работе</th>
                <th>Провал</th>
                <th>Закрыта</th>
                <th>Всего</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="agent in agents" v-if="agent.leads">
                <th>{{agent.VALUE}}</th>
                <td class="success" v-if="data=agent.leads.filter(work)">
                    <a href="#" @click="goTo('work', agent)">{{data.length}}</a> <span class="opacity"
                                                                                       v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td class="bad" v-if="data=agent.leads.filter(lose)">
                    <a href="#" @click="goTo('fail', agent)">{{data.length}}</a> <span class="opacity"
                                                                                       v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td class="primary" v-if="data=agent.leads.filter(won)">
                    <a href="#" @click="goTo('success', agent)">{{data.length}}</a> <span class="opacity"
                                                                                          v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=agent.leads">
                    <a href="#" @click="goTo('all', agent)">{{data.length}}</a> <span class="opacity"
                                                                                      v-if="value=sum(data)">/ {{value}}</span>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>ИТОГО:</th>
                <td v-if="data=leads.filter(work)">
                    <a href="#" @click="goTo('work')">{{data.length}}</a> <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=leads.filter(lose)">
                    <a href="#" @click="goTo('fail')">{{data.length}}</a> <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=leads.filter(won)">
                    <a href="#" @click="goTo('success')">{{data.length}}</a> <span class="opacity"
                                                                                   v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=leads">
                    <a href="#" @click="goTo('all')">{{data.length}}</a> <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
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
    name: 'AgentAnalytic',
    data () {
      return {
        agents: '',
        leads: '',
        code: {
          source: 'UF_CRM_1512969036',
          sourceValue: 100,
          agent: 'UF_CRM_1514277034'
        }
      }
    },
    methods: {
      goTo (type, row) {
        let path = '/crm/deal/category/0/'
        let dates = '-from-' + this.$children[0].dateFrom.split('-').reverse().join('.') + '-to-' + this.$children[0].dateTo.split('-').reverse().join('.')
        let params = []
        params.push('UF_CRM_1512969036-is-100')

        if (type === 'work') {
          params.push('DATE_CREATE-to-' + this.$children[0].dateTo.split('-').reverse().join('.'))
          params.push('CLOSED-is-N')
        } else if (type === 'success') {
          params.push('CLOSEDATE' + dates)
          params.push('STAGE_ID-is-WON')
        } else if (type === 'fail') {
          params.push('CLOSEDATE' + dates)
          params.push('STAGE_ID-is-LOSE')
        } else {
          params.push('DATE_CREATE-to-' + this.$children[0].dateTo.split('-').reverse().join('.'))
          params.push('CLOSEDATE-from-' + this.$children[0].dateFrom.split('-').reverse().join('.'))
        }

        if (row) {
          params.push('UF_CRM_1514277034-is-' + row.ID)
        }

        let url = '//holding-gel.bitrix24.ru' + path + '#/f/' + params.join('/') + '/'

//        console.log(url)
        window.top.location.href = url
      },
      sum (list) {
        return list.length ? list.map(row => +row.OPPORTUNITY).reduce((a, b) => a + b) : false
      },
      date () {
        return this.$children[0]
      },
      lose (data) {
        return data.STAGE_ID === 'LOSE'
      },
      won (data) {
        return data.STAGE_ID === 'WON'
      },
      work (data) {
        return ['LOSE', 'WON'].indexOf(data.STAGE_ID) === -1
      }
    },
    computed: {
      leadsList () {
        this.agents.map(agent => {
          agent.leads = false
          return agent
        })
        this.getDeals.then((data) => {
          this.leads = data
          data.forEach(row => {
            this.agents.map(agent => {
              if (agent.ID === row[this.code.agent]) {
                if (!agent.leads) {
                  agent.leads = []
                }
                agent.leads.push(row)
              }
              return agent
            })
          })
          this.$forceUpdate()
        })
        return this.leads
      },
      getDeals () {
        let filter = {}
        let select = ['ID', 'STAGE_ID', 'OPPORTUNITY', 'SOURCE_ID', this.code.agent]
        filter[this.code.source] = this.code.sourceValue
        return BX.deals(this.$children[0].dateFrom, this.$children[0].dateTo, filter, select)
//        return new Promise(resolve => {
//          let filter1 = {'>=DATE_CREATE': this.$children[0].dateFrom, '<=DATE_CREATE': this.$children[0].dateTo}
//
//          let filter2 = {'>=CLOSEDATE': this.$children[0].dateFrom, '<=CLOSEDATE': this.$children[0].dateTo}
//          filter2[this.code.source] = this.code.sourceValue
//
//          let select = ['ID', 'STAGE_ID', 'OPPORTUNITY', 'SOURCE_ID', this.code.agent]
//          BX.batch([
//            ['crm.deal.list', {filter: filter1, select: select}],
//            ['crm.deal.list', {filter: filter2, select: select}]
//          ]).then(result => {
//            this.$forceUpdate()
//            resolve(result[0].concat(result[1]))
//          })
//        })
      }
    },
    created () {
      BX.get('crm.deal.userfield.list').then(data => {
        this.agents = data.filter(row => row.FIELD_NAME === this.code.agent)[0].LIST
        this.$forceUpdate()
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
