<template>
    <nav class="min-w-[200px]">
        <Link
            :href="route('myFiles')"
            class="h-[88px] px-3 flex items-center gap-3"
        >
            <ApplicationLogo
                class="block h-9 w-auto fill-current text-gray-800"
            />
            LaraStorage
        </Link>
        <div class="px-3">
            <CreatenewDropdown />

            <div class="py-3">
                <NavLink
                    :href="route('myFiles')"
                    :active="$page.url === '/my-files'"
                    >MyFiles</NavLink
                >
                <NavLink
                    :href="route('file.sharedByMe')"
                    :active="$page.url === '/shared-by-me'"
                    >Shared by me</NavLink
                >
                <NavLink
                    :href="route('file.sharedWithMe')"
                    :active="$page.url === '/shared-with-me'"
                    >Shared with me</NavLink
                >
                <NavLink
                    :href="route('trash')"
                    :active="$page.url === '/trash'"
                    >trash</NavLink
                >
            </div>
        </div>
    </nav>
</template>
<script setup>
//imports
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import CreatenewDropdown from "./CreatenewDropdown.vue";
import { Link } from "@inertiajs/vue3";
import NavLink from "../NavLink.vue";
</script>
