import React, { createContext, useCallback, useContext, useState } from 'react'

const ToastContext = createContext(null)

let idCounter = 0
const nextId = () => `toast-${++idCounter}`

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const add = useCallback((variant, message, options = {}) => {
    const id = nextId()
    const duration = options.duration ?? (variant === 'loading' ? 0 : 4000)
    setToasts(prev => [...prev, { id, variant, message, duration, ...options }])
    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
    return id
  }, [])

  const remove = useCallback((id) => {
    setToasts(prev => prev.filter(t => t.id !== id))
  }, [])

  const toast = {
    success: (msg, opts) => add('success', msg, opts),
    error:   (msg, opts) => add('error', msg, opts),
    warning: (msg, opts) => add('warning', msg, opts),
    info:    (msg, opts) => add('info', msg, opts),
    loading: (msg, opts) => add('loading', msg, { duration: 0, ...opts }),
  }

  return (
    <ToastContext.Provider value={{ toasts, toast, dismiss: remove }}>
      {children}
    </ToastContext.Provider>
  )
}

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast must be used within ToastProvider')
  return ctx
}
