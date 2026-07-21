/**
 * The five-pointed Druze star, the community's emblem. Each point carries one
 * of the five cosmic principles, top-clockwise:
 *   green  — العقل   (mind / intelligence)
 *   red    — النفس   (soul)
 *   yellow — الكلمة  (word)
 *   blue   — السابق  (precedent)
 *   white  — التالي  (immanence)
 *
 * Rendered as inline SVG so it scales crisply, mirrors nothing, and — unlike
 * the original site's raster logo — can carry an accessible name.
 */
export function DruzeStar({
    className,
    title,
    decorative = false,
}: {
    className?: string;
    title?: string;
    decorative?: boolean;
}) {
    // Five points at 72° spacing, starting at the top (−90°).
    const cx = 50;
    const cy = 52;
    const outer = 47;
    const inner = 19;
    const rad = (deg: number) => (deg * Math.PI) / 180;
    const at = (r: number, deg: number) => `${cx + r * Math.cos(rad(deg))},${cy + r * Math.sin(rad(deg))}`;

    // Principle order clockwise from the top point.
    const colors = ['#00843D', '#0057B8', '#FFB81C', '#C8102E', '#FFFFFF'];

    return (
        <svg
            viewBox="0 0 100 104"
            className={className}
            role={decorative ? 'presentation' : 'img'}
            aria-hidden={decorative || undefined}
            aria-label={decorative ? undefined : title}
        >
            {!decorative && title && <title>{title}</title>}

            {/*
             * Each principle is a kite from the centre out to one point tip,
             * bounded by the two neighbouring inner vertices — so the five
             * wedges tile the star exactly, without overlap.
             */}
            {colors.map((color, i) => {
                const base = -90 + i * 72;
                const path = [
                    `${cx},${cy}`,
                    at(inner, base - 36),
                    at(outer, base),
                    at(inner, base + 36),
                ].join(' ');

                return (
                    <polygon
                        key={i}
                        points={path}
                        fill={color}
                        stroke={color === '#FFFFFF' ? '#C9C9C9' : 'none'}
                        strokeWidth="0.75"
                    />
                );
            })}
        </svg>
    );
}
