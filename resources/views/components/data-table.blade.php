@props(['title', 'rows' => [], 'columns' => [], 'perPage' => 10])

<div
    class="glass-card overflow-hidden"
    x-data="createDataTable(@js($rows), @js($columns), @js($title), {{ (int) ($perPage ?? 10) }})"
>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b ayp-border px-5 py-4">
        <div>
            <h3 class="text-sm font-semibold ayp-heading" x-text="title"></h3>
            <p class="mt-0.5 text-xs ayp-muted" x-show="rows.length > 0">
                <span x-text="rows.length"></span> record(s)
            </p>
        </div>
        <button
            type="button"
            class="ayp-export-btn inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
            @click="exportExcel()"
            :disabled="rows.length === 0"
        >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export Excel
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="ayp-table-head text-xs uppercase">
                <tr>
                    <template x-for="col in columns" :key="col">
                        <th class="px-5 py-3" x-text="col"></th>
                    </template>
                </tr>
            </thead>
            <tbody class="divide-y ayp-border">
                <template x-if="rows.length === 0">
                    <tr>
                        <td :colspan="columns.length" class="ayp-table-empty px-5 py-8 text-center">No data</td>
                    </tr>
                </template>
                <template x-for="(row, index) in paginatedRows" :key="page + '-' + index">
                    <tr class="ayp-table-row transition">
                        <template x-for="(cell, cellIndex) in rowValues(row)" :key="cellIndex">
                            <td class="ayp-table-cell px-5 py-3" x-text="formatCell(cell)"></td>
                        </template>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div
        class="flex flex-wrap items-center justify-between gap-3 border-t ayp-border px-5 py-3 text-xs ayp-muted"
        x-show="rows.length > perPage"
    >
        <p>
            Showing <span class="ayp-text font-medium" x-text="rangeStart"></span>–<span class="ayp-text font-medium" x-text="rangeEnd"></span>
            of <span class="ayp-text font-medium" x-text="rows.length"></span>
        </p>
        <div class="flex flex-wrap items-center gap-1">
            <button
                type="button"
                class="ayp-btn-ghost rounded-lg border ayp-border px-2.5 py-1 disabled:opacity-40"
                @click="goToPage(page - 1)"
                :disabled="page <= 1"
            >Prev</button>
            <template x-for="p in pageNumbers" :key="p">
                <button
                    type="button"
                    class="min-w-[2rem] rounded-lg border px-2.5 py-1 transition"
                    :class="p === page ? 'ayp-page-active' : 'ayp-border ayp-btn-ghost'"
                    @click="goToPage(p)"
                    x-text="p"
                ></button>
            </template>
            <button
                type="button"
                class="ayp-btn-ghost rounded-lg border ayp-border px-2.5 py-1 disabled:opacity-40"
                @click="goToPage(page + 1)"
                :disabled="page >= totalPages"
            >Next</button>
        </div>
    </div>
</div>
