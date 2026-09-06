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
};
