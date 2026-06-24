import React from 'react'
import { cn } from '../../lib/cn'

export function Skeleton({ className, ...props }) {
  return (
    <div
      className={cn('skeleton', className)}
      aria-hidden="true"
      {...props}
    />
  )
}

export function SkeletonCard() {
  return (
    <div className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-5 shadow-[var(--shadow-sm)]">
      <Skeleton className="h-4 w-24 mb-3" />
      <Skeleton className="h-8 w-32 mb-2" />
      <Skeleton className="h-3 w-20" />
    </div>
  )
}

export function SkeletonRow() {
  return (
    <div className="flex items-center gap-4 px-4 py-3 border-b border-[var(--border)]">
      <Skeleton className="h-8 w-8 rounded-full shrink-0" />
      <div className="flex-1 space-y-2">
        <Skeleton className="h-3.5 w-40" />
        <Skeleton className="h-3 w-24" />
      </div>
      <Skeleton className="h-6 w-16 rounded-full" />
      <Skeleton className="h-4 w-20" />
    </div>
  )
}

export function SkeletonTable({ rows = 5 }) {
  return (
    <div className="rounded-xl border border-[var(--border)] overflow-hidden bg-[var(--surface)]">
      {/* Header */}
      <div className="flex items-center gap-4 px-4 py-3 border-b border-[var(--border)] bg-[var(--surface-2)]">
        {[40, 100, 60, 80, 50].map((w, i) => (
          <Skeleton key={i} className={`h-3 w-${w === 40 ? '10' : w === 100 ? '24' : w === 60 ? '16' : w === 80 ? '20' : '12'}`} />
        ))}
      </div>
      {Array.from({ length: rows }).map((_, i) => <SkeletonRow key={i} />)}
    </div>
  )
}
