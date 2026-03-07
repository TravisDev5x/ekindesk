import * as React from "react"
import { cn } from "@/lib/utils"

/**
 * Contenedor para tablas con scroll horizontal en móvil.
 * - Oculta la barra de scroll (cross-browser).
 * - Añade margen negativo y padding en móvil (-mx-4 px-4), restaurado en md.
 * - Gradiente indicador de scroll a la derecha solo en móvil.
 * Compatible con Table de shadcn: usa forwardRef y HTMLAttributes<HTMLDivElement>.
 */
const TableWrapper = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, children, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("relative", className)}
    {...props}
  >
    <div
      className={cn(
        "overflow-x-auto scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0"
      )}
    >
      {children}
    </div>
    {/* Gradiente indicador de scroll horizontal (solo móvil) */}
    <div
      aria-hidden="true"
      className="pointer-events-none absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-background to-transparent md:hidden"
    />
  </div>
))
TableWrapper.displayName = "TableWrapper"

export { TableWrapper }
