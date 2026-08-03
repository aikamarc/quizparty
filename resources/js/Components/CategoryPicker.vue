<script setup>
import { HugeiconsIcon } from '@hugeicons/vue';
import { Tick02Icon } from '@hugeicons/core-free-icons';

defineProps({
    categories: { type: Array, required: true },
});

const selected = defineModel({ type: Array, required: true });

const isChildSelected = (child) => selected.value.includes(child.id);

const isCategoryActive = (category) => selected.value.includes(category.id)
    || category.children.some((child) => selected.value.includes(child.id));

const toggleCategory = (category) => {
    const ids = new Set(selected.value);
    const childIds = category.children.map((child) => child.id);

    if (isCategoryActive(category)) {
        ids.delete(category.id);
        childIds.forEach((id) => ids.delete(id));
    } else {
        ids.add(category.id);
        childIds.forEach((id) => ids.add(id));
    }

    selected.value = Array.from(ids);
};

const toggleChild = (child, category) => {
    const ids = new Set(selected.value);

    // A partial child selection must not keep the parent selected, otherwise
    // tracks attached directly to the parent would still be included.
    ids.delete(category.id);

    if (ids.has(child.id)) {
        ids.delete(child.id);
    } else {
        ids.add(child.id);
    }

    selected.value = Array.from(ids);
};
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div
            v-for="category in categories"
            :key="category.id"
            class="overflow-hidden rounded-3xl border bg-white/70 shadow-sm transition dark:bg-white/[.03]"
            :class="isCategoryActive(category)
                ? 'border-violet-300 shadow-violet-500/10 dark:border-violet-400/30'
                : 'border-violet-100 dark:border-white/10'"
        >
            <button
                type="button"
                class="group relative flex h-36 w-full items-center justify-center overflow-hidden bg-gradient-to-br from-violet-700 to-violet-950 p-6 text-center text-white sm:h-40"
                @click="toggleCategory(category)"
            >
                <img v-if="category.image_url" :src="category.image_url" alt="" class="absolute inset-0 size-full scale-105 object-cover blur-[2px] transition duration-500 group-hover:scale-110">
                <span class="absolute inset-0 bg-violet-950/45 backdrop-blur-[1px] transition group-hover:bg-violet-950/35" />
                <span v-if="isCategoryActive(category)" class="pointer-events-none absolute inset-2 rounded-2xl ring-3 ring-inset ring-emerald-400" />
                <span class="relative z-10 text-2xl font-black tracking-tight drop-shadow-lg">{{ category.name }}</span>
                <span class="absolute right-4 top-4 z-10 flex size-8 items-center justify-center rounded-full border-2 border-white/70 bg-black/30 shadow-lg transition" :class="isCategoryActive(category) ? 'border-emerald-300 bg-emerald-500' : ''">
                    <HugeiconsIcon v-if="isCategoryActive(category)" :icon="Tick02Icon" :size="19" class="text-white" :stroke-width="3" />
                </span>
            </button>

            <div v-if="category.children.length && isCategoryActive(category)" class="space-y-1 border-t border-violet-100 p-3 dark:border-white/10">
                <button
                    v-for="child in category.children"
                    :key="child.id"
                    type="button"
                    class="flex w-full items-center justify-between gap-3 rounded-2xl px-3 py-2.5 text-left text-sm font-bold transition"
                    :class="isChildSelected(child)
                        ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-200'
                        : 'text-slate-500 hover:bg-violet-50/70 dark:text-slate-300 dark:hover:bg-white/5'"
                    @click="toggleChild(child, category)"
                >
                    <span>{{ child.name }}</span>
                    <span class="relative h-6 w-11 shrink-0 rounded-full transition" :class="isChildSelected(child) ? 'bg-violet-600' : 'bg-slate-200 dark:bg-slate-700'">
                        <span class="absolute top-1 size-4 rounded-full bg-white shadow transition-all" :class="isChildSelected(child) ? 'left-6' : 'left-1'" />
                    </span>
                </button>
            </div>
            <div v-else-if="!category.children.length && isCategoryActive(category)" class="px-5 py-4 text-xs font-bold text-slate-400">{{ $t('No subcategories') }}</div>
        </div>
    </div>
</template>
