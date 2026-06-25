import React, { useEffect, useState, useCallback } from 'react'
import { motion } from 'framer-motion'
import {
  Activity, Database, Cpu, Server, AlertTriangle, CheckCircle2,
  XCircle, RefreshCw, Clock, Users, MessageSquare, Wifi,
  HardDrive, Code2, Layers, Zap, AlertCircle,
} from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { EmptyState } from '../components/ui/EmptyState'
import { SkeletonTable } from '../components/ui/Skeleton'

function StatusDot({ status }) {
  const map = {
    healthy:        'bg-emerald-500',
    warning:        'bg-amber-500',
    critical:       'bg-red-500',
    error:          'bg-red-500',
    not_configured: 'bg-slate-400',
    unknown:        'bg-slate-400',
  }
  return (
    <span className={`inline-flex h-2.5 w-2.5 rounded-full ${map[status] ?? 'bg-slate-400'}`} />
  )
}

function StatusBadge({ status }) {
  const variant = {
    healthy:        'success',
    warning:        'warning',
    critical:       'danger',
    error:          'danger',
    not_configured: 'default',
    unknown:        'default',
  }[status] ?? 'default'

  return <Badge variant={variant} size="sm" className="capitalize">{status?.replace('_', ' ')}</Badge>
}

function MetricCard({ icon: Icon, label, value, sub, status, className = '' }) {
  const borderMap = {
    healthy:  'border-l-emerald-500',
    warning:  'border-l-amber-500',
    critical: 'border-l-red-500',
    error:    'border-l-red-500',
  }
  const border = status ? borderMap[status] ?? '' : ''

  return (
    <div className={`bg-[var(--surface)] rounded-xl border border-[var(--border)] border-l-4 ${border || 'border-l-transparent'} p-4 ${className}`}>
      <div className="flex items-start justify-between gap-2">
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--surface-2)] text-[var(--text-tertiary)] shrink-0">
          <Icon size={16} />
        </div>
        {status && <StatusDot status={status} />}
      </div>
      <p className="mt-3 text-2xl font-bold text-[var(--text-primary)] leading-none">{value ?? '—'}</p>
      <p className="mt-1 text-sm font-medium text-[var(--text-secondary)]">{label}</p>
      {sub && <p className="mt-0.5 text-xs text-[var(--text-tertiary)]">{sub}</p>}
    </div>
  )
}

function SectionCard({ title, icon: Icon, status, children }) {
  return (
    <Card>
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-2">
          <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-600/10 text-brand-600">
            <Icon size={14} />
          </div>
          <span className="text-sm font-semibold text-[var(--text-primary)]">{title}</span>
        </div>
        {status && <StatusBadge status={status} />}
      </div>
      {children}
    </Card>
  )
}

function KV({ label, value, mono = false }) {
  return (
    <div className="flex items-center justify-between py-1.5 border-b border-[var(--border)] last:border-0">
      <span className="text-xs text-[var(--text-tertiary)]">{label}</span>
      <span className={`text-xs font-medium text-[var(--text-primary)] ${mono ? 'font-mono' : ''}`}>
        {value ?? '—'}
      </span>
    </div>
  )
}

export default function MonitoringPage() {
  const { user }          = useAuth()
  const [data, setData]   = useState(null)
  const [loading, setLoad]= useState(true)
  const [lastRefresh, setLast] = useState(null)

  const load = useCallback(() => {
    setLoad(true)
    api.get('/system/health')
      .then(r => { setData(r.data); setLast(new Date()) })
      .catch(() => {})
      .finally(() => setLoad(false))
  }, [])

  useEffect(() => { load() }, [load])

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="System Monitoring" />
        <Card>
          <EmptyState icon={AlertTriangle} title="Admin access required" description="System monitoring is visible to administrators only." />
        </Card>
      </div>
    )
  }

  const { queue, database, twilio, system, platform } = data ?? {}

  const overallStatus = () => {
    const statuses = [queue?.status, database?.status, twilio?.status]
    if (statuses.some(s => s === 'critical' || s === 'error')) return 'critical'
    if (statuses.some(s => s === 'warning')) return 'warning'
    return 'healthy'
  }

  const score = () => {
    let s = 100
    if (queue?.status === 'warning')    s -= 15
    if (queue?.status === 'critical')   s -= 35
    if (database?.status !== 'healthy') s -= 25
    if (twilio?.status === 'warning')   s -= 10
    if (twilio?.status === 'not_configured') s -= 20
    if (system?.debug_mode)             s -= 10
    return Math.max(0, s)
  }

  const sc = data ? score() : null
  const scoreColor = sc >= 90 ? 'text-emerald-600' : sc >= 70 ? 'text-amber-600' : 'text-red-600'

  return (
    <div className="space-y-5">
      <PageHeader
        title="System Monitoring"
        subtitle={lastRefresh ? `Last refreshed ${lastRefresh.toLocaleTimeString()}` : 'Loading…'}
        actions={
          <Button size="sm" variant="secondary" onClick={load} loading={loading} leftIcon={<RefreshCw size={13} />}>
            Refresh
          </Button>
        }
      />

      {loading && !data ? (
        <SkeletonTable rows={4} />
      ) : (
        <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="space-y-5">
          {/* Health Score */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div className="col-span-2 sm:col-span-1 bg-[var(--surface)] rounded-xl border border-[var(--border)] p-4 flex flex-col items-center justify-center">
              <span className={`text-5xl font-black ${scoreColor}`}>{sc}</span>
              <span className="text-xs font-semibold text-[var(--text-tertiary)] mt-1 uppercase tracking-wide">Health Score</span>
              <StatusBadge status={overallStatus()} />
            </div>
            <MetricCard icon={Layers}       label="Pending Jobs"       value={queue?.pending_jobs}       status={queue?.status} />
            <MetricCard icon={AlertTriangle} label="Failed Jobs"       value={queue?.failed_jobs}        status={queue?.failed_jobs > 5 ? 'warning' : 'healthy'} />
            <MetricCard icon={MessageSquare} label="Messages Today"    value={platform?.messages_today}  status={platform?.failed_messages_today > 20 ? 'warning' : 'healthy'} />
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
            {/* Queue */}
            <SectionCard title="Queue" icon={Layers} status={queue?.status}>
              <KV label="Pending Jobs"       value={queue?.pending_jobs} />
              <KV label="Total Failed"       value={queue?.failed_jobs} />
              <KV label="Failed Last Hour"   value={queue?.failed_last_hour} />
              {queue?.recent_failures?.length > 0 && (
                <div className="mt-3">
                  <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-2">Recent Failures</p>
                  <div className="space-y-1.5">
                    {queue.recent_failures.map((f, i) => (
                      <div key={i} className="rounded-lg bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 p-2">
                        <p className="text-xs font-medium text-red-700 dark:text-red-400">{f.job}</p>
                        <p className="text-[10px] text-red-600/70 mt-0.5 truncate">{f.error}</p>
                        <p className="text-[10px] text-red-500/60 mt-0.5">{new Date(f.failed_at).toLocaleString()}</p>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </SectionCard>

            {/* Database */}
            <SectionCard title="Database" icon={Database} status={database?.status}>
              <KV label="Connection"         value={database?.connection} />
              <KV label="Response Time"      value={database?.response_ms != null ? `${database.response_ms} ms` : null} />
              <KV label="Total Messages"     value={database?.total_messages?.toLocaleString()} />
              <KV label="Status"             value={database?.status ?? database?.error} />
            </SectionCard>

            {/* Twilio */}
            <SectionCard title="Twilio" icon={Wifi} status={twilio?.status}>
              <KV label="Configured"         value={twilio?.configured ? 'Yes' : 'No'} />
              <KV label="Account SID"        value={twilio?.account_sid} mono />
              <KV label="Messaging Service"  value={twilio?.messaging_service} mono />
              <KV label="Failed Last Hour"   value={twilio?.failed_last_hour} />
              <KV label="Sent Last 24h"      value={twilio?.sent_last_24h} />
            </SectionCard>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {/* System Info */}
            <SectionCard title="System Information" icon={Server}>
              <KV label="PHP Version"     value={system?.php_version} mono />
              <KV label="Laravel Version" value={system?.laravel_version} mono />
              <KV label="Environment"     value={system?.environment} />
              <KV label="Debug Mode"      value={system?.debug_mode ? '⚠️ Enabled' : 'Disabled'} />
              <KV label="Memory Used"     value={system?.memory_used_mb != null ? `${system.memory_used_mb} MB` : null} />
              <KV label="Memory Limit"    value={system?.memory_limit_mb != null ? `${system.memory_limit_mb} MB` : null} />
              <KV label="Timezone"        value={system?.timezone} />
              <KV label="Cache Driver"    value={system?.cache_driver} />
              <KV label="Queue Driver"    value={system?.queue_driver} />
              <KV label="Mail Driver"     value={system?.mail_driver} />
            </SectionCard>

            {/* Platform Stats */}
            <SectionCard title="Platform Stats" icon={Activity}>
              <KV label="Active Users (24h)"       value={platform?.active_users_24h} />
              <KV label="Total Users"              value={platform?.total_users} />
              <KV label="Messages Today"           value={platform?.messages_today} />
              <KV label="Failed Messages Today"    value={platform?.failed_messages_today} />
              <KV label="Cost Today"               value={platform?.cost_today != null ? `$${platform.cost_today.toFixed(4)}` : null} />
              {platform?.last_successful_campaign && (
                <>
                  <div className="mt-3 mb-1">
                    <p className="text-xs font-semibold text-[var(--text-tertiary)]">Last Successful Campaign</p>
                  </div>
                  <KV label="Name"       value={platform.last_successful_campaign.name} />
                  <KV label="Recipients" value={platform.last_successful_campaign.total_recipients} />
                  <KV label="Completed"  value={new Date(platform.last_successful_campaign.updated_at).toLocaleString()} />
                </>
              )}
            </SectionCard>
          </div>
        </motion.div>
      )}
    </div>
  )
}
