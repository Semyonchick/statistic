import Vue from 'vue'
import Router from 'vue-router'
import LeadAnalytic from '@/components/LeadAnalytic'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'LeadAnalytic',
      component: LeadAnalytic
    }
  ]
})
