<template>
    <div class="columns is-multiline">
        <div class="column is-full">
            <form v-on:submit="searchSubmited">
                <div class="field">
                    <label class="label has-text-weight-normal">ФИО</label>
                    <div class="control">
                        <input class="input" v-model="name" type="text" name="name">
                    </div>
                </div>
                <div class="field">
                    <label class="label has-text-weight-normal">Дата создания письма</label>
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
                                <td>{{ row.created_at }}</td>
                                <td>
                                    <router-link :to="{
                                        name: 'CreateLetter', 
                                        query: {
                                            number: row.id, 
                                            name: row.fullname,
                                            shortname: row.shortname,
                                            text: row.text,
                                            position: row.position,
                                            gender: row.gender,
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
            name: '',
            created: null,
            certs: null,
            offset: 10,
        }
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
            if (this.name) form.set('name', this.name)
            if (this.created) form.set('created', this.created)

            this.offset = 10

            fetch('/api/search-letter', {
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