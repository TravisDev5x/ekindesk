/**
 * Enfoca el primer campo con error tras validación de formulario.
 */
export function focusFirstFormError(errors, fieldOrder = []) {
    if (!errors || typeof errors !== "object") {
        return;
    }

    const keys =
        fieldOrder.length > 0
            ? fieldOrder.filter((key) => errors[key])
            : Object.keys(errors);

    const firstKey = keys[0];
    if (!firstKey) {
        return;
    }

    const byName = document.querySelector(`[name="${firstKey}"]`);
    if (byName && typeof byName.focus === "function") {
        byName.focus();
        return;
    }

    const byId = document.getElementById(firstKey);
    if (byId && typeof byId.focus === "function") {
        byId.focus();
    }
}
