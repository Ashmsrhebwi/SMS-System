import React from 'react'
import { cn } from '../../lib/cn'

const sizes = {
  sm: 'h-8  text-xs px-2.5',
  md: 'h-9  text-sm px-3',
  lg: 'h-11 text-sm px-4',
}

export function Input({
  label,
  error,
  hint,
  leftIcon,
  rightIcon,
  size = 'md',
  className,
  containerClassName,
  required,
  ...props
}) {
  return (
    <div className={cn('flex flex-col gap-1.5', containerClassName)}>
      {label && (
        <label className="text-sm font-medium text-[var(--text-primary)]">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}
      <div className="relative">
        {leftIcon && (
          <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--text-tertiary)] pointer-events-none">
            {leftIcon}
          </span>
        )}
        <input
          className={cn(
            'w-full rounded-lg border bg-[var(--surface)] text-[var(--text-primary)]',
            'border-[var(--border)] placeholder:text-[var(--text-tertiary)]',
            'transition-colors duration-150',
            'focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500',
            error && 'border-red-400 focus:border-red-500 focus:ring-red-400',
            leftIcon && 'pl-9',
            rightIcon && 'pr-9',
            sizes[size],
            className
          )}
          {...props}
        />
        {rightIcon && (
          <span className="absolute inset-y-0 right-0 flex items-center pr-3 text-[var(--text-tertiary)] pointer-events-none">
            {rightIcon}
          </span>
        )}
      </div>
      {error && <p className="text-xs text-red-500">{error}</p>}
      {hint && !error && <p className="text-xs text-[var(--text-tertiary)]">{hint}</p>}
    </div>
  )
}

export function Textarea({ label, error, hint, className, containerClassName, required, ...props }) {
  return (
    <div className={cn('flex flex-col gap-1.5', containerClassName)}>
      {label && (
        <label className="text-sm font-medium text-[var(--text-primary)]">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}
      <textarea
        className={cn(
          'w-full rounded-lg border bg-[var(--surface)] text-[var(--text-primary)] px-3 py-2.5 text-sm',
          'border-[var(--border)] placeholder:text-[var(--text-tertiary)]',
          'transition-colors duration-150 resize-y min-h-[100px]',
          'focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500',
          error && 'border-red-400',
          className
        )}
        {...props}
      />
      {error && <p className="text-xs text-red-500">{error}</p>}
      {hint && !error && <p className="text-xs text-[var(--text-tertiary)]">{hint}</p>}
    </div>
  )
}

export function Select({ label, error, hint, className, containerClassName, required, children, ...props }) {
  return (
    <div className={cn('flex flex-col gap-1.5', containerClassName)}>
      {label && (
        <label className="text-sm font-medium text-[var(--text-primary)]">
          {label}
          {required && <span className="ml-1 text-red-500">*</span>}
        </label>
      )}
      <select
        className={cn(
          'w-full h-9 rounded-lg border bg-[var(--surface)] text-[var(--text-primary)] px-3 text-sm',
          'border-[var(--border)]',
          'transition-colors duration-150',
          'focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500',
          error && 'border-red-400',
          className
        )}
        {...props}
      >
        {children}
      </select>
      {error && <p className="text-xs text-red-500">{error}</p>}
      {hint && !error && <p className="text-xs text-[var(--text-tertiary)]">{hint}</p>}
    </div>
  )
}
