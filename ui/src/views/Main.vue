<template>
    <div>
        <div class="tabs">
            <ul>
                <li v-if="!$root.appType" v-on:click="tab = 'certs'" v-bind:class="{'is-active': tab == 'certs'}"><a>Сертификаты</a></li>
                <li v-on:click="tab = 'thksletters'" v-bind:class="{'is-active': tab == 'thksletters'}"><a>Благодарственные письма</a></li>
            </ul>
        </div>
        <div class="columns" v-if="tab == 'certs'">
            <div class="column">
                <div class="buttons">
                    <router-link :to="{name: 'Import', }" class="button is-success is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-file-csv"></i>
                        </span>
                        <span>Импорт</span>
                    </router-link>
                    <router-link :to="{name: 'Tasks', query: {tab: 'certificate', }}" class="button is-warning is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-angle-double-right"></i>
                        </span>
                        <span>Задачи</span>
                    </router-link>
                    <router-link :to="{name: 'Create', }" class="button is-danger is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-hand-sparkles"></i>
                        </span>
                        <span>Ручной выпуск сертификата</span>
                    </router-link>
                    <router-link :to="{name: 'Search', }" class="button is-link is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-search"></i>
                        </span>
                        <span>Поиск и редактирование</span>
                    </router-link>
                    <router-link :to="{name: 'Reports', }" class="button is-link is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-chart-bar"></i>
                        </span>
                        <span>Отчетность</span>
                    </router-link>
                </div>
            </div>
            <div class="column">
                <div class="buttons">
                    <router-link :to="{name: 'Courses', }" class="button is-info is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-graduation-cap"></i>
                        </span>
                        <span>Управление курсами</span>
                    </router-link>
                    <router-link :to="{name: 'Homes', }" class="button is-info is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-home"></i>
                        </span>
                        <span>Управление домиками</span>
                    </router-link>
                    <router-link :to="{name: 'Regions', }" class="button is-info is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-map"></i>
                        </span>
                        <span>Управление регионами</span>
                    </router-link>
                    <router-link :to="{name: 'Templates', }" class="button is-info is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-image"></i>
                        </span>
                        <span>Управление шаблонами</span>
                    </router-link>
                    <router-link :to="{name: 'Sources', }" class="button is-info is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-building"></i>
                        </span>
                        <span>Управление источниками</span>
                    </router-link>
                </div>
            </div>
        </div>
        <div class="columns" v-if="tab == 'thksletters'">
            <div class="column">
                <div class="buttons">
                    <router-link :to="{name: 'ImportLetter', }" class="button is-success is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-file-csv"></i>
                        </span>
                        <span>Импорт</span>
                    </router-link>
                    <router-link :to="{name: 'Tasks', query: {tab: 'letter'}, }" class="button is-warning is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-angle-double-right"></i>
                        </span>
                        <span>Задачи</span>
                    </router-link>
                    <router-link :to="{name: 'SearchLetter', }" class="button is-link is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-search"></i>
                        </span>
                        <span>Поиск и редактирование</span>
                    </router-link>
                </div>
            </div>
            <div class="column">
                <div class="buttons">
                    <router-link :to="{name: 'Templates', }" class="button is-info is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-image"></i>
                        </span>
                        <span>Управление шаблонами</span>
                    </router-link>
                    <router-link :to="{name: 'CreateLetter', }" class="button is-danger is-light is-fullwidth">
                        <span class="icon">
                            <i class="fas fa-hand-sparkles"></i>
                        </span>
                        <span>Ручной выпуск письма</span>
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Main',
    data() {
        return {
            tab: this.$root.appType == 'letter' ? 'thksletters' : 'certs'
        }
    }
}
</script>