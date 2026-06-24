import React, { useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { AnimatePresence, motion } from 'framer-motion'
import { cn } from '../../lib/cn'

export function Tooltip({ children, content, side = 'top', delay = 400, className }) {
  const [visible, setVisible] = useState(false)
  const [pos, setPos]         = useState({ x: 0, y: 0 })
  const timerRef              = useRef(null)
  const triggerRef            = useRef(null)

  if (!content) return children

  const show = () => {
    timerRef.current = setTimeout(() => {
      const rect = triggerRef.current?.getBoundingClientRect()
      if (!rect) return
      const gap = 6
      let x, y
      if (side === 'top')    { x = rect.left + rect.width / 2; y = rect.top - gap }
      if (side === 'bottom') { x = rect.left + rect.width / 2; y = rect.bottom + gap }
      if (side === 'left')   { x = rect.left - gap;            y = rect.top + rect.height / 2 }
      if (side === 'right')  { x = rect.right + gap;           y = rect.top + rect.height / 2 }
      setPos({ x, y })
      setVisible(true)
    }, delay)
  }

  const hide = () => {
    clearTimeout(timerRef.current)
    setVisible(false)
  }

  const transforms = {
    top:    'translateX(-50%) translateY(-100%)',
    bottom: 'translateX(-50%)',
    left:   'translateX(-100%) translateY(-50%)',
    right:  'translateY(-50%)',
  }

  const initials = {
    top:    { y: 4 },
    bottom: { y: -4 },
    left:   { x: 4 },
    right:  { x: -4 },
  }

  return (
    <>
      <span ref={triggerRef} onMouseEnter={show} onMouseLeave={hide} onFocus={show} onBlur={hide} className="inline-flex">
        {children}
      </span>
      {createPortal(
        <AnimatePresence>
          {visible && (
            <motion.div
              className={cn(
                'fixed z-[9990] pointer-events-none',
                'bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium',
                'px-2.5 py-1.5 rounded-lg shadow-lg',
                'whitespace-nowrap max-w-xs',
                className
              )}
              style={{ left: pos.x, top: pos.y, transform: transforms[side] }}
              initial={{ opacity: 0, ...initials[side] }}
              animate={{ opacity: 1, x: 0, y: 0 }}
              exit={{ opacity: 0 }}
              transition={{ duration: 0.1 }}
            >
              {content}
            </motion.div>
          )}
        </AnimatePresence>,
        document.body
      )}
    </>
  )
}
