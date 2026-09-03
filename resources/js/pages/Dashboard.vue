<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PendingInvitationsModal from '@/components/PendingInvitationsModal.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { dashboard } from '@/routes';
import type { DashboardInvitation, Team } from '@/types';

defineProps<{
    pendingInvitations?: DashboardInvitation[];

    internshipProgress: {
        student: string;
        project: string;
        currentDay: string;
        status: string;
        message: string;
    };
}>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <PendingInvitationsModal
        v-if="pendingInvitations && pendingInvitations.length > 0"
        :invitations="pendingInvitations"
    />
    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <!-- Internship Progress -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border p-7"
            >
                <h1>Mentor</h1>
                <p>Nasir Nobin</p>
            </div>
            <div
                class="border-sidebar-border/70 dark:border-sidebar-border relative aspect-video overflow-hidden rounded-xl border"
            >
                <h1>Intern</h1>
                <p>Jakaria Hossain</p>
            </div>
        </div>
        <div class="rounded-xl border p-6">
            <h1 class="mb-6 text-2xl font-bold">Internship Progress</h1>
            <div class="space-y-3">
                <p>
                    <span class="font-semibold">Student:</span>
                    {{ internshipProgress.student }}
                </p>
                <p>
                    <span class="font-semibold">Project:</span>
                    {{ internshipProgress.project }}
                </p>
                <p>
                    <span class="font-semibold">Current Day:</span>
                    {{ internshipProgress.currentDay }}
                </p>
                <p>
                    <span class="font-semibold">Status:</span>
                    {{ internshipProgress.status }}
                </p>
                <p class="pt-3">{{ internshipProgress.message }}</p>
            </div>
        </div>
        <!-- Existing dashboard placeholders -->
    </div>
</template>
