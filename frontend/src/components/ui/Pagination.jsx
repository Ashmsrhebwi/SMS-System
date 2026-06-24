import React from 'react'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '../../lib/cn'
import { Button } from './Button'

export function Pagination({ meta, onPageChange, className }) {
  if (!meta || meta.last_page <= 1) return null

  const { current_page, last_page, from, to, total } = meta

  const pages = []
  const delta = 1
  const left  = current_page - delta
  const right = current_page + delta

  for (let i = 1; i <= last_page; i++) {
    if (i === 1 || i === last_page || (i >= left && i <= right)) {
      pages.push(i)
    } else if (i === left - 1 || i === right + 1) {
      pages.push('…')
    }
  }

  // Deduplicate consecutive dots
  const deduplicated = pages.filter((p, i) => !(p === '…' && pages[i - 1] === '…'))

  return (
    <div className={cn('flex items-center justify-between px-4 py-3 border-t border-[var(--border)]', className)}>
      <p className="text-xs text-[var(--text-tertiary)]">
        Showing <span className="font-medium text-[var(--text-secondary)]">{from}–{to}</span> of{' '}
        <span className="font-medium text-[var(--text-secondary)]">{total}</span>
      </p>
      <div className="flex items-center gap-1">
        <button
          onClick={() => onPageChange(current_page - 1)}
          disabled={current_page === 1}
          className="p-1.5 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] disabled:opacity-40 disabled:pointer-events-none transition-colors"
        >
          <ChevronLeft size={14} />
        </button>
        {deduplicated.map((p, i) =>
          p === '…' ? (
            <span key={`dot-${i}`} className="px-1 text-xs text-[var(--text-tertiary)]">…</span>
          ) : (
            <button
              key={p}
              onClick={() => onPageChange(p)}
              className={cn(
                'w-7 h-7 rounded-lg text-xs font-medium transition-colors',
                p === current_page
                  ? 'bg-brand-600 text-white'
                  : 'text-[var(--text-secondary)] hover:bg-[var(--surface-2)]'
              )}
            >
              {p}
            </button>
          )
        )}
        <button
          onClick={() => onPageChange(current_page + 1)}
          disabled={current_page === last_page}
          className="p-1.5 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] disabled:opacity-40 disabled:pointer-events-none transition-colors"
        >
          <ChevronRight size={14} />
        </button>
      </div>
    </div>
  )
}
