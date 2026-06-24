import React from 'react'
import { motion } from 'framer-motion'
import { Loader2 } from 'lucide-react'
import { cn } from '../../lib/cn'

const variants = {
  primary:   'bg-brand-600 text-white shadow-sm hover:bg-brand-700 active:bg-brand-800 focus-visible:ring-brand-500',
  secondary: 'bg-[var(--surface)] text-[var(--text-primary)] border border-[var(--border)] shadow-xs hover:bg-[var(--surface-2)] focus-visible:ring-brand-500',
  danger:    'bg-red-600 text-white shadow-sm hover:bg-red-700 active:bg-red-800 focus-visible:ring-red-500',
  ghost:     'text-[var(--text-secondary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)] focus-visible:ring-brand-500',
  link:      'text-brand-600 hover:text-brand-700 underline-offset-4 hover:underline focus-visible:ring-brand-500 p-0 h-auto',
  success:   'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 focus-visible:ring-emerald-500',
}

const sizes = {
  xs: 'h-7  px-2.5 text-xs   gap-1.5 rounded',
  sm: 'h-8  px-3   text-sm   gap-1.5 rounded-md',
  md: 'h-9  px-4   text-sm   gap-2   rounded-lg',
  lg: 'h-11 px-5   text-base gap-2   rounded-lg',
  xl: 'h-12 px-6   text-base gap-2.5 rounded-xl',
}

export function Button({
  children,
  variant = 'primary',
  size = 'md',
  loading = false,
  disabled = false,
  leftIcon,
  rightIcon,
  className,
  as: Tag = 'button',
  ...props
}) {
  const isDisabled = disabled || loading

  return (
    <motion.button
      whileHover={isDisabled ? {} : { scale: 1.01 }}
      whileTap={isDisabled  ? {} : { scale: 0.98 }}
      transition={{ duration: 0.1 }}
      className={cn(
        'inline-flex items-center justify-center font-medium',
        'transition-colors duration-150',
        'focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        'disabled:opacity-50 disabled:pointer-events-none',
        'select-none',
        variants[variant],
        sizes[size],
        className
      )}
      disabled={isDisabled}
      {...props}
    >
      {loading ? (
        <Loader2 className="animate-spin" size={14} />
      ) : leftIcon ? (
        <span className="shrink-0">{leftIcon}</span>
      ) : null}
      {children}
      {rightIcon && !loading && <span className="shrink-0">{rightIcon}</span>}
    </motion.button>
  )
}
