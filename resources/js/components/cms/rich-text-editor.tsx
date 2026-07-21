import { EditorContent, useEditor  } from '@tiptap/react';
import type {Editor} from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    Link as LinkIcon,
    List,
    ListOrdered,
    Quote,
    Redo,
    Strikethrough,
    Underline as UnderlineIcon,
    Undo,
} from 'lucide-react';
import { useEffect } from 'react';

import { Separator } from '@/components/ui/separator';
import { Toggle } from '@/components/ui/toggle';
import { cn } from '@/lib/utils';

/**
 * Rich-text (HTML) editor built on Tiptap with a shadcn-styled toolbar.
 *
 * Outputs HTML, which is sanitised server-side on save (App\Support\Html), so
 * the toolbar exposes only what the `content` purifier profile permits:
 * bold/italic/underline/strike, H2–H3, lists, blockquote and links. The
 * editable surface honours `dir`, so Arabic types RTL and Latin LTR.
 *
 * Controlled: the external `value` is pushed into the editor only when it
 * genuinely differs (e.g. switching locale tabs), never on every keystroke, so
 * the cursor is never disturbed while typing.
 */
export function RichTextEditor({
    value,
    onChange,
    dir = 'rtl',
    placeholder,
}: {
    value: string;
    onChange: (html: string) => void;
    dir?: 'rtl' | 'ltr';
    placeholder?: string;
}) {
    const editor = useEditor({
        immediatelyRender: false,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: {
                    openOnClick: false,
                    HTMLAttributes: { rel: 'noreferrer noopener', target: '_blank' },
                },
            }),
        ],
        content: value || '',
        editorProps: {
            attributes: {
                dir,
                class: 'prose-content tiptap-content min-h-40 max-w-none px-3 py-2 focus:outline-none',
                ...(placeholder ? { 'data-placeholder': placeholder } : {}),
            },
        },
        onUpdate: ({ editor }) => onChange(editor.getHTML()),
    });

    // Sync external value → editor (locale switch), guarding against loops.
    useEffect(() => {
        if (!editor) {
return;
}

        const current = editor.getHTML();
        const next = value || '';

        if (next !== current && !(next === '' && current === '<p></p>')) {
            editor.commands.setContent(next, { emitUpdate: false });
        }
    }, [value, editor]);

    // Keep the editable direction in sync with the active locale.
    useEffect(() => {
        editor?.view.dom.setAttribute('dir', dir);
    }, [dir, editor]);

    if (!editor) {
        return <div className="h-40 rounded-md border border-input bg-muted/30" />;
    }

    return (
        <div className="overflow-hidden rounded-md border border-input bg-background focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50">
            <Toolbar editor={editor} />
            <EditorContent editor={editor} />
        </div>
    );
}

function Toolbar({ editor }: { editor: Editor }) {
    const setLink = () => {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('الرابط (URL):', previous ?? 'https://');

        if (url === null) {
return;
}

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    };

    return (
        <div className="flex flex-wrap items-center gap-0.5 border-b border-border bg-muted/40 p-1">
            <Btn active={editor.isActive('bold')} onClick={() => editor.chain().focus().toggleBold().run()} label="غامق">
                <Bold className="size-4" />
            </Btn>
            <Btn active={editor.isActive('italic')} onClick={() => editor.chain().focus().toggleItalic().run()} label="مائل">
                <Italic className="size-4" />
            </Btn>
            <Btn active={editor.isActive('underline')} onClick={() => editor.chain().focus().toggleUnderline().run()} label="تسطير">
                <UnderlineIcon className="size-4" />
            </Btn>
            <Btn active={editor.isActive('strike')} onClick={() => editor.chain().focus().toggleStrike().run()} label="يتوسطه خط">
                <Strikethrough className="size-4" />
            </Btn>

            <Divider />

            <Btn active={editor.isActive('heading', { level: 2 })} onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()} label="عنوان 2">
                <Heading2 className="size-4" />
            </Btn>
            <Btn active={editor.isActive('heading', { level: 3 })} onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()} label="عنوان 3">
                <Heading3 className="size-4" />
            </Btn>

            <Divider />

            <Btn active={editor.isActive('bulletList')} onClick={() => editor.chain().focus().toggleBulletList().run()} label="قائمة نقطية">
                <List className="size-4" />
            </Btn>
            <Btn active={editor.isActive('orderedList')} onClick={() => editor.chain().focus().toggleOrderedList().run()} label="قائمة مرقمة">
                <ListOrdered className="size-4" />
            </Btn>
            <Btn active={editor.isActive('blockquote')} onClick={() => editor.chain().focus().toggleBlockquote().run()} label="اقتباس">
                <Quote className="size-4" />
            </Btn>
            <Btn active={editor.isActive('link')} onClick={setLink} label="رابط">
                <LinkIcon className="size-4" />
            </Btn>

            <Divider />

            <Btn onClick={() => editor.chain().focus().undo().run()} disabled={!editor.can().undo()} label="تراجع">
                <Undo className="size-4" />
            </Btn>
            <Btn onClick={() => editor.chain().focus().redo().run()} disabled={!editor.can().redo()} label="إعادة">
                <Redo className="size-4" />
            </Btn>
        </div>
    );
}

function Btn({
    children,
    onClick,
    active,
    disabled,
    label,
}: {
    children: React.ReactNode;
    onClick: () => void;
    active?: boolean;
    disabled?: boolean;
    label: string;
}) {
    return (
        <Toggle
            size="sm"
            pressed={active}
            disabled={disabled}
            onPressedChange={onClick}
            aria-label={label}
            title={label}
            className={cn('size-8 p-0', active && 'bg-background text-druze-blue')}
        >
            {children}
        </Toggle>
    );
}

function Divider() {
    return <Separator orientation="vertical" className="mx-0.5 h-5" />;
}
