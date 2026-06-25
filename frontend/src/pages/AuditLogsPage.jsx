import React, { useEffect, useState, useCallback } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Search, Shield, ShieldAlert, Filter, X, ChevronRight, Clock, Globe, Monitor } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Input } from '../components/ui/Input'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'
import { Badge } from '../components/ui/Badge'

const ACTION_VARIANT = {
  create: 'success',
  login:  'success',
  import: 'success',
  send:   'brand',
  update: 'warning',
  edit:   'warning',
  bulk:   'warning',
  export: 'default',
  delete: 'danger',
  logout: 'danger',
  security: 'danger',
}

function actionVariant(action) {
  for (const [key, variant] of Object.entries(ACTION_VARIANT)) {
    if (action?.includes(key)) return variant
  }
  return 'default'
}

function formatAction(action) {
  return action?.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) ?? '—'
}

function DetailDrawer({ log, onClose }) {
  if (!log) return null
  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }}
        className="fixed inset-0 z-50 flex"
        onClick={onClose}
      >
        <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" />
        <motion.div
          initial={{ x: '100%' }} animate={{ x: 0 }} exit={{ x: '100%' }}
          transition={{ type: 'spring', damping: 28, stiffness: 250 }}
          className="ml-auto relative w-full max-w-lg h-full bg-[var(--surface)] shadow-2xl overflow-y-auto"
          onClick={e => e.stopPropagation()}
        >
          <div className="sticky top-0 flex items-center justify-between px-6 py-4 border-b border-[var(--border)] bg-[var(--surface)]">
            <div>
              <Badge variant={actionVariant(log.action)} size="sm">{formatAction(log.action)}</Badge>
              <p className="text-xs text-[var(--text-tertiary)] mt-1">{new Date(log.created_at).toLocaleString()}</p>
            </div>
            <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)]">
              <X size={16} />
            </button>
          </div>

          <div className="p-6 space-y-5">
            {/* Meta */}
            <div className="rounded-xl border border-[var(--border)] divide-y divide-[var(--border)]">
              {[
                ['User',        log.user?.name ?? 'System'],
                ['Entity',      log.entity_type ? `${log.entity_type}${log.entity_id ? ` #${log.entity_id}` : ''}` : '—'],
                ['IP Address',  log.ip_address ?? '—'],
                ['Date',        new Date(log.created_at).toLocaleString()],
              ].map(([label, value]) => (
                <div key={label} className="flex items-start justify-between gap-4 px-4 py-2.5">
                  <span className="text-xs text-[var(--text-tertiary)] shrink-0">{label}</span>
                  <span className="text-xs font-medium text-[var(--text-primary)] text-right font-mono break-all">{value}</span>
                </div>
              ))}
            </div>

            {/* Browser / User Agent */}
            {log.user_agent && (
              <div>
                <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-2 flex items-center gap-1.5"><Monitor size={11} />Browser</p>
                <p className="text-xs font-mono text-[var(--text-secondary)] bg-[var(--surface-2)] rounded-lg p-3 break-all">{log.user_agent}</p>
              </div>
            )}

            {/* Before Values */}
            {log.old_values && Object.keys(log.old_values).length > 0 && (
              <div>
                <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-2">Before</p>
                <div className="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/10 divide-y divide-amber-100 dark:divide-amber-800">
                  {Object.entries(log.old_values).map(([k, v]) => (
                    <div key={k} className="flex items-start justify-between gap-4 px-4 py-2">
                      <span className="text-xs text-amber-700 dark:text-amber-400 shrink-0 font-mono">{k}</span>
                      <span className="text-xs text-amber-800 dark:text-amber-300 text-right break-all max-w-[60%]">
                        {typeof v === 'object' ? JSON.stringify(v) : String(v ?? '—')}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* After Values */}
            {log.new_values && Object.keys(log.new_values).length > 0 && (
              <div>
                <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-2">After</p>
                <div className="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/10 divide-y divide-emerald-100 dark:divide-emerald-800">
                  {Object.entries(log.new_values).map(([k, v]) => (
                    <div key={k} className="flex items-start justify-between gap-4 px-4 py-2">
                      <span className="text-xs text-emerald-700 dark:text-emerald-400 shrink-0 font-mono">{k}</span>
                      <span className="text-xs text-emerald-800 dark:text-emerald-300 text-right break-all max-w-[60%]">
                        {typeof v === 'object' ? JSON.stringify(v) : String(v ?? '—')}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  )
}

const ACTION_CATEGORIES = ['create', 'update', 'delete', 'login', 'logout', 'send', 'import', 'export', 'security', 'bulk']

export default function AuditLogsPage() {
  const { user }              = useAuth()
  const [data, setData]       = useState(null)
  const [search, setSearch]   = useState('')
  const [action, setAction]   = useState('')
  const [entityType, setEType]= useState('')
  const [dateFrom, setFrom]   = useState('')
  const [dateTo, setTo]       = useState('')
  const [page, setPage]       = useState(1)
  const [loading, setLoad]    = useState(true)
  const [selected, setSelected] = useState(null)

  const load = useCallback((p = 1) => {
    setLoad(true)
    const params = { page: p, per_page: 50 }
    if (search)     params.search      = search
    if (action)     params.action      = action
    if (entityType) params.entity_type = entityType
    if (dateFrom)   params.date_from   = dateFrom
    if (dateTo)     params.date_to     = dateTo
    api.get('/audit-logs', { params })
      .then(r => setData(r.data))
      .finally(() => setLoad(false))
  }, [search, action, entityType, dateFrom, dateTo])

  useEffect(() => { load(1); setPage(1) }, [search, action, entityType, dateFrom, dateTo])
  useEffect(() => { if (page > 1) load(page) }, [page])

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Audit Logs" />
        <Card><EmptyState icon={ShieldAlert} title="Admin access required" description="Audit logs are visible to administrators only." /></Card>
      </div>
    )
  }

  const hasFilters = search || action || entityType || dateFrom || dateTo
  const clearFilters = () => { setSearch(''); setAction(''); setEType(''); setFrom(''); setTo('') }

  return (
    <div className="space-y-5">
      <PageHeader title="Audit Logs" subtitle={`${data?.meta?.total ?? 0} total entries`} icon={<Shield size={18} />} />

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <Input
          placeholder="Search action, entity, IP, user…"
          leftIcon={<Search size={14} />}
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="w-64"
        />

        <select
          value={action}
          onChange={e => setAction(e.target.value)}
          className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
          <option value="">All actions</option>
          {ACTION_CATEGORIES.map(a => (
            <option key={a} value={a} className="capitalize">{a.charAt(0).toUpperCase() + a.slice(1)}</option>
          ))}
        </select>

        <select
          value={entityType}
          onChange={e => setEType(e.target.value)}
          className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
          <option value="">All entities</option>
          {['Contact', 'Campaign', 'User', 'Segment', 'Tag', 'Template', 'Security'].map(e => (
            <option key={e} value={e}>{e}</option>
          ))}
        </select>

        <div className="flex items-center gap-1.5">
          <input type="date" value={dateFrom} onChange={e => setFrom(e.target.value)}
            className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500" />
          <span className="text-xs text-[var(--text-tertiary)]">–</span>
          <input type="date" value={dateTo} onChange={e => setTo(e.target.value)}
            className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500" />
        </div>

        {hasFilters && (
          <button onClick={clearFilters} className="flex items-center gap-1.5 h-9 px-3 text-sm text-[var(--text-tertiary)] hover:text-[var(--text-primary)] rounded-lg hover:bg-[var(--surface-2)] transition-colors">
            <X size={13} /> Clear
          </button>
        )}
      </div>

      {loading ? (
        <SkeletonTable rows={8} />
      ) : !data?.data?.length ? (
        <Card><EmptyState icon={Shield} title="No audit logs" description="No activity matches your filters." /></Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Action', 'Entity', 'User', 'IP Address', 'Time', ''].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {data.data.map((log, i) => (
                <motion.tr
                  key={log.id}
                  className="hover:bg-[var(--surface-2)] transition-colors cursor-pointer"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: i * 0.015 }}
                  onClick={() => setSelected(log)}
                >
                  <td className="px-4 py-3">
                    <Badge variant={actionVariant(log.action)} size="sm">{formatAction(log.action)}</Badge>
                  </td>
                  <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">
                    {log.entity_type
                      ? `${log.entity_type.split('\\').pop()}${log.entity_id ? ` #${log.entity_id}` : ''}`
                      : '—'}
                  </td>
                  <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">
                    {log.user?.name ?? <span className="text-[var(--text-tertiary)] italic">System</span>}
                  </td>
                  <td className="px-4 py-3 text-sm font-mono text-[var(--text-tertiary)]">
                    {log.ip_address ?? '—'}
                  </td>
                  <td className="px-4 py-3 text-xs text-[var(--text-tertiary)] whitespace-nowrap">
                    {new Date(log.created_at).toLocaleString()}
                  </td>
                  <td className="px-4 py-3">
                    <ChevronRight size={13} className="text-[var(--text-tertiary)]" />
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </Card>
      )}

      <DetailDrawer log={selected} onClose={() => setSelected(null)} />
    </div>
  )
}
