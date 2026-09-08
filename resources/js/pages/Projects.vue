<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index } from '@/routes/projects';
import { store as generatePlan } from '@/routes/projects/generations';
import type { LatestGeneration, Project } from '@/types';

defineProps<{
    projects: Project[];
    latestGeneration: LatestGeneration | null;
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

const isGenerating = ref(false);

function generateContentPlan(project: Project): void {
    if (isGenerating.value) {
        return;
    }

    router.post(generatePlan(project).url, {}, {
        onStart: () => {
            isGenerating.value = true;
        },
        onFinish: () => {
            isGenerating.value = false;
        },
    });
}
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
            <template
                v-for="project in projects"
                :key="project.id"
            >
                <div
                    class="flex items-center justify-between gap-4 rounded-lg border p-4"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ project.title }}</p>
                        <p class="text-muted-foreground text-sm">
                            {{ project.content_type }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-4">
                        <Button
                            v-if="project.brief && !project.has_content_plan"
                            type="button"
                            size="sm"
                            :disabled="isGenerating"
                            @click="generateContentPlan(project)"
                        >
                            {{ isGenerating ? 'Generating…' : 'Generate content plan' }}
                        </Button>
                        <Badge variant="secondary">{{ project.status }}</Badge>
                        <span class="text-muted-foreground text-sm">
                            {{ project.due_date || '—' }}
                        </span>
                    </div>
                </div>

                <section
                    v-if="latestGeneration && latestGeneration.project_id === project.id"
                    class="space-y-5 rounded-lg border p-6"
                >
                    <Heading
                        variant="small"
                        title="Content plan"
                        :description="`Generated for “${latestGeneration.project_title}”`"
                    />

                    <div>
                        <h3 class="font-medium">Suggested title</h3>
                        <p class="text-muted-foreground text-sm">
                            {{ latestGeneration.suggested_title }}
                        </p>
                    </div>

                    <div>
                        <h3 class="font-medium">Content brief</h3>
                        <p class="text-muted-foreground text-sm">
                            {{ latestGeneration.content_brief }}
                        </p>
                    </div>

                    <div>
                        <h3 class="font-medium">Outline</h3>
                        <ul class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li
                                v-for="(item, outlineIndex) in latestGeneration.outline"
                                :key="outlineIndex"
                            >
                                <span class="text-foreground font-medium">{{ item.heading }}</span>
                                — {{ item.purpose }}
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-medium">Key points</h3>
                        <ul class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li
                                v-for="(keyPoint, keyPointIndex) in latestGeneration.key_points"
                                :key="keyPointIndex"
                            >
                                {{ keyPoint }}
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-medium">Production tasks</h3>
                        <ul class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li
                                v-for="(task, taskIndex) in latestGeneration.production_tasks"
                                :key="taskIndex"
                            >
                                {{ task }}
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="font-medium">Risks or missing information</h3>
                        <ul class="text-muted-foreground list-disc space-y-1 pl-5 text-sm">
                            <li
                                v-for="(risk, riskIndex) in latestGeneration.risks_or_missing_information"
                                :key="riskIndex"
                            >
                                {{ risk }}
                            </li>
                        </ul>
                    </div>
                </section>
            </template>
        </div>
    </div>
</template>
