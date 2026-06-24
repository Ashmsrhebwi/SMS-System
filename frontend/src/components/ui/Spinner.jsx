import React from 'react'
import { cn } from '../../lib/cn'

const sizes = {
  xs: 'w-3 h-3 border',
  sm: 'w-4 h-4 border-2',
  md: 'w-6 h-6 border-2',
  lg: 'w-8 h-8 border-[3px]',
  xl: 'w-12 h-12 border-4',
}

export function Spinner({ size = 'md', className }) {
  return (
    <div
      className={cn(
        'rounded-full border-transparent border-t-brand-600 animate-spin',
        'border-l-brand-600/30 border-r-brand-600/30 border-b-brand-600/30',
        sizes[size],
        className
      )}
      role="status"
      aria-label="Loading"
    />
  )
}

export function PageSpinner() {
  return (
    <div className="flex h-64 items-center justify-center">
      <Spinner size="lg" />
    </div>
  )
}
