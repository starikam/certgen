<template>
    <div>
        <div class="columns">
        <div class="column">
            <h1 class="has-text-weight-semibold view-header">Выпуск единичного сертификата</h1>
            <form v-on:submit="importSubmited" v-if="!createdPdf">
                <div class="field">
                    <label class="label has-text-weight-normal">Дата выпуска сертификатов</label>
                    <div class="control">
                        <input required class="input" v-model="date" type="date" name="date">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Номер сертификата</label>
                    <div class="control">
                        <input class="input" v-model="number" type="text" name="number">
                    </div>
                    <p class="help is-danger">Оставьте пустым для автоматического заполнения</p>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">ФИО</label>
                    <div class="control">
                        <input required class="input" v-model="name" type="text" name="name">
                    </div>
                </div>
                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label has-text-weight-normal">Пол</label>
                            <div class="control">
                                <div class="select select is-fullwidth">
                                    <select name="gender" v-model="gender">
                                        <option value="m">М</option>
                                        <option value="f">Ж</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label has-text-weight-normal">Пол (строка)</label>
                            <div class="control">
                                <input class="input" type="text" name="gender_str" v-model="gender_str">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Направление обучения</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select required name="course_id" v-model="course_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.courses" v-bind:key="row.id" :value="row.id">{{ row.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label has-text-weight-normal">Количество академических часов</label>
                            <div class="control">
                                <input required class="input" v-model="hours" type="text" name="hours" value="32">
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label has-text-weight-normal">Количество академических часов (строка)</label>
                            <div class="control">
                                <input class="input" v-model="hours_str" type="text" name="hours_str">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Учреждение</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select required name="institution_id" v-model="institution_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.institutions" v-bind:key="row.id" :value="row.id">{{ row.name }} ({{ row.regname }})</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Шаблон</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select required name="template_id" v-model="template_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.templates" v-bind:key="row.id" :value="row.id">{{ row.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="notification is-danger" v-if="err">{{ err }}</div>
                <br>
                <div class="field" v-if="!id_check">
                    <button v-if="!recreate" class="button is-success is-light is-fullwidth">Выпустить</button>
                    <button v-else class="button is-success is-light is-fullwidth">Перевыпустить</button>
                </div>
                <div class="field" v-if="id_check">
                    <button class="button is-success is-light is-fullwidth">Я подтверждаю действие</button>
                </div>
            </form>
        </div>
    </div>
    <div class="columns" v-if="certs">
        <div class="column">
            <h1 class="has-text-weight-semibold view-header">История</h1>
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <table>
                            <tr v-for="row in certs" v-bind:key="row.id">
                                <td>{{ row.id }}</td>
                                <td>{{ row.fullname }}</td>
                                <td>{{ row.coursename }}</td>
                                <td>{{ row.instname }}</td>
                                <td>
                                    <a :href="'/' + row.pdf_path" v-if="row.pdf_path" class="has-text-success">Скачать</a>
                                </td>
                                <td>
                                    <a href="#" v-on:click="removeCert(row.id)" class="has-text-danger">Удалить</a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

<script>

export default {
    name: 'Create',
    data() {
        return {
            course_id: 0,
            name: '',
            gender: 'm',
            gender_str: null,
            number: null,
            institution_id: 0,
            template_id: 0,
            hours: 32,
            hours_str: null,
            date: new Date().toISOString().slice(0,10),
            csvfile: null,
            err: null,
            recreate: false,
            id_check: false,
            createdPdf: null,
            certs: null
        }
    },
    mounted() {
        if (Object.keys(this.$route.query).length > 0) this.recreate = true

        if ('course_id' in this.$route.query) this.course_id = this.$route.query.course_id
        if ('name' in this.$route.query) this.name = this.$route.query.name
        if ('hours' in this.$route.query) this.hours = this.$route.query.hours
        if ('hours_str' in this.$route.query) this.hours_str = this.$route.query.hours_str
        if ('number' in this.$route.query) this.number = this.$route.query.number
        if ('gender' in this.$route.query) this.gender = this.$route.query.gender
        if ('gender_str' in this.$route.query) this.gender_str = this.$route.query.gender_str
        if ('inst_id' in this.$route.query) this.institution_id = this.$route.query.inst_id
        if ('template_id' in this.$route.query) this.template_id = this.$route.query.template_id
        if ('date' in this.$route.query) this.date = this.$route.query.date

        fetch('/api/single-certs', {
            method: 'GET',
        }).then(r => {
            if (r.status != 200)
                this.err = 'Невозможно выполнить запрос'

            return r.json()
        }).then(r => {
            this.certs = r
        }).catch(r => {
            console.log(r)
        })
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
                        fetch('/api/single-certs', {
                            method: 'GET',
                        }).then(r => {
                            if (r.status != 200)
                                this.err = 'Невозможно выполнить запрос'
    
                            return r.json()
                        }).then(r => {
                            this.certs = r
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
        },
        onFileChange: function (e) {
            let files = e.target.files || e.dataTransfer.files
            if (!files.length) return

            this.csvfile = files[0]

            this.fileErr = null
            if (['text/csv', 'text/plain'].indexOf(this.csvfile.type) == -1)
                this.err = 'Недопустимый формат файла'
        },
        importSubmited: function (e) {
            e.preventDefault()

            if (!this.id_check && this.number) {
                fetch('/api/cert/' + this.number, {
                    method: 'GET',
                }).then(r => {
                    if (r.status != 200) {
                        this.err = 'Невозможно выполнить запрос'
                        return
                    }

                    return r.json()
                }).then(r => {
                    if (r && 'id' in r) {
                        this.err = 'Внимание! Вы хотите перевыпустить сертификат с № ' + r['id'] + '. Подтвердитет свое действие повторным нажатием.'
                    }

                    this.id_check = true
                }).catch(r => {
                    console.log(r)
                })
            } else {
                let data = new FormData()
                data.set('course_id', parseInt(this.course_id))
                data.set('institution_id', parseInt(this.institution_id))
                data.set('template_id', parseInt(this.template_id))
                data.set('name', this.name)
                data.set('gender', this.gender)
                data.set('gender_str', this.gender_str)
                data.set('hours', this.hours.replace(',', '.'))
                data.set('hours_str', this.hours_str)
                data.set('date', this.date)
                if (this.number)
                    data.set('number', this.number)

                fetch('/api/cert/create', {
                    method: 'POST',
                    body: data,
                }).then(r => {
                    if (r.status != 200) {
                        this.err = 'Невозможно выполнить запрос'
                        return
                    }

                    return r.json()
                }).then(r => {
                    this.err = false
                    if ('cert_id' in r && 'pdf' in r) {
                        this.createdPdf = r.pdf

                        window.open(r.pdf, '_blank')
                        this.$router.push({name: 'Main', params: {id: r.task_id, }, })
                    }
                }).catch(r => {
                    console.log(r)
                })
            }
        }
    }
}
</script>