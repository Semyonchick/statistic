<template>
    <div class="grid-x">
        <div class="large-7 cell">
            <div class="input-group">
                <label for="from" class="input-group-label">с</label>
                <input id="from" type="date" class="input-group-field" v-model="dateFrom" @change="interval='other'">
                <label for="to" class="input-group-label">до</label>
                <input id="to" type="date" class="input-group-field" v-model="dateTo" @change="interval='other'">
                <div class="input-group-button">
                    <button class="button" :disabled="interval=='other'" @click="changeInterval(-1)">предыдущий
                    </button>
                    <button class="button" :disabled="interval=='other'" @click="changeInterval(+1)">следующий
                    </button>
                </div>
            </div>
        </div>

        <div class="large-5 cell">
            <div class="radioList medium-text-center large-text-right">
                <template v-for="(value, key) in intervals">
                    <input :id="key" type="radio" v-model="interval" :value="key" name="interval"
                           @change="dateCorrect()">
                    <label :for="key">{{value}}</label>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
  export default {
    name: 'date-select',
    data () {
      return {
        interval: 'month',
        dateFrom: '',
        dateTo: '',
        intervals: {
          week: 'Неделя',
          month: 'Месяц',
          quarter: 'Квартал',
          year: 'Год',
          other: 'Другое'
        },
        intervalSize: {
          month: 1,
          quarter: 3,
          year: 12
        }
      }
    },
    methods: {
      changeInterval: function (i) {
        let parse = this.dateTo.split('-')
        let date = new Date(Date.UTC(parse[0], parse[1] - 1, parse[2]))

        if (this.intervalSize[this.interval]) {
          date.setDate(1)
          if (i) date.setMonth(date.getMonth() + i * this.intervalSize[this.interval])
        } else if (this.interval === 'week') {
          date.setDate(date.getDate() - 7 - date.getDay() + 1)
          if (i) date.setDate(date.getDate() + i * 7)
        } else {
          date = false
        }

        this.setInterval(date)
      },
      dateCorrect: function () {
        this.changeInterval(0)
      },
      setInterval: function (date) {
        if (!date) return

        if (this.intervalSize[this.interval]) {
          date.setMonth(Math.floor(date.getMonth() / this.intervalSize[this.interval]) * this.intervalSize[this.interval])
        }
        this.dateFrom = date.toISOString().split('T')[0]

        if (this.intervalSize[this.interval]) {
          date.setMonth(date.getMonth() + this.intervalSize[this.interval])
          date.setDate(0)
        } else if (this.interval === 'week') {
          date.setDate(date.getDate() + 6)
        }
        this.dateTo = date.toISOString().split('T')[0]
      }
    },
    created () {
      const d = new Date()
      this.setInterval(new Date(Date.UTC(d.getFullYear(), d.getMonth(), 1)))
    }
  }
</script>

<style scoped>
    .radioList {
        margin-top: 5px;
    }

    .radioList label {
        font-size: 14px;
    }
</style>
