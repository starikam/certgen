<template>
    <div class="columns is-multiline" v-if="task">
        <div class="column is-full">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <p>
                            <span class="has-text-weight-semibold"><i class="fas fa-medal" v-if="task.type == 'letter'"></i> Задача #{{ task.id }}</span>
                            <span> от {{ task.created_at }}</span>
                            <span class="has-text-warning" v-if="task.is_completed == 0 && task.total_completed > 0"> (выполняется)</span>
                            <span class="has-text-info" v-else-if="task.is_completed == 0"> (ожидает запуска)</span>
                            <span class="has-text-success" v-else> (выполнена)</span>
                        </p>
                        <p>
                            Готовность: <span class="has-text-success">{{ task.total }}</span> / <span class="has-text-link">{{ task.total_completed }}</span> ({{ Math.round((task.total_completed / task.total) * 100) }}%)
                        </p>
                        <p v-if="task.type == 'certificate'">
                            Дата выпуска: <span class="has-text-info">{{ task.issuance_date }}</span>
                        </p>
                        <p v-if="task.type == 'certificate'">
                            Диапазон номеров: <span class="has-text-danger">{{ task.first_id }} - {{ task.last_id }}</span>
                        </p>
                    </div>
                </div>
                <footer class="card-footer">
                    <a :href="'/' + task.pdf_path" v-if="task.is_completed == 1" class="card-footer-item has-text-success">Скачать</a>
                    <router-link :to="{name: 'Reissuance', id: task.id, }" v-if="task.is_completed == 1" class="card-footer-item has-text-info">Перевыпуск</router-link>
                    <a href="#" v-on:click="remove(task.id)" v-if="task.is_completed == 1" class="card-footer-item has-text-danger">Удалить</a>
                </footer>
            </div>
        </div>
        <div class="column is-full">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <table>
                            <tr v-for="row in task.certs.slice(0, offset)" v-bind:key="row.id">
                                <td>{{ row.id }}</td>
                                <td>{{ row.fullname }}</td>
                                <td v-if="row.position">{{ row.position }}</td>
                                <td>
                                    <a :href="'/' + row.pdf_path" v-if="row.pdf_path" class="has-text-success">Скачать</a>
                                </td>
                                <td>
                                    <a href="#" v-on:click="removeCert(row.id)" class="has-text-danger">Удалить</a>
                                </td>
                            </tr>
                        </table>
                        <div class="column"><button v-if="offset <= task.certs.length" v-on:click="offset += 50" class="button is-success is-light is-fullwidth">Показать ещё</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Task',
    data() {
        return {
            task: null,
            taskUpdater: null,
            offset: 50,
        }
    },
    methods: {
        remove: function(id) {
            // return
            fetch('/api/tasks/' + id + '/remove', {
                method: 'GET',
            }).then(r => {
                if (r.status != 200)
                    this.err = 'Невозможно выполнить запрос'

                return r.json()
            }).then(r => {
                if (r != false)
                    this.$router.push('/')
                else
                    alert('Ошибка сервера')

                console.log(r)
            }).catch(r => {
                console.log(r)
            })
        },
        removeCert: function(id) {
            if (confirm('Вы действительно хотите удалить сертификат №' + id + '?')) {
                fetch('/api/cert/' + id + '/remove', {
                    method: 'GET',
                }).then(r => {
                    if (r.status != 200)
                        this.err = 'Невозможно выполнить запрос'
    
                    return r.json()
                }).then(r => {
                    if (r != false) {
                        fetch('/api/tasks/' + this.$route.params.id, {
                            method: 'GET',
                        }).then(r => {
                            if (r.status != 200)
                                this.err = 'Невозможно выполнить запрос'
    
                            return r.json()
                        }).then(r => {
                            this.task = r
    
                            if (this.task.is_completed == 1)
                                clearInterval(this.taskUpdater)
                        }).catch(r => {
                            console.log(r)
                        })
                    }
                    else {
                        alert('Ошибка сервера')
                    }
    
                    console.log(r)
                }).catch(r => {
                    console.log(r)
                })
            }
        }
    },
    created() {
        fetch('/api/tasks/' + this.$route.params.id, {
            method: 'GET',
        }).then(r => {
            if (r.status != 200)
                this.err = 'Невозможно выполнить запрос'

            return r.json()
        }).then(r => {
            this.task = r

            if (this.task.is_completed == 0 && !this.taskUpdater) {
                this.taskUpdater = setInterval(() => {
                    fetch('/api/tasks/' + this.$route.params.id, {
                        method: 'GET',
                    }).then(r => {
                        if (r.status != 200)
                            this.err = 'Невозможно выполнить запрос'

                        return r.json()
                    }).then(r => {
                        this.task = r

                        if (this.task.is_completed == 1)
                            clearInterval(this.taskUpdater)
                    }).catch(r => {
                        console.log(r)
                    })
                }, 3000)
            }
        }).catch(r => {
            console.log(r)
        })
    }
}
</script>