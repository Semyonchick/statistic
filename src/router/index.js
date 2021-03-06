import Vue from 'vue'
import Router from 'vue-router'
import LeadAnalytic from '@/components/LeadAnalytic'
import ManagerAnalytic from '@/components/ManagerAnalytic'
import ManagerAgentAnalytic from '@/components/ManagerAgentAnalytic'
import AgentAnalytic from '@/components/AgentAnalytic'

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
    },
    {
      path: '/agentManager',
      name: 'ManagerAgentAnalytic',
      component: ManagerAgentAnalytic
    },
    {
      path: '/agents',
      name: 'AgentAnalytic',
      component: AgentAnalytic
    }
  ]
})
