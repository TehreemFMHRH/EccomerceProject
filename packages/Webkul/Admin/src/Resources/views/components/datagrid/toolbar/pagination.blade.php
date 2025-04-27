<v-datagrid-pagination
    :is-loading="isLoading"
    :available="available"
    :applied="applied"
    @changePage="changePage"
    @changePerPageOption="changePerPageOption"
>
    {{ $slot }}
</v-datagrid-pagination>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-pagination-template"
    >
        <slot
            name="pagination"
            :available="available"
            :applied="applied"
            :change-page="changePage"
            :change-per-page-option="changePerPageOption"
        >
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.toolbar.pagination />
            </template>

            <template v-else>
                <div class="flex items-center gap-x-2">
                    <x-admin::dropdown>
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                            >
                                <span>
                                    @{{ applied.pagination.perPage }}
                                </span>

                                <span class="icon-sort-down text-2xl"></span>
                            </button>
                        </x-slot>

                        <x-slot:menu>
                            <x-admin::dropdown.menu.item
                                v-for="perPageOption in available.meta.per_page_options"
                                @click="changePerPageOption(perPageOption)"
                            >
                                @{{ perPageOption }}
                            </x-admin::dropdown.menu.item>
                        </x-slot>
                    </x-admin::dropdown>

                    <p class="whitespace-nowrap text-gray-600 dark:text-gray-300 max-sm:hidden">
                        @lang('admin::app.components.datagrid.toolbar.per-page')
                    </p>

                    <input
                        type="text"
                        class="inline-flex min-h-[38px] max-w-10 appearance-none items-center justify-center gap-x-1 rounded-md border bg-white px-3 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                        :value="available.meta.current_page"
                        @change="changePage(parseInt($event.target.value))"
                    >

                    <div class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                        <span>
                            @lang('admin::app.components.datagrid.toolbar.of')
                        </span>

                        <span>
                            @{{ available.meta.last_page }}
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <div
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 transition-all marker:shadow hover:bg-gray-200 active:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"
                            @click="changePage('previous')"
                        >
                            <span class="icon-sort-left rtl:icon-sort-right text-2xl"></span>
                        </div>

                        <div
                            class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 transition-all marker:shadow hover:bg-gray-200 active:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"
                            @click="changePage('next')"
                        >
                            <span class="icon-sort-right rtl:icon-sort-left text-2xl"></span>
                        </div>
                    </div>
                </div>
            </template>
        </slot>

        <!-- Empty slot for right toolbar after -->
        <slot name="right-toolbar-left-after"></slot>
    </script>

    <script type="module">
        app.component('v-datagrid-pagination', {
            template: '#v-datagrid-pagination-template',

            props: ['isLoading', 'available', 'applied'],

            emits: ['changePage', 'changePerPageOption'],

            methods: {
                
                changePage(directionOrPageNumber) {
                    let newPage;

                    if (typeof directionOrPageNumber === 'string') {
                        if (directionOrPageNumber === 'previous') {
                            newPage = this.available.meta.current_page - 1;
                        } else if (directionOrPageNumber === 'next') {
                            newPage = this.available.meta.current_page + 1;
                        } else {
                            console.warn('Invalid Direction Provided : ' + directionOrPageNumber);

                            return;
                        }
                    }  else if (typeof directionOrPageNumber === 'number') {
                        newPage = directionOrPageNumber;
                    } else {
                        console.warn('Invalid Input Provided: ' + directionOrPageNumber);

                        return;
                    }

                    
                    if (newPage >= 1 && newPage <= this.available.meta.last_page) {
                        this.$emit('changePage', newPage);
                    } else {
                        console.warn('Invalid Page Provided: ' + newPage);
                    }
                },

                
                changePerPageOption(option) {
                    this.$emit('changePerPageOption', option);
                },
            },
        });
    </script>
@endPushOnce
