import React, { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Users, Send, CheckCircle2,
  DollarSign, UserX, ArrowRight, Clock, Plus,
  TrendingUp, AlertCircle, Activity, MousePointerClick,
  ShieldOff, Zap,
} from 'lucide-react'
import api from '../services/api'
import { PageHeader } from '../components/layout/PageHeader'
import { DeliveryChart } from '../components/charts/DeliveryChart'
import { Card, CardHeader } from '../components/ui/Card'
import { StatusBadge } from '../components/ui/Badge'
import { Skeleton } from '../components/ui/Skeleton'
import { Button } from '../components/ui/Button'

// ── Animated counter hook ─────────────────────────────────────────────────────
function useCountUp(target, duration = 900) {
  const [value, setValue] = useState(0)
  const frameRef = useRef(null)

  useEffect(() => {
    if (target == null || isNaN(Number(target))) return
    const end   = Number(target)
    const start = Date.now()

    const tick = () => {
      const elapsed  = Date.now() - start
      const progress = Math.min(elapsed / duration, 1)
      // easeOutQuart
      const eased = 1 - Math.pow(1 - progress, 4)
      setValue(Math.round(eased * end))
      if (progress < 1) frameRef.current = requestAnimationFrame(tick)
    }
    frameRef.current = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frameRef.current)
  }, [target, duration])

  return value
}

// ── Metric card with animated counter ─────────────────────────────────────────
function MetricTile({ label, rawValue, displayValue, icon: Icon, iconColor, loading, delay = 0 }) {
  const counted = useCountUp(rawValue, 800)

  if (loading) {
    return (
      <div className="rounded-2xl bg-[var(--surface)] border border-[var(--border)] p-5 shadow-[var(--shadow-sm)]">
        <Skeleton className="h-3 w-24 mb-4" />
        <Skeleton className="h-9 w-28 mb-2" />
        <Skeleton className="h-2.5 w-16" />
      </div>
    )
  }

  return (
    <motion.div
      className="rounded-2xl bg-[var(--surface)] border border-[var(--border)] p-5 shadow-[var(--shadow-sm)] hover:shadow-[var(--shadow-md)] transition-shadow duration-200"
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.22, delay, ease: [0.25, 0.46, 0.45, 0.94] }}
      whileHover={{ y: -1 }}
    >
      <div className="flex items-start justify-between mb-4">
        <p className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider">{label}</p>
        {Icon && (
          <div className={`flex h-8 w-8 items-center justify-center rounded-xl ${iconColor}`}>
            <Icon size={15} />
          </div>
        )}
      </div>
      <p className="text-3xl font-bold text-[var(--text-primary)] tabular leading-none">
        {displayValue
          ? displayValue
          : typeof rawValue === 'number'
            ? counted.toLocaleString()
            : '—'}
      </p>
    </motion.div>
  )
}

// ── Activity feed item ─────────────────────────────────────────────────────────
const STATUS_CONFIG = {
  delivered:   { color: 'bg-emerald-500', icon: CheckCircle2,    label: 'Delivered' },
  failed:      { color: 'bg-red-500',     icon: AlertCircle,     label: 'Failed' },
  undelivered: { color: 'bg-orange-400',  icon: AlertCircle,     label: 'Undelivered' },
}

function ActivityItem({ item, loading }) {
  if (loading) {
    return (
      <div className="flex items-center gap-3 py-3">
        <Skeleton className="h-7 w-7 rounded-full shrink-0" />
        <div className="flex-1 space-y-1.5">
          <Skeleton className="h-3 w-44" />
          <Skeleton className="h-2.5 w-28" />
        </div>
        <Skeleton className="h-5 w-16 rounded-full" />
      </div>
    )
  }

  const cfg   = STATUS_CONFIG[item.status] ?? STATUS_CONFIG.delivered
  const Icon  = cfg.icon

  return (
    <div className="flex items-center gap-3 py-3 border-b border-[var(--border)] last:border-0">
      <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full ${cfg.color} text-white`}>
        <Icon size={13} />
      </div>
      <div className="flex-1 min-w-0">
        <p className="text-sm text-[var(--text-primary)] truncate">
          <span className="font-medium">{item.contact?.name ?? 'Unknown'}</span>
          <span className="text-[var(--text-tertiary)]"> via </span>
          <span className="font-medium">{item.campaign?.name ?? 'Campaign'}</span>
        </p>
        <p className="text-xs text-[var(--text-tertiary)] flex items-center gap-1 mt-0.5">
          <Clock size={10} />
          {new Date(item.updated_at).toLocaleString()}
        </p>
      </div>
      <StatusBadge status={item.status} />
    </div>
  )
}

// ── Page ───────────────────────────────────────────────────────────────────────
export default function DashboardPage() {
  const [data, setData]         = useState(null)
  const [delivery, setDelivery] = useState([])
  const [loading, setLoading]   = useState(true)

  useEffect(() => {
    Promise.all([
      api.get('/dashboard'),
      api.get('/reports/delivery', { params: { days: 30 } }),
    ])
      .then(([d, dl]) => {
        setData(d.data)
        setDelivery(dl.data.data ?? [])
      })
      .catch(() => {
        // Silently fail — dashboard remains in skeleton state
      })
      .finally(() => setLoading(false))
  }, [])

  const stats = data?.stats ?? {}
  const sym   = stats.currency_symbol ?? '$'
  const optOut = (stats.total_contacts ?? 0) - (stats.opted_in ?? 0)

  const tiles = [
    { label: 'Total Contacts',  rawValue: stats.total_contacts,  icon: Users,             iconColor: 'bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400',     delay: 0,    href: '/contacts' },
    { label: 'Opted In',        rawValue: stats.opted_in,        icon: CheckCircle2,      iconColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400', delay: 0.04, href: '/contacts' },
    { label: 'Total Campaigns', rawValue: stats.total_campaigns, icon: Send,              iconColor: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400', delay: 0.08, href: '/campaigns' },
    { label: 'Active Campaigns',rawValue: stats.active_campaigns,icon: Zap,              iconColor: 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400', delay: 0.12, href: '/campaigns' },
    { label: 'Delivered',       rawValue: stats.total_delivered, icon: TrendingUp,        iconColor: 'bg-teal-50 text-teal-600 dark:bg-teal-950 dark:text-teal-400',     delay: 0.16, href: '/reports' },
    { label: 'Click Rate',      rawValue: null, displayValue: `${stats.click_rate ?? 0}%`, icon: MousePointerClick, iconColor: 'bg-pink-50 text-pink-600 dark:bg-pink-950 dark:text-pink-400', delay: 0.20, href: '/reports' },
    { label: 'Monthly Cost',    rawValue: null, displayValue: `${sym}${(stats.cost_this_month ?? 0).toFixed(2)}`, icon: DollarSign, iconColor: 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400', delay: 0.24, href: '/reports' },
    { label: 'Suppressed',      rawValue: stats.suppressed,      icon: ShieldOff,         iconColor: 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400',         delay: 0.28, href: '/suppression' },
  ]

  return (
    <div className="space-y-6">
      <PageHeader
        title="Dashboard"
        subtitle="Platform overview and recent activity"
        action={
          <Link to="/campaigns/new">
            <Button leftIcon={<Plus size={14} />}>
              New Campaign
            </Button>
          </Link>
        }
      />

      {/* Metrics */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
        {tiles.map(t => (
          t.href ? (
            <Link key={t.label} to={t.href}>
              <MetricTile {...t} loading={loading} />
            </Link>
          ) : (
            <MetricTile key={t.label} {...t} loading={loading} />
          )
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {/* Delivery trend */}
        <Card className="lg:col-span-3">
          <CardHeader
            title="Delivery Trend"
            subtitle="Last 30 days"
            action={
              <Link to="/reports" className="text-xs text-brand-600 hover:text-brand-700 flex items-center gap-1 font-medium">
                Full report <ArrowRight size={12} />
              </Link>
            }
          />
          <DeliveryChart data={delivery} loading={loading} />
        </Card>

        {/* Top countries */}
        <Card className="lg:col-span-2">
          <CardHeader title="Top Countries" subtitle="By contact volume" />
          {loading ? (
            <div className="space-y-3.5">
              {[...Array(5)].map((_, i) => (
                <div key={i} className="flex items-center gap-3">
                  <Skeleton className="h-3 w-20" />
                  <Skeleton className="flex-1 h-2 rounded-full" />
                  <Skeleton className="h-3 w-8" />
                </div>
              ))}
            </div>
          ) : (
            <div className="space-y-3">
              {(data?.top_countries ?? []).map(({ country, count }, i) => {
                const max  = data?.top_countries?.[0]?.count ?? 1
                const pct  = Math.round((count / max) * 100)
                return (
                  <div key={country} className="flex items-center gap-3">
                    <span className="text-xs text-[var(--text-secondary)] w-20 truncate font-medium">{country}</span>
                    <div className="flex-1 h-1.5 bg-[var(--surface-2)] rounded-full overflow-hidden">
                      <motion.div
                        className="h-full bg-brand-500 rounded-full"
                        initial={{ width: 0 }}
                        animate={{ width: `${pct}%` }}
                        transition={{ duration: 0.7, delay: i * 0.06, ease: 'easeOut' }}
                      />
                    </div>
                    <span className="text-xs tabular text-[var(--text-tertiary)] w-8 text-right">{count}</span>
                  </div>
                )
              })}
              {!data?.top_countries?.length && (
                <p className="text-xs text-[var(--text-tertiary)] py-4 text-center">No data yet</p>
              )}
            </div>
          )}
        </Card>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
        {/* Recent campaigns */}
        <Card padding={false} className="lg:col-span-3">
          <div className="px-5 py-4 border-b border-[var(--border)] flex items-center justify-between">
            <div>
              <h3 className="text-sm font-semibold text-[var(--text-primary)]">Recent Campaigns</h3>
              <p className="text-xs text-[var(--text-tertiary)] mt-0.5">Latest campaign activity</p>
            </div>
            <Link to="/campaigns" className="text-xs text-brand-600 hover:text-brand-700 flex items-center gap-1 font-medium">
              View all <ArrowRight size={12} />
            </Link>
          </div>
          {loading ? (
            <div className="divide-y divide-[var(--border)]">
              {[...Array(4)].map((_, i) => (
                <div key={i} className="flex items-center gap-4 px-5 py-3.5">
                  <Skeleton className="h-4 flex-1" />
                  <Skeleton className="h-5 w-20 rounded-full" />
                  <Skeleton className="h-4 w-16" />
                </div>
              ))}
            </div>
          ) : (
            <div className="divide-y divide-[var(--border)]">
              {(data?.recent_campaigns ?? []).map((c, i) => (
                <motion.div
                  key={c.id}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: i * 0.04 }}
                >
                  <Link
                    to={`/campaigns/${c.id}`}
                    className="flex items-center gap-4 px-5 py-3.5 hover:bg-[var(--surface-2)] transition-colors group"
                  >
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-[var(--text-primary)] group-hover:text-brand-600 transition-colors truncate">
                        {c.name}
                      </p>
                    </div>
                    <StatusBadge status={c.status} />
                    <span className="text-xs tabular text-[var(--text-tertiary)] shrink-0 w-24 text-right">
                      {c.total_recipients?.toLocaleString() ?? 0} recipients
                    </span>
                    <span className="text-xs text-[var(--text-tertiary)] shrink-0 flex items-center gap-1 w-20">
                      <Clock size={11} />
                      {new Date(c.created_at).toLocaleDateString()}
                    </span>
                  </Link>
                </motion.div>
              ))}
              {!data?.recent_campaigns?.length && (
                <div className="py-10 text-center">
                  <p className="text-sm text-[var(--text-tertiary)]">No campaigns yet</p>
                  <Link to="/campaigns/new" className="mt-3 inline-flex">
                    <Button size="sm" leftIcon={<Plus size={13} />}>Create first campaign</Button>
                  </Link>
                </div>
              )}
            </div>
          )}
        </Card>

        {/* Recent activity feed */}
        <Card className="lg:col-span-2">
          <CardHeader
            title="Recent Activity"
            subtitle="Latest message events"
            icon={<Activity size={14} className="text-brand-500" />}
          />
          <div className="divide-y divide-[var(--border)]">
            {loading
              ? [...Array(5)].map((_, i) => <ActivityItem key={i} loading />)
              : (data?.recent_activity ?? []).map(item => (
                  <ActivityItem key={item.id} item={item} />
                ))
            }
            {!loading && !data?.recent_activity?.length && (
              <p className="py-8 text-center text-sm text-[var(--text-tertiary)]">No recent activity</p>
            )}
          </div>
        </Card>
      </div>
    </div>
  )
}
