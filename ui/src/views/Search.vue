<template>
    <div class="columns is-multiline">
        <div class="column is-full">
            <form v-on:submit="searchSubmited">
                <div class="field">
                    <label class="label has-text-weight-normal">Номер сертификата</label>
                    <div class="control">
                        <input class="input" v-model="number" type="number" name="number">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">ФИО</label>
                    <div class="control">
                        <input class="input" v-model="name" type="text" name="name">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Направление обучения</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select name="course_id" v-model="course_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.courses" v-bind:key="row.id" :value="row.id">{{ row.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Домик</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select name="institution_id" v-model="institution_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.institutions" v-bind:key="row.id" :value="row.id">{{ row.name }} ({{ row.regname }})</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Источник запроса</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select name="source_id" v-model="source_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.sources" v-bind:key="row.id" :value="row.id">{{ row.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Дата выпуска сертификатов (указанная)</label>
                    <div class="control">
                        <input class="input" v-model="date" type="date" name="date">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Дата создания сертификата</label>
                    <div class="control">
                        <input class="input" v-model="created" type="date" name="created">
                    </div>
                </div>
                <button class="button is-success is-light is-fullwidth">Поиск</button>
            </form>
        </div>
        <div class="column is-full" v-if="certs">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <table>
                            <tr v-for="row in certs.slice(0, offset)" v-bind:key="row.id">
                                <td>{{ row.id }}</td>
                                <td>{{ row.fullname }}</td>
                                <td>{{ row.regname }}</td>
                                <td>{{ row.coursename }}</td>
                                <td>{{ row.instname }}</td>
                                <td>{{ row.source }}</td>
                                <td>
                                    <router-link :to="{
                                        name: 'Create', 
                                        query: {
                                            number: row.id, 
                                            name: row.fullname,
                                            course_id: row.course_id,
                                            inst_id: row.inst_id,
                                            gender: row.gender,
                                            hours: row.hours,
                                            date: row.date,
                                            template_id: row.template_id,
                                        }
                                    }" v-if="row.pdf_path" class="has-text-link">Редактировать</router-link>
                                </td>
                                <td>
                                    <a :href="'/' + row.pdf_path" v-if="row.pdf_path" class="has-text-success">Скачать</a>
                                </td>
                                <td>
                                    <a href="#" v-on:click="removeCert(row.id)" class="has-text-danger">Удалить</a>
                                </td>
                            </tr>
                        </table>
                        <div class="column"><button v-if="offset <= certs.length" v-on:click="offset += 10" class="button is-success is-light is-fullwidth">Показать ещё</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Search',
    data() {
        return {
            number: null,
            name: '',
            course_id: null,
            source_id: null,
            institution_id: null,
            date: null,
            created: null,
            certs: null,
            offset: 10,
        }
    },
    methods: {
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
                        location.reload(); 
                    }
                    else {
                        alert('Ошибка сервера')
                    }
    
                    console.log(r)
                }).catch(r => {
                    console.log(r)
                })
            }
        },
        searchSubmited: function(e) {
            e.preventDefault()

            let form = new FormData()
            if (this.number) form.set('number', this.number)
            if (this.name) form.set('name', this.name)
            if (this.course_id) form.set('course_id', this.course_id)
            if (this.institution_id) form.set('institution_id', this.institution_id)
            if (this.date) form.set('date', this.date)
            if (this.source_id) form.set('source_id', this.source_id)
            if (this.created) form.set('created', this.created)

            this.offset = 10

            fetch('/api/search', {
                method: 'POST',
                body: form,
            }).then(r => {
                if (r.status != 200) {
                    alert('Невозможно выполнить запрос')
                    return
                }

                return r.json()
            }).then(r => {
                this.certs = r
            }).catch(r => {
                console.log(r)
            })
        }
    }
}
</script>