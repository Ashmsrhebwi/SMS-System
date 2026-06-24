import React, { useRef, useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import { cn } from '../../lib/cn'

export function DropdownMenu({ trigger, items, align = 'right' }) {
  const [open, setOpen] = useState(false)
  const ref             = useRef(null)

  return (
    <div className="relative inline-block" ref={ref}>
      <div onClick={() => setOpen(v => !v)}>{trigger}</div>

      {open && <div className="fixed inset-0 z-20" onClick={() => setOpen(false)} />}

      <AnimatePresence>
        {open && (
          <motion.div
            className={cn(
              'absolute z-30 mt-1 w-48',
              'bg-[var(--surface)] rounded-xl border border-[var(--border)] shadow-[var(--shadow-xl)]',
              'overflow-hidden py-1',
              align === 'right' ? 'right-0' : 'left-0'
            )}
            initial={{ opacity: 0, y: -6, scale: 0.97 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -6, scale: 0.97 }}
            transition={{ duration: 0.12 }}
          >
            {items.map((item, i) => {
              if (item.separator) {
                return <div key={i} className="my-1 border-t border-[var(--border)]" />
              }
              const Icon = item.icon
              return (
                <button
                  key={i}
                  onClick={() => { setOpen(false); item.onClick?.() }}
                  disabled={item.disabled}
                  className={cn(
                    'w-full flex items-center gap-2.5 px-3 py-2 text-sm transition-colors',
                    'focus:outline-none',
                    item.danger
                      ? 'text-red-600 hover:bg-red-50 dark:hover:bg-red-950'
                      : 'text-[var(--text-primary)] hover:bg-[var(--surface-2)]',
                    item.disabled && 'opacity-40 pointer-events-none'
                  )}
                >
                  {Icon && <Icon size={14} className="shrink-0" />}
                  {item.label}
                  {item.shortcut && (
                    <span className="ml-auto text-xs text-[var(--text-tertiary)] font-mono">{item.shortcut}</span>
                  )}
                </button>
              )
            })}
          </motion.div>
        )}
      </AnimatePresence>
    </div>
  )
}
