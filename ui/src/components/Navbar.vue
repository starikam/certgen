<template>
    <nav class="navbar" role="navigation">
        <div class="navbar-brand">
            <a class="navbar-item" href="/">
                <span class="icon">
                    <i class="fas fa-graduation-cap"></i>
                </span>
                <span>Управление сертификатами</span>
            </a>
        </div>
        <div class="navbar-menu">
            <div class="navbar-end" v-if="$root.meta">
                <p class="navbar-item">Диск: {{ $root.meta.stat.free_mem }} / {{ $root.meta.stat.total_mem }}</p>
            </div>
            <div class="navbar-end">
                <router-link :to="{name: 'Main', }" class="navbar-item">Главная</router-link>
                <router-link v-if="$root.appType != 'letter'" :to="{name: 'Import', }" class="navbar-item">Импорт</router-link>
                <router-link v-else :to="{name: 'ImportLetter', }" class="navbar-item">Импорт</router-link>
                <router-link v-if="$root.appType != 'letter'" :to="{name: 'Search', }" class="navbar-item">Поиск</router-link>
                <router-link v-else :to="{name: 'SearchLetter', }" class="navbar-item">Поиск</router-link>
                <router-link v-if="$root.appType != 'letter'" :to="{name: 'Tasks', }" class="navbar-item">Задачи</router-link>
                <router-link v-else :to="{name: 'Tasks', query: {tab: 'letter'}}" class="navbar-item">Задачи</router-link>
            </div>
        </div>
    </nav>
</template>

<script>
export default {
    name: 'Navbar',
}
</script>