import React, { useEffect, useRef, useState } from 'react'
import { motion } from 'framer-motion'
import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { cn } from '../../lib/cn'
import { Skeleton } from '../ui/Skeleton'

// Shared count-up hook — re-used across the app
export function useCountUp(target, duration = 800) {
  const [value, setValue] = useState(0)
  const frameRef          = useRef(null)

  useEffect(() => {
    if (target == null || isNaN(Number(target))) return
    const end   = Number(target)
    const start = Date.now()

    const tick = () => {
      const elapsed  = Date.now() - start
      const progress = Math.min(elapsed / duration, 1)
      const eased    = 1 - Math.pow(1 - progress, 4)        // easeOutQuart
      setValue(Math.round(eased * end))
      if (progress < 1) frameRef.current = requestAnimationFrame(tick)
    }
    frameRef.current = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frameRef.current)
  }, [target, duration])

  return value
}

// Inline sparkline — renders a tiny SVG path from an array of numbers
function Sparkline({ data, color = 'var(--brand-500)', height = 28, className }) {
  if (!data?.length || data.length < 2) return null

  const max  = Math.max(...data)
  const min  = Math.min(...data)
  const range = max - min || 1
  const w    = 80
  const h    = height
  const step = w / (data.length - 1)

  const pts = data.map((v, i) => [
    i * step,
    h - ((v - min) / range) * h,
  ])

  const path = pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p[0].toFixed(1)} ${p[1].toFixed(1)}`).join(' ')

  // Area fill
  const area = `${path} L ${(pts.length - 1) * step} ${h} L 0 ${h} Z`

  return (
    <svg
      viewBox={`0 0 ${w} ${h}`}
      className={cn('overflow-visible', className)}
      style={{ width: 80, height }}
      preserveAspectRatio="none"
    >
      <defs>
        <linearGradient id="spk-fill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stopColor={color} stopOpacity="0.18" />
          <stop offset="100%" stopColor={color} stopOpacity="0" />
        </linearGradient>
      </defs>
      <path d={area} fill="url(#spk-fill)" />
      <path d={path} fill="none" stroke={color} strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  )
}

export function MetricCard({
  label,
  value,
  rawValue,          // numeric value for count-up; if omitted, value is shown as-is
  subValue,
  icon: Icon,
  iconColor,
  trend,
  sparkline,         // optional array of numbers for inline sparkline
  loading,
  className,
}) {
  const counted = useCountUp(rawValue, 750)

  if (loading) {
    return (
      <div className={cn('rounded-xl bg-[var(--surface)] border border-[var(--border)] p-5 shadow-[var(--shadow-sm)]', className)}>
        <Skeleton className="h-3 w-24 mb-3" />
        <Skeleton className="h-9 w-32 mb-2" />
        <Skeleton className="h-3 w-16" />
      </div>
    )
  }

  const trendPositive = trend > 0
  const trendNeutral  = trend === 0 || trend === undefined || trend === null
  const TrendIcon     = trendNeutral ? Minus : trendPositive ? TrendingUp : TrendingDown

  const displayValue = rawValue != null
    ? counted.toLocaleString()
    : value

  return (
    <motion.div
      className={cn(
        'rounded-xl bg-[var(--surface)] border border-[var(--border)]',
        'p-5 shadow-[var(--shadow-sm)]',
        'hover:shadow-[var(--shadow-md)] transition-shadow duration-200',
        className
      )}
      whileHover={{ y: -1 }}
      transition={{ duration: 0.15 }}
    >
      <div className="flex items-start justify-between mb-3">
        <p className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider">{label}</p>
        {Icon && (
          <div className={cn(
            'flex h-8 w-8 items-center justify-center rounded-xl',
            iconColor ?? 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400'
          )}>
            <Icon size={15} />
          </div>
        )}
      </div>

      <div className="flex items-end justify-between gap-3">
        <div>
          <p className="text-3xl font-bold text-[var(--text-primary)] tabular leading-none mb-1.5">
            {displayValue}
          </p>
          <div className="flex items-center gap-2">
            {!trendNeutral && (
              <div className={cn(
                'flex items-center gap-1 text-xs font-semibold',
                trendPositive ? 'text-emerald-600' : 'text-red-500'
              )}>
                <TrendIcon size={12} />
                <span>{Math.abs(trend)}%</span>
              </div>
            )}
            {subValue && (
              <p className="text-xs text-[var(--text-tertiary)]">{subValue}</p>
            )}
          </div>
        </div>

        {sparkline?.length >= 2 && (
          <Sparkline data={sparkline} />
        )}
      </div>
    </motion.div>
  )
}
