<script>
  /* eslint-disable */
  export default {
    cache: true,
    domain () {
      return BX24.getAuth().domain
    },
    get (method, params, next) {
      const cacheId = method + '-' + JSON.stringify([params, next])

      return new Promise((resolve, reject) => {
        if (this.cache && sessionStorage.getItem(cacheId)) {
          resolve(JSON.parse(sessionStorage.getItem(cacheId)))
        } else {
          let data = []
          BX24.callMethod(method, params, (request) => {
            if (request.error()) {
              reject(request.error())
            } else {
              data = data.concat(request.answer.result)
              if (request.answer.next && (!next || next(data))) request.next()
              else {
                if (this.cache && method.match(/\.(get|list)$/)) {
                  sessionStorage.setItem(cacheId, JSON.stringify(data))
                }
                resolve(data)
              }
            }
          })
        }
      })
    },
    batch (methods) {
      return new Promise((resolve) => {
        BX24.callBatch(methods, (request) => {
          resolve(request.map(row => row.answer.result).filter(row => row))
        })
      })
    },
    deals (from, to, filter, select) {
      return new Promise((resolve, reject) => {
        let id = []
        let result = {}
        let status = []
        this.get('crm.deal.list', {
          filter: Object.assign({
            '<=DATE_CREATE': to,
            '>=CLOSEDATE': from
          }, filter),
          select: select
        }).then(data => {
          data.forEach(row => {
            result[row.ID] = row
          })
          status.push(1)
          if(status.length === 2) resolve(Object.values(result))
        })
        this.get('crm.deal.list', {
          filter: Object.assign({
            '<=DATE_CREATE': to,
            'CLOSED': 'N'
          }, filter),
          select: select
        }).then(data => {
          data.forEach(row => {
            result[row.ID] = row
          })
          status.push(1)
          if(status.length === 2) resolve(Object.values(result))
        })
      })
    }
  }
</script>