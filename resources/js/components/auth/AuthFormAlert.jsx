import { authMessageError, authMessageSuccess } from "@/lib/marketingTheme";

/**
 * Mensajes de error / éxito en formularios auth públicos.
 */
export function AuthFormAlert({ error, success }) {
    if (!error && !success) {
        return null;
    }

    return (
        <div className="space-y-2">
            {error ? (
                <p className={authMessageError} role="alert" aria-live="polite">
                    {error}
                </p>
            ) : null}
            {success ? <p className={authMessageSuccess}>{success}</p> : null}
        </div>
    );
}
