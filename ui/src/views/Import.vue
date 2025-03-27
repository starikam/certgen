<template>
    <div class="columns">
        <div class="column">
            <h1 class="has-text-weight-semibold view-header">Импорт CSV для массового выпуска сертификатов</h1>
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
                <div class="field">
                    <label class="label has-text-weight-normal">CSV файл</label>
                    <div class="file has-name is-fullwidth">
                        <label class="file-label">
                        <input required class="file-input" multiple type="file" name="csvfile" v-on:change="onFileChange">
                            <span class="file-cta">
                                <span class="file-icon">
                                    <i class="fas fa-upload"></i>
                                </span>
                                <span class="file-label">Выберите файл</span>
                            </span>
                            <span class="file-name" v-if="csvfile">{{ filenames.join(', ') }}</span>
                            <span class="file-name" v-else>...</span>
                        </label>
                    </div>
                </div>
                <div class="notification is-danger" v-if="err">{{ err }}</div>
                <br>
                <div class="field">
                    <button v-if="!err" class="button is-success is-light is-fullwidth">Импортировать</button>
                    <button v-else disabled class="button is-success is-light is-fullwidth">Импортировать</button>
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
            hours_str: null,
            date: new Date().toISOString().slice(0,10),
            csvfile: null,
            filenames: [],
            err: null,
        }
    },
    methods: {
        onFileChange: function (e) {
            let files = e.target.files || e.dataTransfer.files
            if (!files.length) return

            this.csvfile = files
            this.filenames = []
            for (let f of this.csvfile)
                this.filenames.push(f.name)

            this.fileErr = null
            // if (['text/csv', 'text/plain'].indexOf(this.csvfile.type) == -1)
            //     this.err = 'Недопустимый формат файла'
        },
        importSubmited: function (e) {
            e.preventDefault()

            let data = new FormData()
            data.set('course_id', parseInt(this.course_id))
            data.set('institution_id', parseInt(this.institution_id))
            data.set('template_id', parseInt(this.template_id))
            data.set('source_id', parseInt(this.source_id))
            data.set('hours', this.hours.replace(',', '.'))
            data.set('hours_str', this.hours_str)
            
            for (let f of this.csvfile)
                data.append('csvfile[]', f)

            data.set('date', this.date)

            fetch('/api/task/add', {
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
                    if (r.length > 1) {
                        this.$router.push({name: 'Tasks', })
                    } else {
                        this.$router.push({name: 'Task', params: {id: r[0].task_id, }, })
                    }
                }
            }).catch(r => {
                console.log(r)
            })
        }
    }
}
</script>