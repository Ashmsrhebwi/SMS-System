import React from 'react'
import { ChevronRight } from 'lucide-react'
import { Link } from 'react-router-dom'
import { cn } from '../../lib/cn'

export function PageHeader({ title, subtitle, action, breadcrumbs, className }) {
  return (
    <div className={cn('flex items-start justify-between mb-6', className)}>
      <div>
        {breadcrumbs && breadcrumbs.length > 0 && (
          <nav className="flex items-center gap-1.5 mb-2">
            {breadcrumbs.map((crumb, i) => (
              <React.Fragment key={i}>
                {i > 0 && <ChevronRight size={12} className="text-[var(--text-tertiary)]" />}
                {crumb.href ? (
                  <Link
                    to={crumb.href}
                    className="text-xs text-[var(--text-tertiary)] hover:text-[var(--text-secondary)] transition-colors"
                  >
                    {crumb.label}
                  </Link>
                ) : (
                  <span className="text-xs text-[var(--text-secondary)]">{crumb.label}</span>
                )}
              </React.Fragment>
            ))}
          </nav>
        )}
        <h1 className="text-2xl font-bold text-[var(--text-primary)] tracking-tight">{title}</h1>
        {subtitle && (
          <p className="text-sm text-[var(--text-secondary)] mt-1">{subtitle}</p>
        )}
      </div>
      {action && <div className="shrink-0 ml-6 mt-1">{action}</div>}
    </div>
  )
}
