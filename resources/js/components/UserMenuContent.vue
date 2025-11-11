<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { logout } from "@/routes"; // <-- Ini sudah benar, hanya import 'logout'
import { Link, router } from "@inertiajs/vue3";
import { LogOut, Settings } from "lucide-vue-next"; // (Biarkan import Settings)
</script>

<template>
    <DropdownMenuLabel>My Account</DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        
        <DropdownMenuItem as-child>
            <Link href="/my-account">
                <Settings class="mr-2 h-4 w-4" />
                <span>My Account</span>
            </Link>
        </DropdownMenuItem> 

    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem as-child>
        <button
            type="button"
            class="w-full"
            @click="router.post(logout())"
        >
            <LogOut class="mr-2 h-4 w-4" />
            <span>Log out</span>
        </button>
    </DropdownMenuItem>
</template>