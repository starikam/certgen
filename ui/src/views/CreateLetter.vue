<template>
    <div>
        <div class="columns">
        <div class="column">
            <h1 class="has-text-weight-semibold view-header">Выпуск единичного письма</h1>
            <form v-on:submit="importSubmited" v-if="!createdPdf">
                <div class="field" v-if="number">
                    <label class="label has-text-weight-normal">Номер письма</label>
                    <div class="control">
                        <input :readonly="number != null" class="input" v-model="number" type="text" name="number">
                    </div>
                    <p class="help is-danger">Оставьте пустым для автоматического заполнения</p>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">ФИО (Кому?)</label>
                    <div class="control">
                        <input required class="input" v-model="name" type="text" name="name">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Короткое обращение</label>
                    <div class="control">
                        <input required class="input" v-model="shortname" type="text" name="shortname">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Должность</label>
                    <div class="control">
                        <input required class="input" v-model="position" type="text" name="position">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Текст</label>
                    <div class="control">
                        <textarea class="textarea" v-model="text" name="text"></textarea>
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Пол</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select required name="gender" v-model="gender">
                                <option value="m">М</option>
                                <option value="f">Ж</option>
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
    <div class="columns" v-if="certs && name == ''">
        <div class="column">
            <h1 class="has-text-weight-semibold view-header">История</h1>
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <table>
                            <tr v-for="row in certs" v-bind:key="row.id">
                                <td>{{ row.id }}</td>
                                <td>{{ row.fullname }}</td>
                                <td>{{ row.created_at }}</td>
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
            text: '',
            position: '',
            shortname: '',
            gender: 'm',
            number: null,
            institution_id: 0,
            template_id: 0,
            hours: 32,
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

        if ('name' in this.$route.query) this.name = this.$route.query.name
        if ('text' in this.$route.query) this.text = this.$route.query.text
        if ('position' in this.$route.query) this.position = this.$route.query.position
        if ('shortname' in this.$route.query) this.shortname = this.$route.query.shortname
        if ('number' in this.$route.query) this.number = this.$route.query.number
        if ('gender' in this.$route.query) this.gender = this.$route.query.gender
        if ('template_id' in this.$route.query) this.template_id = this.$route.query.template_id

        fetch('/api/single-letter', {
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
            if (confirm('Вы действительно хотите удалить письмо №' + id + '?')) {
                fetch('/api/letter/' + id + '/remove', {
                    method: 'GET',
                }).then(r => {
                    if (r.status != 200)
                        this.err = 'Невозможно выполнить запрос'
    
                    return r.json()
                }).then(r => {
                    if (r != false) {
                        fetch('/api/single-letter', {
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
                fetch('/api/letter/' + this.number, {
                    method: 'GET',
                }).then(r => {
                    if (r.status != 200) {
                        this.err = 'Невозможно выполнить запрос'
                        return
                    }

                    return r.json()
                }).then(r => {
                    if (r && 'id' in r) {
                        this.err = 'Внимание! Вы хотите перевыпустить письмо с № ' + r['id'] + '. Подтвердитет свое действие повторным нажатием.'
                    }

                    this.id_check = true
                }).catch(r => {
                    console.log(r)
                })
            } else {
                let data = new FormData()
                data.set('template_id', parseInt(this.template_id))
                data.set('name', this.name)
                data.set('shortname', this.shortname)
                data.set('gender', this.gender)
                data.set('position', this.position)
                data.set('text', this.text)
                if (this.number)
                    data.set('number', this.number)

                fetch('/api/letter/create', {
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