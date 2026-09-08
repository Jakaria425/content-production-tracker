export type ProjectContentType =
    | 'Ebook'
    | 'Blog post'
    | 'Newsletter'
    | 'Social post';

export type ProjectStatus = 'Draft' | 'In progress' | 'Review' | 'Complete';

export type Project = {
    id: number;
    title: string;
    content_type: ProjectContentType;
    status: ProjectStatus;
    due_date: string | null;
    brief: string | null;
};

export type ContentPlanOutlineItem = {
    heading: string;
    purpose: string;
};

export type ContentPlan = {
    suggested_title: string;
    content_brief: string;
    outline: ContentPlanOutlineItem[];
    key_points: string[];
    production_tasks: string[];
    risks_or_missing_information: string[];
};

export type LatestGeneration = {
    id: number;
    project_title: string;
} & ContentPlan;
