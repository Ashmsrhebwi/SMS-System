import React from 'react'
import { createPortal } from 'react-dom'
import { AnimatePresence, motion } from 'framer-motion'
import { CheckCircle2, XCircle, AlertTriangle, Info, Loader2, X } from 'lucide-react'
import { useToast } from '../../context/ToastContext'
import { cn } from '../../lib/cn'

const CONFIG = {
  success: { icon: CheckCircle2, color: 'text-emerald-500', bg: 'bg-[var(--success-bg)] border-[var(--success-border)]' },
  error:   { icon: XCircle,      color: 'text-red-500',     bg: 'bg-[var(--danger-bg)] border-[var(--danger-border)]' },
  warning: { icon: AlertTriangle,color: 'text-amber-500',   bg: 'bg-[var(--warning-bg)] border-[var(--warning-border)]' },
  info:    { icon: Info,         color: 'text-blue-500',    bg: 'bg-[var(--info-bg)] border-[var(--info-border)]' },
  loading: { icon: Loader2,      color: 'text-brand-500',   bg: 'bg-[var(--surface)] border-[var(--border)]' },
}

function ToastItem({ toast, onDismiss }) {
  const cfg = CONFIG[toast.variant] ?? CONFIG.info
  const Icon = cfg.icon

  return (
    <motion.div
      layout
      initial={{ opacity: 0, y: 24, scale: 0.95 }}
      animate={{ opacity: 1, y: 0, scale: 1 }}
      exit={{ opacity: 0, x: 48, scale: 0.95 }}
      transition={{ duration: 0.22, ease: [0.25, 0.46, 0.45, 0.94] }}
      className={cn(
        'flex items-start gap-3 rounded-xl border px-4 py-3.5 shadow-[var(--shadow-lg)]',
        'min-w-[280px] max-w-sm pointer-events-auto',
        'bg-[var(--surface)]',
        cfg.bg
      )}
    >
      <Icon
        size={18}
        className={cn('shrink-0 mt-0.5', cfg.color, toast.variant === 'loading' && 'animate-spin')}
      />
      <p className="flex-1 text-sm text-[var(--text-primary)] leading-snug">{toast.message}</p>
      <button
        onClick={() => onDismiss(toast.id)}
        className="shrink-0 -mt-0.5 -mr-1 rounded p-1 text-[var(--text-tertiary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-2)] transition-colors"
      >
        <X size={14} />
      </button>
    </motion.div>
  )
}

export function ToastContainer() {
  const { toasts, dismiss } = useToast()

  return createPortal(
    <div className="fixed bottom-6 right-6 z-[9999] flex flex-col gap-2 pointer-events-none">
      <AnimatePresence mode="sync">
        {toasts.map(t => (
          <ToastItem key={t.id} toast={t} onDismiss={dismiss} />
        ))}
      </AnimatePresence>
    </div>,
    document.body
  )
}
