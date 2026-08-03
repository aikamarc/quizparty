<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !! useSlots().actions);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="border border-slate-200/80 bg-white px-4 py-5 shadow-[0_8px_0_rgb(15_23_42/0.05)] dark:border-white/10 dark:bg-white/[0.06] sm:p-6"
                    :class="hasActions ? 'rounded-t-[1.75rem]' : 'rounded-[1.75rem]'"
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div v-if="hasActions" class="flex items-center justify-end rounded-b-[1.75rem] border border-t-0 border-slate-200/80 bg-slate-50 px-4 py-4 text-end shadow-[0_8px_0_rgb(15_23_42/0.05)] dark:border-white/10 dark:bg-white/[0.03] sm:px-6">
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>
