<template>
    <div class="columns">
        <div class="column">
            <h1 class="has-text-weight-semibold view-header">Импорт CSV для массового выпуска писем</h1>
            <form v-on:submit="importSubmited">
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
                    <label class="label has-text-weight-normal">CSV файл</label>
                    <div class="file has-name is-fullwidth">
                        <label class="file-label">
                        <input required class="file-input" type="file" name="csvfile" v-on:change="onFileChange">
                            <span class="file-cta">
                                <span class="file-icon">
                                    <i class="fas fa-upload"></i>
                                </span>
                                <span class="file-label">Выберите файл</span>
                            </span>
                            <span class="file-name" v-if="csvfile">{{ csvfile.name }}</span>
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
            hours: 32,
            date: new Date().toISOString().slice(0,10),
            csvfile: null,
            err: null,
        }
    },
    methods: {
        onFileChange: function (e) {
            let files = e.target.files || e.dataTransfer.files
            if (!files.length) return

            this.csvfile = files[0]

            this.fileErr = null
            console.log(this.csvfile.type)
            // if (['text/csv', 'text/plain'].indexOf(this.csvfile.type) == -1)
            //     this.err = 'Недопустимый формат файла'
        },
        importSubmited: function (e) {
            e.preventDefault()

            let data = new FormData()
            data.set('template_id', parseInt(this.template_id))
            data.set('csvfile', this.csvfile)

            fetch('/api/task-letter/add', {
                method: 'POST',
                body: data,
            }).then(r => {
                if (r.status != 200) {
                    this.err = 'Невозможно выполнить запрос'
                    return
                }

                return r.json()
            }).then(r => {
                if ('task_id' in r) {
                    // fetch('/api/tasks/' + r.task_id + '/run', {
                    //     method: 'GET',
                    // })

                    this.$router.push({name: 'Task', params: {id: r.task_id, }, })
                }
            }).catch(r => {
                console.log(r)
            })
        }
    }
}
</script>