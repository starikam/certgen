<template>
    <div class="columns is-multiline">
        <div class="column is-6">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <p>Всего сертификатов: <span class="has-text-success">{{ $root.meta.stat.total }}</span></p>
                        <p>Выпущено за месяц: <span class="has-text-info">{{ $root.meta.stat.total_month }}</span></p>
                        <p>Выпущено за неделю: <span class="has-text-danger">{{ $root.meta.stat.total_week }}</span></p>
                        <p>Диапазон номеров: <span class="has-text-warning">{{ $root.meta.stat.id_min }} - {{ $root.meta.stat.id_max }}</span></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="column is-6">
            <div class="card">
                <div class="card-content">
                    <div class="content">
                        <a href="/api/report" class="button is-success is-light is-fullwidth">Выгрузить БД в CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Reports',
    mounted() {
        console.log('ok')
    }
}
</script>