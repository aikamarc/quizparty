<script setup>
import { reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AdminTabs from '@/Components/Admin/AdminTabs.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { trans } from 'laravel-vue-i18n';

defineProps({
    categories: { type: Array, required: true },
});

const form = useForm({
    name: '',
    image: null,
});

const subForms = reactive({});
const editForms = reactive({});

const subForm = (categoryId) => {
    if (! subForms[categoryId]) {
        subForms[categoryId] = useForm({ name: '', parent_id: categoryId });
    }

    return subForms[categoryId];
};

const addCategory = () => {
    form.post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const addSubCategory = (categoryId) => {
    subForm(categoryId).post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => subForm(categoryId).reset('name'),
    });
};

const editForm = (category) => {
    if (!editForms[category.id]) {
        editForms[category.id] = useForm({ name: category.name, image: null, _method: 'patch' });
    }
    return editForms[category.id];
};

const updateCategory = (category) => {
    editForm(category).post(route('admin.categories.update', category.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => editForm(category).reset('image'),
    });
};

const removeCategory = (categoryId) => {
    if (confirm(trans('Delete this category and its subcategories?'))) {
        router.delete(route('admin.categories.destroy', categoryId), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :title="$t('Categories')">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">
                {{ $t('Categories') }}
            </h2>
            <AdminTabs />
        </template>

        <div class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('New category') }}</h3>

                <form class="mt-4 grid items-start gap-4 sm:grid-cols-[1fr_1fr_auto]" @submit.prevent="addCategory">
                    <div>
                        <TextInput v-model="form.name" :placeholder="$t('Example: Pop')" class="block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div><input type="file" accept="image/*" class="block w-full text-sm font-bold text-slate-500 file:me-3 file:rounded-xl file:border-0 file:bg-violet-100 file:px-4 file:py-2 file:font-black file:text-violet-700" @change="form.image = $event.target.files[0]" /><InputError :message="form.errors.image" class="mt-2" /></div>
                    <PrimaryButton :disabled="form.processing">{{ $t('Add') }}</PrimaryButton>
                </form>
            </div>

            <div v-if="! categories.length" class="rounded-md bg-white p-6 text-sm text-gray-500 shadow dark:bg-gray-800 dark:text-gray-400">
                {{ $t('No categories yet.') }}
            </div>

            <div
                v-for="category in categories"
                :key="category.id"
                class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800"
            >
                <div v-if="category.image_url" class="mb-5 h-40 overflow-hidden rounded-2xl"><img :src="category.image_url" :alt="category.name" class="size-full object-cover"></div>
                <form class="flex flex-col gap-3 sm:flex-row sm:items-start" @submit.prevent="updateCategory(category)">
                    <div class="flex-1"><TextInput v-model="editForm(category).name" class="block w-full" required /><InputError :message="editForm(category).errors.name" class="mt-2" /></div>
                    <div class="flex-1"><input type="file" accept="image/*" class="block w-full text-sm font-bold text-slate-500 file:me-3 file:rounded-xl file:border-0 file:bg-violet-100 file:px-3 file:py-2 file:font-black file:text-violet-700" @change="editForm(category).image = $event.target.files[0]" /><InputError :message="editForm(category).errors.image" class="mt-2" /></div>
                    <PrimaryButton :disabled="editForm(category).processing">{{ $t('Save') }}</PrimaryButton>
                    <DangerButton @click="removeCategory(category.id)">{{ $t('Delete') }}</DangerButton>
                </form>

                <ul v-if="category.children.length" class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="child in category.children" :key="child.id" class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ child.name }}</span>
                        <DangerButton @click="removeCategory(child.id)">{{ $t('Delete') }}</DangerButton>
                    </li>
                </ul>

                <form class="mt-4 flex items-start gap-3" @submit.prevent="addSubCategory(category.id)">
                    <div class="flex-1">
                        <TextInput v-model="subForm(category.id).name" :placeholder="$t('New subcategory')" class="block w-full" />
                        <InputError :message="subForm(category.id).errors.name" class="mt-2" />
                    </div>
                    <PrimaryButton :disabled="subForm(category.id).processing">+ {{ $t('Subcategory') }}</PrimaryButton>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
