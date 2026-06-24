import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { Search, Shield, ShieldAlert } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Input } from '../components/ui/Input'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'
import { Badge } from '../components/ui/Badge'

const ACTION_COLOR = {
  create: 'success',
  login:  'success',
  send:   'brand',
  update: 'warning',
  edit:   'warning',
  delete: 'danger',
  logout: 'danger',
}

function actionBadgeVariant(action) {
  for (const [key, variant] of Object.entries(ACTION_COLOR)) {
    if (action.includes(key)) return variant
  }
  return 'default'
}

function formatAction(action) {
  return action.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

export default function AuditLogsPage() {
  const { user }           = useAuth()
  const [data, setData]    = useState(null)
  const [search, setSearch] = useState('')
  const [page, setPage]    = useState(1)
  const [loading, setLoad] = useState(true)

  const load = (p = 1) => {
    setLoad(true)
    api.get('/audit-logs', { params: { page: p, search } })
      .then(r => setData(r.data))
      .finally(() => setLoad(false))
  }

  useEffect(() => { load(1); setPage(1) }, [search])
  useEffect(() => { if (page > 1) load(page) }, [page])

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Audit Logs" />
        <Card>
          <EmptyState icon={ShieldAlert} title="Admin access required" description="Audit logs are visible to administrators only." />
        </Card>
      </div>
    )
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Audit Logs"
        subtitle={`${data?.meta?.total ?? 0} total entries`}
      />

      <Input
        placeholder="Search by action, entity, or user…"
        leftIcon={<Search size={14} />}
        value={search}
        onChange={e => setSearch(e.target.value)}
        className="sm:w-80"
      />

      {loading ? (
        <SkeletonTable rows={8} />
      ) : !data?.data?.length ? (
        <Card>
          <EmptyState icon={Shield} title="No audit logs" description="No activity has been recorded yet." />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Action', 'Entity', 'User', 'IP Address', 'Time'].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {data.data.map((log, i) => (
                <motion.tr
                  key={log.id}
                  className="hover:bg-[var(--surface-2)] transition-colors"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: i * 0.02 }}
                >
                  <td className="px-4 py-3">
                    <Badge variant={actionBadgeVariant(log.action)} size="sm">
                      {formatAction(log.action)}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">
                    {log.entity_type
                      ? `${log.entity_type.split('\\').pop()}${log.entity_id ? ` #${log.entity_id}` : ''}`
                      : '—'}
                  </td>
                  <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">
                    {log.user?.name ?? <span className="text-[var(--text-tertiary)]">System</span>}
                  </td>
                  <td className="px-4 py-3 text-sm font-mono text-[var(--text-tertiary)]">
                    {log.ip_address ?? '—'}
                  </td>
                  <td className="px-4 py-3 text-xs text-[var(--text-tertiary)] whitespace-nowrap">
                    {new Date(log.created_at).toLocaleString()}
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </Card>
      )}
    </div>
  )
}
