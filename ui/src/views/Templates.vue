<template>
    <div class="columns is-multiline">
        <div class="column is-full" v-if="!formActive && !updateActive">
            <button v-on:click="create" class="button is-link is-light is-fullwidth">
                <span class="icon">
                    <i class="fas fa-plus"></i>
                </span>
                <span>Добавить</span>
            </button>
        </div>
        <div class="column is-full" v-if="formActive || updateActive">
            <div class="field">
                <label class="label has-text-weight-normal">Название шаблона</label>
                <div class="control">
                    <input required class="input" v-model="name" type="text" name="name" value="32">
                </div>
            </div>
            <div class="field">
                    <label class="label has-text-weight-normal">SVG шаблон</label>
                    <div class="file has-name is-fullwidth">
                        <label class="file-label">
                        <input required class="file-input" type="file" name="svgfile" v-on:change="onFileChange">
                            <span class="file-cta">
                                <span class="file-icon">
                                    <i class="fas fa-upload"></i>
                                </span>
                                <span class="file-label">Выберите файл</span>
                            </span>
                            <span class="file-name" v-if="svgfile && !currentSvgfile">{{ svgfile.name }}</span>
                            <span class="file-name" v-else-if="currentSvgfile">{{ currentSvgfile }}</span>
                            <span class="file-name" v-else>...</span>
                        </label>
                    </div>
                </div>
            <input v-on:click="create" v-if="!updateActive" type="submit" class="button is-success is-light is-fullwidth" value="Добавить шаблон">
            <input v-on:click="save" v-if="updateActive" type="submit" class="button is-success is-light is-fullwidth" value="Сохранить">
        </div>
        <div class="column is-full">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <table>
                            <tr v-for="template in $root.meta.templates" v-bind:key="template.id">
                                <td>{{ template.id }}</td>
                                <td>{{ template.name }}</td>
                                <td><a :href="template.svg_path" target="_blank">Превью</a></td>
                                <td>
                                    <a href="#" v-on:click="showUpdate(template.id, template.name, template.svg_path)" class="has-text-success">Редактировать</a>
                                </td>
                                <td>
                                    <a href="#" v-on:click="remove(template.id)" class="has-text-danger">Удалить</a>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            name: '',
            updateId: 0,
            formActive: false,
            updateActive: false,
            svgfile: null,
            currentSvgfile: null,
        }
    },
    methods: {
        onFileChange: function (e) {
            let files = e.target.files || e.dataTransfer.files
            if (!files.length) return

            this.svgfile = files[0]

            this.fileErr = null
            if (['text/csv', 'text/plain'].indexOf(this.svgfile.type) == -1)
                this.err = 'Недопустимый формат файла'
        },
        save() {
            if (this.name && (this.svgfile || this.currentSvgfile)) {
                let data = new FormData()
                data.set('name', this.name)

                console.log(this.svgfile)

                if (this.svgfile)
                    data.set('svgfile', this.svgfile)
                else
                    data.set('currentSvgfile', this.currentSvgfile)

                fetch('/api/template/' + this.updateId + '/save', {
                    method: 'POST',
                    body: data
                }).then(r => {
                    if (r.status != 200)
                        this.err = 'Невозможно выполнить запрос'

                    return r.json()
                }).then(r => {
                    if (r != false)
                        this.$root.refreshMeta()
                    else
                        alert('Ошибка сервера')

                    console.log(r)
                }).catch(r => {
                    console.log(r)
                })
            } else {
                alert('Имя не может быть пустым')
            }

            this.name = ''
            this.updateId = 0
            this.updateActive = false
        },
        showUpdate(id, name, svgfile) {
            this.name = name
            this.currentSvgfile = svgfile
            this.updateId = id
            this.updateActive = true
        },
        remove(id) {
            fetch('/api/template/' + id + '/remove', {
                method: 'GET',
            }).then(r => {
                if (r.status != 200)
                    this.err = 'Невозможно выполнить запрос'

                return r.json()
            }).then(r => {
                if (r != false)
                    this.$root.refreshMeta()
                else
                    alert('Ошибка сервера')

                console.log(r)
            }).catch(r => {
                console.log(r)
            })
        },
        create() {
            if (!this.formActive) {
                this.formActive = true
                return
            }

            if (this.name && this.svgfile) {
                let form = new FormData()
                form.set('name', this.name)
                form.set('svgfile', this.svgfile)

                fetch('/api/template/create', {
                    method: 'POST',
                    body: form
                }).then(r => {
                    if (r.status != 200)
                        this.err = 'Невозможно выполнить запрос'

                    return r.json()
                }).then(r => {
                    if (r != false && 'id' in r)
                        this.$root.refreshMeta()
                    else
                        alert('Ошибка сервера')

                    console.log(r)

                    this.formActive = false
                    this.name = ''
                }).catch(r => {
                    console.log(r)
                })
            }
        }
    }
}
</script>