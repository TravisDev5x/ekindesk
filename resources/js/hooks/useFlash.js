import { useEffect } from "react";
import { usePage } from "@inertiajs/react";
import { notify } from "@/lib/notify";

export function useFlash() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) notify.success(flash.success);
        if (flash?.error) notify.error(flash.error);
        if (flash?.info) notify.info(flash.info);
        if (flash?.warning) notify.warning(flash.warning);
    }, [flash]);
}
