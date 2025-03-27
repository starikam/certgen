<template>
    <div class="columns">
        <div class="column" v-if="source_id">
            <h1 class="has-text-weight-semibold view-header">Перевыпуск сертификатов</h1>
            <form v-on:submit="importSubmited">
                <div class="field">
                    <label class="label has-text-weight-normal">Дата выпуска сертификатов</label>
                    <div class="control">
                        <input required class="input" v-model="date" type="date" name="date">
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
                <div class="field">
                    <label class="label has-text-weight-normal">Количество академических часов</label>
                    <div class="control">
                        <input required class="input" v-model="hours" type="text" name="hours" value="32">
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
                <div class="field">
                    <label class="label has-text-weight-normal">Источник запроса</label>
                    <div class="control">
                        <div class="select select is-fullwidth">
                            <select required name="source_id" v-model="source_id">
                                <option value="">-</option>
                                <option v-for="row in $root.meta.sources" v-bind:key="row.id" :value="row.id">{{ row.name }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="notification is-danger" v-if="err">{{ err }}</div>
                <br>
                <div class="field">
                    <button v-if="!err" class="button is-success is-light is-fullwidth">Перевыпуск</button>
                    <button v-else disabled class="button is-success is-light is-fullwidth">Перевыпуск</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>

export default {
    name: 'Import',
    data() {
        return {
            source_id: 0,
            course_id: 0,
            institution_id: 0,
            template_id: 0,
            hours: '32',
            date: new Date().toISOString().slice(0,10),
            csvfile: null,
            filenames: [],
            err: null,
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
            if (r && 'certs' in r && r['certs']) {
                this.source_id = r['certs'][0]['source_id']
                this.course_id = r['certs'][0]['course_id']
                this.institution_id = r['certs'][0]['institution_id']
                this.template_id = r['certs'][0]['template_id']
                this.hours = r['certs'][0]['hours']
                this.date = r['certs'][0]['issuance_date']
            }
        }).catch(r => {
            console.log(r)
        })
    },
    methods: {
        importSubmited: function (e) {
            e.preventDefault()

            let data = new FormData()
            data.set('course_id', parseInt(this.course_id))
            data.set('institution_id', parseInt(this.institution_id))
            data.set('template_id', parseInt(this.template_id))
            data.set('source_id', parseInt(this.source_id))
            data.set('hours', this.hours.replace(',', '.'))
            data.set('date', this.date)

            fetch('/api/task/reissuance/' + this.$route.params.id, {
                method: 'POST',
                body: data,
            }).then(r => {
                if (r.status != 200) {
                    this.err = 'Невозможно выполнить запрос'
                    return
                }

                return r.json()
            }).then(r => {
                if (r) {
                    if (r == true) {
                        this.$router.push({name: 'Task', params: {id: this.$route.params.id, }, })
                    } else {
                        this.err = 'Невозможно выполнить запрос'
                    }
                }
            }).catch(r => {
                console.log(r)
            })
        }
    }
}
</script>