import Vue from 'vue'
import VueRouter from 'vue-router'
import Import from '../views/Import.vue'
import Reissuance from '../views/Reissuance.vue'
import Main from '../views/Main.vue'
import Task from '../views/Task.vue'
import Tasks from '../views/Tasks.vue'
import Courses from '../views/Courses.vue'
import Regions from '../views/Regions.vue'
import Homes from '../views/Homes.vue'
import Search from '../views/Search.vue'
import SearchLetter from '../views/SearchLetter.vue'
import Templates from '../views/Templates.vue'
import Create from '../views/Create.vue'
import CreateLetter from '../views/CreateLetter.vue'
import Sources from '../views/Sources.vue'
import Reports from '../views/Reports.vue'
import ImportLetter from '../views/ImportLetter.vue'

Vue.use(VueRouter)

const routes = [
  {
    path: '/',
    name: 'Main',
    component: Main
  },
  {
    path: '/tasks/:id',
    name: 'Task',
    component: Task
  },
  {
    path: '/search',
    name: 'Search',
    component: Search
  },
  {
    path: '/search-letter',
    name: 'SearchLetter',
    component: SearchLetter
  },
  {
    path: '/tasks',
    name: 'Tasks',
    component: Tasks
  },
  {
    path: '/regions',
    name: 'Regions',
    component: Regions
  },
  {
    path: '/sources',
    name: 'Sources',
    component: Sources
  },
  {
    path: '/homes',
    name: 'Homes',
    component: Homes
  },
  {
    path: '/courses',
    name: 'Courses',
    component: Courses
  },
  {
    path: '/templates',
    name: 'Templates',
    component: Templates
  },
  {
    path: '/import',
    name: 'Import',
    component: Import
  },
  {
    path: '/reissuance/:id',
    name: 'Reissuance',
    component: Reissuance
  },
  {
    path: '/import-letter',
    name: 'ImportLetter',
    component: ImportLetter,
  },
  {
    path: '/create',
    name: 'Create',
    component: Create
  },
  {
    path: '/create-letter',
    name: 'CreateLetter',
    component: CreateLetter
  },
  {
    path: '/reports',
    name: 'Reports',
    component: Reports
  },
]

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
})

export default router
