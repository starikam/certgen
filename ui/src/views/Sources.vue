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
                <label class="label has-text-weight-normal">Наименование источника</label>
                <div class="control">
                    <input required class="input" v-model="name" type="text" name="name" value="32">
                </div>
            </div>
            <input v-on:click="create" v-if="!updateActive" type="submit" class="button is-success is-light is-fullwidth" value="Создать новый источник">
            <input v-on:click="save" v-if="updateActive" type="submit" class="button is-success is-light is-fullwidth" value="Сохранить">
        </div>
        <div class="column is-full">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <table>
                            <tr v-for="source in $root.meta.sources" v-bind:key="source.id">
                                <td>{{ source.id }}</td>
                                <td>{{ source.name }}</td>
                                <td>{{ source.certs }}</td>
                                <td>
                                    <a href="#" v-on:click="showUpdate(source.id, source.name)" class="has-text-success">Редактировать</a>
                                </td>
                                <td>
                                    <a href="#" v-on:click="remove(source.id)" class="has-text-danger">Удалить</a>
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
        }
    },
    methods: {
        save() {
            if (this.name) {
                let data = new FormData()
                data.set('name', this.name)

                fetch('/api/source/' + this.updateId + '/save', {
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
        showUpdate(id, name) {
            this.name = name
            this.updateId = id
            this.updateActive = true
        },
        remove(id) {
            fetch('/api/source/' + id + '/remove', {
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

            if (this.name) {
                let form = new FormData()
                form.set('name', this.name)

                fetch('/api/source/create', {
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