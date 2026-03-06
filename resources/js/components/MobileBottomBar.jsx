import React from 'react'
import { NavLink, useLocation } from 'react-router-dom'
import { useI18n } from '@/hooks/useI18n'
import { cn } from '@/lib/utils'
import { Home, Ticket, LayoutDashboard, Menu } from 'lucide-react'

const ICON_SIZE = 22

const items = [
  { to: '/', labelKey: 'nav.home', icon: Home, end: true },
  { to: '/resolbeb/mis-tickets', labelKey: 'nav.myTickets', icon: Ticket, end: false },
  { to: '/resolbeb', labelKey: 'nav.resolbeb', icon: LayoutDashboard, end: true },
]

export function MobileBottomBar({ onOpenMenu, forceVisible = false }) {
  const { t } = useI18n()
  const location = useLocation()

  return (
    <nav
      className={cn(
        'fixed bottom-0 left-0 right-0 z-50',
        forceVisible ? 'flex flex-col' : 'md:hidden',
        'px-4 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2'
      )}
      aria-label="Navegación principal"
    >
      <div
        className={cn(
          'mx-auto flex max-w-lg items-center justify-around gap-1',
          'rounded-2xl border border-border/60 bg-card/95 py-2 px-2 shadow-lg backdrop-blur-md'
        )}
      >
        {items.map(({ to, labelKey, icon: Icon, end }) => {
          const isActive = end
            ? location.pathname === to
            : location.pathname === to || location.pathname.startsWith(to + '/')
          return (
            <NavLink
              key={to}
              to={to}
              end={end}
              className={cn(
                'flex flex-col items-center justify-center gap-0.5 rounded-xl py-2 px-3 min-w-[64px] transition-colors',
                isActive
                  ? 'bg-accent text-accent-foreground'
                  : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground'
              )}
              aria-current={isActive ? 'page' : undefined}
            >
              <Icon size={ICON_SIZE} strokeWidth={2} className="shrink-0" />
              <span className="text-[10px] font-medium leading-tight truncate max-w-full">
                {t(labelKey)}
              </span>
            </NavLink>
          )
        })}
        <button
          type="button"
          onClick={() => onOpenMenu?.(true)}
          className={cn(
            'flex flex-col items-center justify-center gap-0.5 rounded-xl py-2 px-3 min-w-[64px] transition-colors',
            'text-muted-foreground hover:bg-muted/60 hover:text-foreground'
          )}
          aria-label="Abrir menú"
        >
          <Menu size={ICON_SIZE} strokeWidth={2} className="shrink-0" />
          <span className="text-[10px] font-medium leading-tight">Más</span>
        </button>
      </div>
    </nav>
  )
}
