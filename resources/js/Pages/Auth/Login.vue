<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head :title="trans('Log in')" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="$t('Email')" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" :value="$t('Password')" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    <span class="ms-2 text-sm text-gray-600">{{ $t('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ $t('Forgot your password?') }}
                </Link>

                <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ $t('Log in') }}
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-4 flex items-center gap-4">
            <div class="h-px flex-1 bg-gray-200" />
            <span class="text-xs uppercase text-gray-400">{{ $t('or') }}</span>
            <div class="h-px flex-1 bg-gray-200" />
        </div>

        <a
            :href="route('auth.google.redirect')"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
        >
            <svg class="size-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47a5.54 5.54 0 01-2.4 3.63v3h3.88c2.27-2.09 3.54-5.17 3.54-8.87z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.94-2.91l-3.88-3a7.42 7.42 0 01-11.03-3.9H1.02v3.09A12 12 0 0012 24z"/><path fill="#FBBC05" d="M5.03 14.19a7.2 7.2 0 010-4.38V6.72H1.02a12 12 0 000 10.56l4.01-3.09z"/><path fill="#EA4335" d="M12 4.75c1.76 0 3.34.6 4.59 1.79l3.44-3.44C17.94 1.19 15.24 0 12 0A12 12 0 001.02 6.72l4.01 3.09A7.15 7.15 0 0112 4.75z"/></svg>
            {{ $t('Continue with Google') }}
        </a>
    </AuthenticationCard>
</template>
