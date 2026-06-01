import { Link } from "@inertiajs/react";

/** Enlace interno vía Inertia (`to` o `href`). */
export default function NavLink({ to, href, children, ...props }) {
    const path = href ?? to;
    if (!path) {
        return <span {...props}>{children}</span>;
    }
    return (
        <Link href={path} {...props}>
            {children}
        </Link>
    );
}
