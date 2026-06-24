import React from 'react'
import { cn } from '../../lib/cn'
import { Button } from './Button'

export function EmptyState({ icon: Icon, title, description, action, actionLabel, className }) {
  return (
    <div className={cn('flex flex-col items-center justify-center py-16 px-4 text-center', className)}>
      {Icon && (
        <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[var(--surface-2)] text-[var(--text-tertiary)]">
          <Icon size={28} strokeWidth={1.5} />
        </div>
      )}
      <h3 className="text-sm font-semibold text-[var(--text-primary)] mb-1">{title}</h3>
      {description && (
        <p className="text-sm text-[var(--text-secondary)] max-w-xs">{description}</p>
      )}
      {action && actionLabel && (
        <div className="mt-5">
          <Button onClick={action} size="sm">{actionLabel}</Button>
        </div>
      )}
    </div>
  )
}
