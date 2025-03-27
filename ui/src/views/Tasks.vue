<template>
    <div class="columns is-multiline">
        <div class="column">
            <form v-on:submit="searchSubmited">
                <div class="field">
                    <div class="columns">
                        <div class="column">
                            <label class="label has-text-weight-normal">Номер задачи</label>
                            <div class="control">
                                <input class="input" v-model="number" type="number" name="number">
                            </div>
                        </div>
                        <div class="column">
                            <label class="label has-text-weight-normal">Дата создания</label>
                            <div class="control">
                                <input class="input" v-model="date" type="date" name="date">
                            </div>
                        </div>
                        <div class="column">
                            <label class="label has-text-weight-normal">Диапазон номеров (от)</label>
                            <div class="control">
                                <input class="input" v-model="number_from" type="number" name="number_from" placeholder="От">
                            </div>
                        </div>
                        <div class="column">
                            <label class="label has-text-weight-normal">(до)</label>
                            <div class="control">
                                <input class="input" v-model="number_to" type="number" name="number_to" placeholder="До">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column"><button class="button is-success is-light is-fullwidth">Поиск</button></div>
                    <div class="column"><a href="#" v-on:click="resetFilter" class="button is-danger is-light is-fullwidth">Сбросить фильтр</a></div>
                </div>
            </form>
        </div>
        <div class="column is-full">
            <div class="columns is-multiline">
                <div class="column is-half" v-for="task in tasks.slice(0, offset)" v-bind:key="task.id">
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
                                <p v-if="task.type == 'certificate' && task.source">
                                    Источник запроса: <span class="has-text-danger">{{ task.source }}</span>
                                </p>
                                <p v-if="task.type == 'certificate' && task.course">
                                    Направление: <span class="has-text-danger">{{ task.course.slice(0, 50) + '...' }}</span>
                                </p>
                            </div>
                        </div>
                        <footer class="card-footer">
                            <router-link :to="{name: 'Task', params: {id: task.id}}" class="card-footer-item">Подробнее</router-link>
                            <a :href="'/' + task.pdf_path" v-if="task.is_completed == 1" class="card-footer-item has-text-success">Скачать</a>
                            <a href="#" v-on:click="remove(task.id)" class="card-footer-item has-text-danger">Удалить</a>
                        </footer>
                    </div>
                </div>
                <div class="column"><button v-if="offset <= tasks.length" v-on:click="offset += 10" class="button is-success is-light is-fullwidth">Показать ещё</button></div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Task',
    data() {
        return {
            tasks: [],
            err: null,
            offset: 10,
            number: null,
            date: null,
            number_from: null,
            number_to: null,
            updater: null,
        }
    },
    methods: {
        resetFilter: function() {
            this.number = null
            this.number_from = null
            this.number_to = null
            this.date = null

            fetch('/api/tasks', {
                method: 'GET',
            }).then(r => {
                if (r.status != 200)
                    this.err = 'Невозможно выполнить запрос'

                return r.json()
            }).then(r => {
                if (this.$route.query.tab) {
                    let tab = this.$route.query.tab
                    r = r.filter((e) => {
                        return e.type == tab
                    })
                }

                this.tasks = r

                if (this.updater)
                    clearInterval(this.updater)

                this.updater = setInterval(() => {
                    fetch('/api/tasks', {
                        method: 'GET',
                    }).then(r => {
                        if (r.status != 200)
                            this.err = 'Невозможно выполнить запрос'

                        return r.json()
                    }).then(r => {
                        if (this.$route.query.tab) {
                            let tab = this.$route.query.tab
                            r = r.filter((e) => {
                                return e.type == tab
                            })
                        }

                        this.tasks = r
                    }).catch(r => {
                        console.log(r)
                    })
                }, 3000)
            }).catch(r => {
                console.log(r)
            })
        },
        searchSubmited: function(e) {
            e.preventDefault()

            let form = new FormData()
            if (this.number) form.set('number', this.number)
            if (this.number_from) form.set('number_from', this.number_from)
            if (this.number_to) form.set('number_to', this.number_to)
            if (this.date) form.set('date', this.date)

            this.offset = 10

            fetch('/api/tasks', {
                method: 'POST',
                body: form,
            }).then(r => {
                if (r.status != 200) {
                    alert('Невозможно выполнить запрос')
                    return
                }

                return r.json()
            }).then(r => {
                this.tasks = r

                if (this.updater)
                    clearInterval(this.updater)

                this.updater = setInterval(() => {
                    fetch('/api/tasks', {
                        method: 'POST',
                        body: form,
                    }).then(r => {
                        if (r.status != 200)
                            this.err = 'Невозможно выполнить запрос'

                        return r.json()
                    }).then(r => {
                        if (this.$route.query.tab) {
                            let tab = this.$route.query.tab
                            r = r.filter((e) => {
                                return e.type == tab
                            })
                        }

                        this.tasks = r
                    }).catch(r => {
                        console.log(r)
                    })
                }, 3000)
            }).catch(r => {
                console.log(r)
            })
        }, remove: function(id) {
            if (confirm('Вы действительно хотите удалить задачу №' + id + '?')) {
                fetch('/api/tasks/' + id + '/remove', {
                    method: 'GET',
                }).then(r => {
                    if (r.status != 200)
                        this.err = 'Невозможно выполнить запрос'

                    return r.json()
                }).then(r => {
                    if (r != false) {
                        fetch('/api/tasks', {
                            method: 'GET',
                        }).then(r => {
                            if (r.status != 200)
                                this.err = 'Невозможно выполнить запрос'
                            return r.json()
                        }).then(r => {
                            this.tasks = r
                        })
                    } else {
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
        fetch('/api/tasks', {
            method: 'GET',
        }).then(r => {
            if (r.status != 200)
                this.err = 'Невозможно выполнить запрос'

            return r.json()
        }).then(r => {
            if (this.$route.query.tab) {
                let tab = this.$route.query.tab
                r = r.filter((e) => {
                    return e.type == tab
                })
            }

            this.tasks = r
            console.log(r.length)

            let activeTasks = this.tasks.filter((e) => {
                if (e.is_completed == 0) return e
            })

            if (activeTasks.length > 0) {
                this.updater = setInterval(() => {
                    fetch('/api/tasks', {
                        method: 'GET',
                    }).then(r => {
                        if (r.status != 200)
                            this.err = 'Невозможно выполнить запрос'

                        return r.json()
                    }).then(r => {
                        if (this.$route.query.tab) {
                            let tab = this.$route.query.tab
                            r = r.filter((e) => {
                                return e.type == tab
                            })
                        }

                        this.tasks = r
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