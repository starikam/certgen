import Vue from 'vue'
import App from './App.vue'
import router from './router'

Vue.config.productionTip = false

new Vue({
  router,
  render: h => h(App),
  data() {
    return {
      meta: null,
      appType: '',
    }
  },
  methods: {
    refreshMeta: function() {
      fetch('/api/meta', {
          method: 'GET',
      }).then(r => {
          if (r.status != 200)
              alert('Невозможно получить мета-ифнормацию')

          return r.json()
      }).then(r => {
          this.meta = r
      }).catch(r => {
          console.log(r)
      })
    }
  },
  mounted() {
    this.refreshMeta()
  }
}).$mount('#app')
