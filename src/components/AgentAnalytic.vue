<template>
    <div>
        <date-select></date-select>

        <table v-if="date()" width="100%" cellspacing="0" cellpadding="0">
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
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td class="bad" v-if="data=agent.leads.filter(lose)">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td class="primary" v-if="data=agent.leads.filter(won)">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=agent.leads">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>ИТОГО:</th>
                <td v-if="data=leads.filter(work)">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=leads.filter(lose)">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=leads.filter(won)">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
                </td>
                <td v-if="data=leads">
                    {{data.length}} <span class="opacity" v-if="value=sum(data)">/ {{value}}</span>
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
          sourceValue: '100',
          agent: 'UF_CRM_1514277034'
        }
      }
    },
    methods: {
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
      getLeads () {
        return new Promise(resolve => {
          let filter1 = {'>=DATE_CREATE': this.date().dateFrom, '<=DATE_CREATE': this.date().dateTo}
          filter1[this.code.source] = this.code.sourceValue

          let filter2 = {'>=CLOSEDATE': this.date().dateFrom, '<=CLOSEDATE': this.date().dateTo}
          filter2[this.code.source] = this.code.sourceValue

          let select = ['ID', 'STAGE_ID', 'OPPORTUNITY', 'SOURCE_ID', this.code.agent]
          BX.batch([
            ['crm.deal.list', {filter: filter1, select: select}],
            ['crm.deal.list', {filter: filter2, select: select}]
          ]).then(result => {
            resolve(result[0].concat(result[1]))
          })
        })
      }
    },
    created () {
      BX.get('crm.deal.userfield.list').then(data => {
        this.agents = data.filter(row => row.FIELD_NAME === this.code.agent)[0].LIST
        this.getLeads.then((data) => {
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
          console.log(data)
        })
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
