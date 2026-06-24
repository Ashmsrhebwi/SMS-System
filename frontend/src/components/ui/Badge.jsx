import React from 'react'
import { cn } from '../../lib/cn'

const variants = {
  default:  'bg-[var(--surface-2)] text-[var(--text-secondary)] border border-[var(--border)]',
  brand:    'bg-brand-50 text-brand-700 border border-brand-200 dark:bg-brand-950 dark:text-brand-300 dark:border-brand-800',
  success:  'bg-[var(--success-bg)] text-emerald-700 border border-[var(--success-border)] dark:text-emerald-400',
  warning:  'bg-[var(--warning-bg)] text-amber-700 border border-[var(--warning-border)] dark:text-amber-400',
  danger:   'bg-[var(--danger-bg)] text-red-700 border border-[var(--danger-border)] dark:text-red-400',
  info:     'bg-[var(--info-bg)] text-blue-700 border border-[var(--info-border)] dark:text-blue-400',
  purple:   'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950 dark:text-purple-300 dark:border-purple-800',
  orange:   'bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-950 dark:text-orange-300 dark:border-orange-800',
}

const sizes = {
  sm: 'text-xs px-2 py-0.5 gap-1',
  md: 'text-xs px-2.5 py-1 gap-1.5',
}

const DOT_COLOR = {
  default: 'bg-gray-400',
  brand:   'bg-brand-500',
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger:  'bg-red-500',
  info:    'bg-blue-500',
  purple:  'bg-purple-500',
  orange:  'bg-orange-500',
}

export function Badge({ children, variant = 'default', size = 'sm', dot = false, className }) {
  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full font-medium',
        variants[variant],
        sizes[size],
        className
      )}
    >
      {dot && (
        <span className={cn('rounded-full w-1.5 h-1.5 shrink-0', DOT_COLOR[variant])} />
      )}
      {children}
    </span>
  )
}

// Campaign / message status → Badge variant mapping
const STATUS_MAP = {
  draft:       { variant: 'default',  label: 'Draft' },
  scheduled:   { variant: 'info',     label: 'Scheduled' },
  queued:      { variant: 'warning',  label: 'Queued' },
  sending:     { variant: 'brand',    label: 'Sending' },
  sent:        { variant: 'brand',    label: 'Sent' },
  delivered:   { variant: 'success',  label: 'Delivered' },
  completed:   { variant: 'success',  label: 'Completed' },
  failed:      { variant: 'danger',   label: 'Failed' },
  undelivered: { variant: 'danger',   label: 'Undelivered' },
  active:      { variant: 'success',  label: 'Active' },
  inactive:    { variant: 'default',  label: 'Inactive' },
  pending:     { variant: 'warning',  label: 'Pending' },
  opted_in:    { variant: 'success',  label: 'Opted In' },
  opted_out:   { variant: 'danger',   label: 'Opted Out' },
}

export function StatusBadge({ status, dot = true }) {
  const map = STATUS_MAP[status] ?? { variant: 'default', label: status }
  return <Badge variant={map.variant} dot={dot}>{map.label}</Badge>
}
