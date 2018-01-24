import Vue from 'vue'
import Router from 'vue-router'
import LeadAnalytic from '@/components/LeadAnalytic'
import ManagerAnalytic from '@/components/ManagerAnalytic'

Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/leads',
      name: 'LeadAnalytic',
      component: LeadAnalytic
    },
    {
      path: '/manager',
      name: 'ManagerAnalytic',
      component: ManagerAnalytic
    }
  ]
})
