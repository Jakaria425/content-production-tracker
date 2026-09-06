<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { index } from '@/routes/projects';
import type { Project } from '@/types';

defineProps<{
    projects: Project[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Projects"
            description="Your content production projects"
        />

        <p class="text-muted-foreground text-sm">
            {{
                projects.length === 1
                    ? 'You have 1 project.'
                    : `You have ${projects.length} projects.`
            }}
        </p>

        <div v-if="projects.length === 0" class="rounded-lg border p-8">
            <p class="text-muted-foreground text-center">
                You have no projects yet. New projects will appear here.
            </p>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="project in projects"
                :key="project.id"
                class="flex items-center justify-between gap-4 rounded-lg border p-4"
            >
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ project.title }}</p>
                    <p class="text-muted-foreground text-sm">
                        {{ project.content_type }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-4">
                    <Badge variant="secondary">{{ project.status }}</Badge>
                    <span class="text-muted-foreground text-sm">
                        {{ project.due_date || '—' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>