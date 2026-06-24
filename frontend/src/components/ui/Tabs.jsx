import React from 'react'
import { motion } from 'framer-motion'
import { cn } from '../../lib/cn'

export function Tabs({ tabs, active, onChange, className }) {
  return (
    <div className={cn('flex items-center gap-0.5 border-b border-[var(--border)]', className)}>
      {tabs.map(tab => {
        const isActive = tab.value === active
        const Icon = tab.icon
        return (
          <button
            key={tab.value}
            onClick={() => onChange(tab.value)}
            className={cn(
              'relative flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium transition-colors',
              'focus:outline-none',
              isActive
                ? 'text-brand-600 dark:text-brand-400'
                : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'
            )}
          >
            {Icon && <Icon size={14} className="shrink-0" />}
            {tab.label}
            {tab.count != null && (
              <span className={cn(
                'ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-semibold leading-none',
                isActive
                  ? 'bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300'
                  : 'bg-[var(--surface-2)] text-[var(--text-tertiary)]'
              )}>
                {tab.count}
              </span>
            )}
            {isActive && (
              <motion.div
                className="absolute bottom-0 left-0 right-0 h-0.5 bg-brand-600 dark:bg-brand-400 rounded-full"
                layoutId="tab-indicator"
                transition={{ type: 'spring', stiffness: 500, damping: 40 }}
              />
            )}
          </button>
        )
      })}
    </div>
  )
}
