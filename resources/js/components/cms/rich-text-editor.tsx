import { Color } from '@tiptap/extension-color';
import { Image } from '@tiptap/extension-image';
import { TextAlign } from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import { EditorContent, useEditor  } from '@tiptap/react';
import type {Editor} from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    AlignCenter,
    AlignLeft,
    AlignRight,
    Bold,
    Heading2,
    Heading3,
    Image as ImageIcon,
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
import { useEffect, useState } from 'react';

import type { LibraryImage } from '@/components/cms/media-picker';
import { MediaPickerModal } from '@/components/cms/media-picker';
import { Separator } from '@/components/ui/separator';
import { Toggle } from '@/components/ui/toggle';
import { cn } from '@/lib/utils';

/**
 * The text-colour palette offered in the toolbar — the brand colours (readable
 * on white) plus a reset. Kept to a small fixed set so the sanitiser only has
 * to permit these; arbitrary colours are neither offered nor needed.
 */
const TEXT_COLORS: Array<{ label: string; value: string }> = [
    { label: 'أزرق', value: '#069ed9' },
    { label: 'أخضر', value: '#00843d' },
    { label: 'أحمر', value: '#c8102e' },
    { label: 'رمادي', value: '#555555' },
];

/**
 * Preset widths for a selected image. Stored as a percentage on the HTML
 * `width` attribute (`null` = the image's natural, container-capped size), so
 * sizing stays responsive and survives the `content` purifier untouched.
 */
const IMAGE_SIZES: Array<{ label: string; value: string | null }> = [
    { label: 'صغيرة', value: '25%' },
    { label: 'متوسطة', value: '50%' },
    { label: 'كبيرة', value: '75%' },
    { label: 'كاملة', value: null },
];

/**
 * The image node, extended with a `width` attribute so a selected image can be
 * resized from the toolbar. Kept as the HTML `width` attribute holding a
 * percentage (e.g. `"50%"`) — HTML 4.01 permits a percentage there, and the
 * `content` purifier profile already allows `img[…|width|…]`, so it round-trips
 * through save with no sanitiser change.
 */
const ResizableImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: (element) => element.getAttribute('width'),
                renderHTML: (attributes) => {
                    const width = attributes.width as string | null;

                    return width ? { width } : {};
                },
            },
        };
    },
});

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
    library,
}: {
    value: string;
    onChange: (html: string) => void;
    dir?: 'rtl' | 'ltr';
    placeholder?: string;
    /** When provided, a toolbar button inserts an image from this library. */
    library?: LibraryImage[];
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
            // Text colour: TextStyle carries the mark, Color sets it. Emitted as
            // <span style="color:…">, allowed by the `content` purifier profile.
            TextStyle,
            Color,
            // Paragraph/heading alignment. `defaultAlignment: 'start'` means an
            // untouched block emits no style (so RTL keeps its natural start
            // edge); only an explicit left/center/right is written out.
            TextAlign.configure({ types: ['heading', 'paragraph'], defaultAlignment: 'start' }),
            // Block images inserted from the media library, resizable via a
            // `width` attribute; <img> is allowlisted in the `content` profile.
            ResizableImage.configure({ inline: false, allowBase64: false }),
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
            <Toolbar editor={editor} library={library} />
            <EditorContent editor={editor} />
        </div>
    );
}

function Toolbar({ editor, library }: { editor: Editor; library?: LibraryImage[] }) {
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

            <Btn active={editor.isActive({ textAlign: 'right' })} onClick={() => editor.chain().focus().setTextAlign('right').run()} label="محاذاة لليمين">
                <AlignRight className="size-4" />
            </Btn>
            <Btn active={editor.isActive({ textAlign: 'center' })} onClick={() => editor.chain().focus().setTextAlign('center').run()} label="توسيط">
                <AlignCenter className="size-4" />
            </Btn>
            <Btn active={editor.isActive({ textAlign: 'left' })} onClick={() => editor.chain().focus().setTextAlign('left').run()} label="محاذاة لليسار">
                <AlignLeft className="size-4" />
            </Btn>

            <Divider />

            <ColorControl editor={editor} />
            {library && library.length > 0 && <ImageControl editor={editor} library={library} />}
            <ImageSizeControl editor={editor} />

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

function ColorControl({ editor }: { editor: Editor }) {
    const [open, setOpen] = useState(false);
    const active = (editor.getAttributes('textStyle').color as string | undefined) ?? null;

    return (
        <div className="relative">
            <Toggle
                size="sm"
                pressed={open || active !== null}
                onPressedChange={() => setOpen((v) => !v)}
                aria-label="لون النص"
                aria-expanded={open}
                title="لون النص"
                className="size-8 p-0"
            >
                <span
                    className="flex size-4 items-center justify-center rounded-sm border border-border text-[11px] font-bold"
                    style={{ color: active ?? 'currentColor' }}
                >
                    A
                </span>
            </Toggle>

            {open && (
                <>
                    <div className="fixed inset-0 z-10" onClick={() => setOpen(false)} aria-hidden />
                    <div className="absolute top-full z-20 mt-1 flex items-center gap-1 rounded-md border border-border bg-popover p-1.5 shadow-md">
                        {TEXT_COLORS.map((color) => (
                            <button
                                key={color.value}
                                type="button"
                                aria-label={color.label}
                                title={color.label}
                                onClick={() => {
                                    editor.chain().focus().setColor(color.value).run();
                                    setOpen(false);
                                }}
                                className={cn(
                                    'size-6 rounded-full ring-offset-1 transition hover:ring-2 hover:ring-ring',
                                    active === color.value && 'ring-2 ring-ring',
                                )}
                                style={{ backgroundColor: color.value }}
                            />
                        ))}

                        {/* Custom colour — the full picker/wheel for any hue. */}
                        <label
                            className="relative flex size-6 cursor-pointer items-center justify-center overflow-hidden rounded-full border border-border"
                            title="لون مخصّص"
                            aria-label="لون مخصّص"
                        >
                            <span
                                className="pointer-events-none absolute inset-0 rounded-full"
                                style={{ background: 'conic-gradient(red, yellow, lime, aqua, blue, magenta, red)' }}
                                aria-hidden
                            />
                            <input
                                type="color"
                                value={active ?? '#000000'}
                                onChange={(e) => editor.chain().focus().setColor(e.target.value).run()}
                                className="absolute inset-0 cursor-pointer opacity-0"
                            />
                        </label>

                        <Separator orientation="vertical" className="mx-0.5 h-5" />
                        <button
                            type="button"
                            aria-label="بلا لون"
                            title="بلا لون"
                            onClick={() => {
                                editor.chain().focus().unsetColor().run();
                                setOpen(false);
                            }}
                            className="flex size-6 items-center justify-center rounded-full border border-border text-xs text-muted-foreground hover:bg-muted"
                        >
                            ✕
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}

function ImageControl({ editor, library }: { editor: Editor; library: LibraryImage[] }) {
    const [open, setOpen] = useState(false);

    return (
        <>
            <Btn onClick={() => setOpen(true)} label="إدراج صورة">
                <ImageIcon className="size-4" />
            </Btn>

            {open && (
                <MediaPickerModal
                    library={library}
                    accept="image"
                    onSelect={(id) => {
                        const image = library.find((m) => m.id === id);

                        if (image) {
                            editor.chain().focus().setImage({ src: image.url, alt: image.alt }).run();
                        }

                        setOpen(false);
                    }}
                    onClose={() => setOpen(false)}
                />
            )}
        </>
    );
}

/**
 * Contextual size controls: appears only while an image is selected, and sets
 * its stored `width` percentage (or clears it for the full/natural size).
 */
function ImageSizeControl({ editor }: { editor: Editor }) {
    if (!editor.isActive('image')) {
        return null;
    }

    const current = (editor.getAttributes('image').width as string | null | undefined) ?? null;

    return (
        <>
            <Divider />
            <div className="flex items-center gap-0.5" role="group" aria-label="حجم الصورة">
                {IMAGE_SIZES.map((size) => (
                    <button
                        key={size.label}
                        type="button"
                        title={`حجم الصورة: ${size.label}`}
                        onClick={() => editor.chain().focus().updateAttributes('image', { width: size.value }).run()}
                        className={cn(
                            'h-8 rounded-md px-2 text-xs font-medium text-muted-foreground transition hover:bg-background hover:text-foreground',
                            current === size.value && 'bg-background text-druze-blue',
                        )}
                    >
                        {size.label}
                    </button>
                ))}
            </div>
        </>
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
