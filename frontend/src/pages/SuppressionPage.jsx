import React, { useEffect, useState, useCallback } from 'react'
import { AlertTriangle, Search, Plus, Trash2, Phone, RefreshCw, ShieldOff } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Pagination } from '../components/ui/Pagination'
import { EmptyState } from '../components/ui/EmptyState'
import { Spinner } from '../components/ui/Spinner'
import { MetricCard } from '../components/charts/MetricCard'

const REASON_LABELS = {
  blacklisted:   'Blacklisted',
  failed:        'Delivery Failed',
  manual:        'Manual',
  opted_out:     'Opted Out',
  spam:          'Spam Report',
  undelivered:   'Undelivered',
}

function ReasonBadge({ reason }) {
  const colors = {
    blacklisted:  'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
    failed:       'bg-orange-50 text-orange-700 dark:bg-orange-950 dark:text-orange-400',
    manual:       'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    opted_out:    'bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-400',
    spam:         'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
    undelivered:  'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
  }
  return (
    <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[reason] ?? 'bg-gray-100 text-gray-700'}`}>
      {REASON_LABELS[reason] ?? reason}
    </span>
  )
}

function AddNumberModal({ onClose, onAdded }) {
  const { toast } = useToast()
  const [form, setForm] = useState({ phone: '', name: '', reason: 'manual', error_code: '' })
  const [saving, setSaving] = useState(false)

  const handleSubmit = async e => {
    e.preventDefault()
    setSaving(true)
    try {
      await api.post('/suppression', form)
      toast.success('Number added to suppression list')
      onAdded()
      onClose()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Failed to add number')
    } finally {
      setSaving(false)
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div className="w-full max-w-md bg-[var(--surface)] rounded-2xl shadow-[var(--shadow-xl)] border border-[var(--border)]">
        <div className="px-6 py-4 border-b border-[var(--border)]">
          <h2 className="text-base font-semibold text-[var(--text-primary)]">Add to Suppression List</h2>
          <p className="text-xs text-[var(--text-tertiary)] mt-0.5">Suppress a phone number from receiving SMS messages</p>
        </div>
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Phone Number <span className="text-red-500">*</span></label>
            <Input
              placeholder="+447..."
              value={form.phone}
              onChange={e => setForm(f => ({ ...f, phone: e.target.value }))}
              required
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Name (optional)</label>
            <Input
              placeholder="Contact name"
              value={form.name}
              onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Reason</label>
            <Select value={form.reason} onChange={e => setForm(f => ({ ...f, reason: e.target.value }))}>
              {Object.entries(REASON_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </Select>
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Error Code (optional)</label>
            <Input
              placeholder="e.g. 30007"
              value={form.error_code}
              onChange={e => setForm(f => ({ ...f, error_code: e.target.value }))}
            />
          </div>
          <div className="flex items-center justify-end gap-2 pt-2">
            <Button variant="ghost" type="button" onClick={onClose}>Cancel</Button>
            <Button type="submit" loading={saving}>Add to Suppression</Button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default function SuppressionPage() {
  const { user }             = useAuth()
  const { toast }            = useToast()
  const [list, setList]      = useState(null)
  const [stats, setStats]    = useState(null)
  const [loading, setLoading]= useState(true)
  const [page, setPage]      = useState(1)
  const [search, setSearch]  = useState('')
  const [reasonFilter, setReasonFilter] = useState('')
  const [showAdd, setShowAdd]= useState(false)
  const [removing, setRemoving] = useState(null)

  const load = useCallback(async () => {
    setLoading(true)
    try {
      const [l, s] = await Promise.all([
        api.get('/suppression', { params: { page, search: search || undefined, reason: reasonFilter || undefined } }),
        api.get('/suppression/stats'),
      ])
      setList(l.data)
      setStats(s.data)
    } catch {
      toast.error('Failed to load suppression list')
    } finally {
      setLoading(false)
    }
  }, [page, search, reasonFilter])

  useEffect(() => { load() }, [load])

  const handleRemove = async (id) => {
    if (!confirm('Remove this number from the suppression list?')) return
    setRemoving(id)
    try {
      await api.delete(`/suppression/${id}`)
      toast.success('Number removed from suppression list')
      load()
    } catch {
      toast.error('Failed to remove number')
    } finally {
      setRemoving(null)
    }
  }

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Suppression List" />
        <Card><EmptyState icon={ShieldOff} title="Admin access required" description="The suppression list is available to administrators only." /></Card>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {showAdd && <AddNumberModal onClose={() => setShowAdd(false)} onAdded={load} />}

      <PageHeader
        title="Suppression List"
        subtitle="Numbers excluded from all SMS campaigns"
        action={
          <Button leftIcon={<Plus size={14} />} onClick={() => setShowAdd(true)}>
            Add Number
          </Button>
        }
      />

      {/* Stats */}
      {stats && (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <MetricCard label="Total Suppressed" value={stats.total?.toLocaleString() ?? '—'}        icon={ShieldOff}    iconColor="bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400" />
          <MetricCard label="Blacklisted"       value={stats.by_reason?.blacklisted?.toLocaleString() ?? '0'} icon={AlertTriangle} iconColor="bg-orange-50 text-orange-600 dark:bg-orange-950 dark:text-orange-400" />
          <MetricCard label="Opted Out"         value={stats.by_reason?.opted_out?.toLocaleString() ?? '0'}   icon={Phone}        iconColor="bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400" />
          <MetricCard label="Failed Delivery"   value={stats.by_reason?.failed?.toLocaleString() ?? '0'}      icon={RefreshCw}    iconColor="bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400" />
        </div>
      )}

      {/* Reason breakdown */}
      {stats?.by_reason && Object.keys(stats.by_reason).length > 0 && (
        <Card>
          <CardHeader title="Suppression by Reason" />
          <div className="flex flex-wrap gap-3">
            {Object.entries(stats.by_reason).map(([reason, count]) => (
              <button
                key={reason}
                onClick={() => setReasonFilter(prev => prev === reason ? '' : reason)}
                className={`flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors ${
                  reasonFilter === reason
                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                    : 'border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-secondary)] hover:border-brand-300'
                }`}
              >
                <ReasonBadge reason={reason} />
                <span className="font-semibold">{Number(count).toLocaleString()}</span>
              </button>
            ))}
          </div>
        </Card>
      )}

      {/* Table */}
      <Card padding={false}>
        <div className="flex items-center gap-3 px-5 py-4 border-b border-[var(--border)]">
          <div className="relative flex-1 max-w-sm">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-tertiary)]" />
            <Input
              placeholder="Search phone or name…"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
              className="pl-8"
            />
          </div>
          <Select value={reasonFilter} onChange={e => { setReasonFilter(e.target.value); setPage(1) }} className="w-44">
            <option value="">All reasons</option>
            {Object.entries(REASON_LABELS).map(([v, l]) => <option key={v} value={v}>{l}</option>)}
          </Select>
          <Button variant="ghost" size="sm" leftIcon={<RefreshCw size={12} />} onClick={load}>Refresh</Button>
        </div>

        {loading ? (
          <div className="flex items-center justify-center py-16">
            <Spinner size="lg" />
          </div>
        ) : list?.data?.length === 0 ? (
          <EmptyState
            icon={ShieldOff}
            title="No suppressed numbers"
            description={search || reasonFilter ? 'No numbers match your filters.' : 'The suppression list is empty.'}
          />
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                    {['Phone', 'Name', 'Reason', 'Error Code', 'Failure Count', 'Added By', 'Last Failed', 'Added'].map(h => (
                      <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide whitespace-nowrap">{h}</th>
                    ))}
                    <th className="px-4 py-3" />
                  </tr>
                </thead>
                <tbody className="divide-y divide-[var(--border)]">
                  {list.data.map(item => (
                    <tr key={item.id} className="hover:bg-[var(--surface-2)] transition-colors">
                      <td className="px-4 py-3 text-sm font-mono font-medium text-[var(--text-primary)]">{item.phone}</td>
                      <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{item.name ?? '—'}</td>
                      <td className="px-4 py-3"><ReasonBadge reason={item.reason} /></td>
                      <td className="px-4 py-3 text-sm font-mono text-[var(--text-secondary)]">{item.error_code ?? '—'}</td>
                      <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{item.failure_count ?? 0}</td>
                      <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{item.added_by?.name ?? 'System'}</td>
                      <td className="px-4 py-3 text-xs text-[var(--text-tertiary)]">
                        {item.last_failed_at ? new Date(item.last_failed_at).toLocaleDateString() : '—'}
                      </td>
                      <td className="px-4 py-3 text-xs text-[var(--text-tertiary)]">{new Date(item.created_at).toLocaleDateString()}</td>
                      <td className="px-4 py-3">
                        <button
                          onClick={() => handleRemove(item.id)}
                          disabled={removing === item.id}
                          className="flex items-center justify-center h-7 w-7 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-500 transition-colors disabled:opacity-50"
                        >
                          {removing === item.id ? <Spinner size="xs" /> : <Trash2 size={13} />}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Pagination meta={list.meta} onPageChange={setPage} />
          </>
        )}
      </Card>
    </div>
  )
}
