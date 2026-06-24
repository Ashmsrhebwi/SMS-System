import React, { useEffect, useState, useCallback } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Send, RotateCcw, Copy, Trash2, Download,
  CheckCircle2, XCircle, MousePointerClick, Users, Clock,
  Target, MessageSquare, ChevronRight
} from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { StatusBadge } from '../components/ui/Badge'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonCard } from '../components/ui/Skeleton'
import { Spinner } from '../components/ui/Spinner'
import { staggerContainer, staggerItem } from '../lib/animations'
import { cn } from '../lib/cn'

// Stat card with animated progress bar
function StatCard({ icon: Icon, label, value, rate, total, barColor, iconColor }) {
  const pct = total > 0 && value != null ? Math.min(100, Math.round((value / total) * 100)) : 0
  return (
    <div className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-4 shadow-[var(--shadow-sm)] overflow-hidden">
      <div className="flex items-center gap-2 mb-3">
        <div className={`flex h-7 w-7 items-center justify-center rounded-lg ${iconColor}`}>
          <Icon size={14} />
        </div>
        <span className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{label}</span>
      </div>
      <p className="text-2xl font-bold text-[var(--text-primary)] tabular mb-1">{(value ?? 0).toLocaleString()}</p>
      {rate !== undefined && (
        <p className="text-xs text-[var(--text-secondary)] mb-3">{rate}%</p>
      )}
      {total > 0 && (
        <div className="h-1 rounded-full bg-[var(--surface-2)] overflow-hidden">
          <motion.div
            className={`h-full rounded-full ${barColor}`}
            initial={{ width: 0 }}
            animate={{ width: `${pct}%` }}
            transition={{ duration: 0.9, ease: 'easeOut', delay: 0.1 }}
          />
        </div>
      )}
    </div>
  )
}

function SendingIndicator() {
  return (
    <span className="relative flex h-2.5 w-2.5">
      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75" />
      <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-brand-500" />
    </span>
  )
}

// Create segment from clicked/non-clicked modal
function CreateSegmentModal({ campaignId, type, count, onClose, onCreated }) {
  const { toast } = useToast()
  const [name, setName] = useState('')
  const [saving, setSaving] = useState(false)

  const handleSubmit = async e => {
    e.preventDefault()
    setSaving(true)
    try {
      const r = await api.post(`/campaigns/${campaignId}/create-segment-from-clicks`, { type, name })
      toast.success(`Segment "${r.data.segment?.name}" created with ${r.data.segment?.contact_count ?? count} contacts`)
      onCreated()
      onClose()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Failed to create segment')
    } finally {
      setSaving(false)
    }
  }

  const label = type === 'clicked' ? 'Clicked' : 'Non-Clicked'

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div className="w-full max-w-md bg-[var(--surface)] rounded-2xl shadow-[var(--shadow-xl)] border border-[var(--border)]">
        <div className="px-6 py-4 border-b border-[var(--border)]">
          <h2 className="text-base font-semibold text-[var(--text-primary)]">Create Segment from {label} Contacts</h2>
          <p className="text-xs text-[var(--text-tertiary)] mt-0.5">{count?.toLocaleString()} contacts will be added to this segment</p>
        </div>
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Segment Name <span className="text-red-500">*</span></label>
            <input
              type="text"
              className="w-full px-3 py-2 rounded-lg border border-[var(--border)] bg-[var(--surface)] text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
              placeholder={`e.g. Campaign ${campaignId} ${label}`}
              value={name}
              onChange={e => setName(e.target.value)}
              required
              autoFocus
            />
          </div>
          <div className="flex items-center justify-end gap-2 pt-2">
            <Button variant="ghost" type="button" onClick={onClose}>Cancel</Button>
            <Button type="submit" loading={saving} leftIcon={<Target size={14} />}>Create Segment</Button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Contacts sub-table (clicked or non-clicked)
function ContactsSubTable({ campaignId, type, stats }) {
  const { toast }           = useToast()
  const [data, setData]     = useState(null)
  const [page, setPage]     = useState(1)
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)

  const count = type === 'clicked' ? stats?.clicked : (stats?.delivered - stats?.clicked)

  const load = useCallback(() => {
    setLoading(true)
    const endpoint = type === 'clicked' ? 'clicked-contacts' : 'non-clicked-contacts'
    api.get(`/campaigns/${campaignId}/${endpoint}`, { params: { page } })
      .then(r => setData(r.data))
      .finally(() => setLoading(false))
  }, [campaignId, type, page])

  useEffect(() => { load() }, [load])

  return (
    <div className="space-y-3">
      {showModal && (
        <CreateSegmentModal
          campaignId={campaignId}
          type={type}
          count={count}
          onClose={() => setShowModal(false)}
          onCreated={() => {}}
        />
      )}

      <div className="flex items-center justify-between">
        <p className="text-sm text-[var(--text-secondary)]">
          {count != null ? <><span className="font-semibold text-[var(--text-primary)]">{Number(count).toLocaleString()}</span> contacts</> : '—'}
        </p>
        {count > 0 && (
          <Button
            size="sm"
            variant="secondary"
            leftIcon={<Target size={13} />}
            onClick={() => setShowModal(true)}
          >
            Create Segment
          </Button>
        )}
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-10">
          <Spinner size="lg" />
        </div>
      ) : data?.data?.length === 0 ? (
        <div className="py-10 text-center text-sm text-[var(--text-tertiary)]">No contacts found</div>
      ) : (
        <div className="rounded-xl border border-[var(--border)] overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Contact', 'Phone', 'Country', 'Status', type === 'clicked' ? 'Clicks' : 'Delivered At'].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {data.data.map(m => (
                <tr key={m.id} className="hover:bg-[var(--surface-2)] transition-colors">
                  <td className="px-4 py-3">
                    {m.contact?.id ? (
                      <Link to={`/contacts/${m.contact.id}`} className="text-sm font-medium text-brand-600 hover:underline">
                        {m.contact.name}
                      </Link>
                    ) : (
                      <span className="text-sm text-[var(--text-tertiary)] italic">Deleted</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{m.contact?.phone ?? '—'}</td>
                  <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{m.contact?.country ?? '—'}</td>
                  <td className="px-4 py-3"><StatusBadge status={m.status} /></td>
                  <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">
                    {type === 'clicked' ? (m.click?.click_count ?? 0) : (m.updated_at ? new Date(m.updated_at).toLocaleString() : '—')}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={data.meta} onPageChange={setPage} />
        </div>
      )}
    </div>
  )
}

export default function CampaignDetailPage() {
  const { id }    = useParams()
  const navigate  = useNavigate()
  const { toast } = useToast()

  const [data, setData]         = useState(null)
  const [messages, setMessages] = useState(null)
  const [msgPage, setMsgPage]   = useState(1)
  const [loading, setLoading]   = useState(true)
  const [actionLoading, setAL]  = useState('')
  const [activeTab, setTab]     = useState('messages')
  const [msgFilter, setMsgFilter] = useState('')

  const load = () => {
    setLoading(true)
    Promise.all([
      api.get(`/campaigns/${id}`),
      api.get(`/campaigns/${id}/messages`, { params: { page: msgPage } }),
    ]).then(([c, m]) => { setData(c.data); setMessages(m.data) })
      .finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [id])

  useEffect(() => {
    if (!loading) {
      const params = { page: msgPage }
      if (msgFilter) params.status = msgFilter
      api.get(`/campaigns/${id}/messages`, { params }).then(r => setMessages(r.data))
    }
  }, [msgPage, msgFilter])

  // Auto-refresh while sending
  useEffect(() => {
    if (data?.campaign?.status !== 'sending') return
    const t = setInterval(() => {
      api.get(`/campaigns/${id}`).then(r => setData(r.data))
    }, 8000)
    return () => clearInterval(t)
  }, [data?.campaign?.status])

  const doAction = async (endpoint, successMsg, method = 'post') => {
    setAL(endpoint)
    try {
      await api[method](`/campaigns/${id}/${endpoint}`)
      toast.success(successMsg)
      load()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Action failed.')
    } finally {
      setAL('')
    }
  }

  const handleDelete = async () => {
    if (!confirm('Delete this campaign? This cannot be undone.')) return
    try {
      await api.delete(`/campaigns/${id}`)
      toast.success('Campaign deleted')
      navigate('/campaigns')
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Delete failed.')
    }
  }

  if (loading) {
    return (
      <div className="space-y-6">
        <div className="h-8 w-64 skeleton rounded-lg" />
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
          {[...Array(4)].map((_, i) => <SkeletonCard key={i} />)}
        </div>
      </div>
    )
  }

  if (!data) return null

  const { campaign, stats } = data
  const canSend   = ['draft', 'scheduled'].includes(campaign.status)
  const isSending = campaign.status === 'sending'
  const hasSent   = ['sent', 'sending', 'completed'].includes(campaign.status)

  const tabs = [
    { id: 'messages',    label: 'All Messages', count: stats.total },
    { id: 'clicked',     label: 'Clicked',      count: stats.clicked },
    { id: 'non_clicked', label: 'Not Clicked',  count: stats.delivered != null && stats.clicked != null ? stats.delivered - stats.clicked : null },
  ]

  return (
    <div className="space-y-6">
      <PageHeader
        title={campaign.name}
        subtitle={`Created by ${campaign.creator?.name ?? 'Unknown'} · ${new Date(campaign.created_at).toLocaleDateString()}`}
        breadcrumbs={[{ label: 'Campaigns', href: '/campaigns' }, { label: campaign.name }]}
        action={
          <div className="flex items-center gap-2 flex-wrap">
            {canSend && (
              <Button
                leftIcon={<Send size={14} />}
                loading={actionLoading === 'send'}
                onClick={() => doAction('send', 'Campaign is being sent!')}
              >
                Send Now
              </Button>
            )}
            <Button
              variant="secondary"
              leftIcon={<Copy size={14} />}
              loading={actionLoading === 'duplicate'}
              onClick={() => doAction('duplicate', 'Campaign duplicated')}
            >
              Duplicate
            </Button>
            <a href={`/api/v1/campaigns/${id}/export`} target="_blank" rel="noreferrer">
              <Button variant="secondary" leftIcon={<Download size={14} />}>Export</Button>
            </a>
            {!isSending && (
              <Button variant="danger" leftIcon={<Trash2 size={14} />} onClick={handleDelete}>
                Delete
              </Button>
            )}
          </div>
        }
      />

      {/* Status row */}
      <div className="flex items-center gap-3 -mt-2">
        {isSending ? (
          <span className="inline-flex items-center gap-2 rounded-full bg-brand-50 border border-brand-200 dark:bg-brand-950 dark:border-brand-800 px-3 py-1 text-xs font-semibold text-brand-700 dark:text-brand-300">
            <SendingIndicator />
            Sending…
          </span>
        ) : (
          <StatusBadge status={campaign.status} />
        )}
        {campaign.scheduled_at && (
          <span className="text-xs text-[var(--text-tertiary)] flex items-center gap-1">
            <Clock size={11} />
            Scheduled: {new Date(campaign.scheduled_at).toLocaleString()}
          </span>
        )}
        {isSending && (
          <span className="text-xs text-[var(--text-tertiary)]">Auto-refreshing every 8 s</span>
        )}
      </div>

      {/* Message body */}
      <Card>
        <CardHeader title="Message" subtitle="SMS body sent to recipients" />
        <p className="text-sm text-[var(--text-primary)] font-mono whitespace-pre-wrap leading-relaxed bg-[var(--surface-2)] rounded-lg px-4 py-3">
          {campaign.message_body}
        </p>
      </Card>

      {/* Stats */}
      <motion.div
        className="grid grid-cols-2 gap-4 sm:grid-cols-4"
        variants={staggerContainer}
        initial="initial"
        animate="animate"
      >
        {[
          { icon: Users, label: 'Total', value: stats.total, barColor: 'bg-gray-300 dark:bg-gray-600', iconColor: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' },
          { icon: CheckCircle2, label: 'Delivered', value: stats.delivered, rate: stats.delivery_rate, barColor: 'bg-emerald-500', iconColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' },
          { icon: XCircle, label: 'Failed', value: stats.failed, rate: stats.failure_rate, barColor: 'bg-red-500', iconColor: 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400' },
          { icon: MousePointerClick, label: 'Clicked', value: stats.clicked, rate: stats.click_rate, barColor: 'bg-brand-500', iconColor: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400' },
        ].map((s, i) => (
          <motion.div key={s.label} variants={staggerItem}>
            <StatCard {...s} total={stats.total} />
          </motion.div>
        ))}
      </motion.div>

      {/* Resend failed */}
      {stats.failed > 0 && (
        <Button
          variant="secondary"
          leftIcon={<RotateCcw size={14} />}
          loading={actionLoading === 'resend-failed'}
          onClick={() => doAction('resend-failed', `Re-queued ${stats.failed} failed messages`)}
        >
          Re-queue {stats.failed} Failed Messages
        </Button>
      )}

      {/* Tab navigation */}
      <div className="flex items-center gap-1 border-b border-[var(--border)]">
        {tabs.map(t => (
          <button
            key={t.id}
            onClick={() => { setTab(t.id); setMsgPage(1) }}
            className={cn(
              'flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px',
              activeTab === t.id
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-[var(--text-secondary)] hover:text-[var(--text-primary)]'
            )}
          >
            {t.label}
            {t.count != null && (
              <span className={cn(
                'text-[10px] font-semibold px-1.5 py-0.5 rounded-full',
                activeTab === t.id
                  ? 'bg-brand-100 dark:bg-brand-900 text-brand-700 dark:text-brand-300'
                  : 'bg-[var(--surface-2)] text-[var(--text-tertiary)]'
              )}>
                {Number(t.count).toLocaleString()}
              </span>
            )}
          </button>
        ))}
      </div>

      {/* All messages tab */}
      {activeTab === 'messages' && (
        <Card padding={false}>
          <div className="flex items-center gap-3 px-5 py-4 border-b border-[var(--border)]">
            <h3 className="text-sm font-semibold text-[var(--text-primary)] flex-1">Messages</h3>
            <select
              value={msgFilter}
              onChange={e => { setMsgFilter(e.target.value); setMsgPage(1) }}
              className="text-xs rounded-lg border border-[var(--border)] bg-[var(--surface)] text-[var(--text-secondary)] px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-brand-500"
            >
              <option value="">All statuses</option>
              <option value="sent">Sent</option>
              <option value="delivered">Delivered</option>
              <option value="failed">Failed</option>
              <option value="undelivered">Undelivered</option>
            </select>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                  {['Contact', 'Phone', 'Country', 'Status', 'Cost', 'Clicks', 'Updated'].map(h => (
                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]">
                {messages?.data?.map(m => (
                  <tr key={m.id} className="hover:bg-[var(--surface-2)] transition-colors">
                    <td className="px-4 py-3 text-sm">
                      {m.contact?.id ? (
                        <Link to={`/contacts/${m.contact.id}`} className="font-medium text-brand-600 hover:underline">
                          {m.contact.name}
                        </Link>
                      ) : (
                        <span className="text-[var(--text-tertiary)] italic">Deleted</span>
                      )}
                    </td>
                    <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{m.contact?.phone ?? '—'}</td>
                    <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{m.contact?.country ?? '—'}</td>
                    <td className="px-4 py-3"><StatusBadge status={m.status} /></td>
                    <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{m.cost ? `$${m.cost}` : '—'}</td>
                    <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{m.click?.click_count ?? 0}</td>
                    <td className="px-4 py-3 text-xs text-[var(--text-tertiary)]">{new Date(m.updated_at).toLocaleString()}</td>
                  </tr>
                ))}
                {messages?.data?.length === 0 && (
                  <tr>
                    <td colSpan={7} className="py-10 text-center text-sm text-[var(--text-tertiary)]">No messages found</td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <Pagination meta={messages?.meta} onPageChange={setMsgPage} />
        </Card>
      )}

      {/* Clicked contacts tab */}
      {activeTab === 'clicked' && hasSent && (
        <Card>
          <CardHeader
            title="Clicked Contacts"
            subtitle="Contacts who clicked the link in this campaign"
          />
          <ContactsSubTable campaignId={id} type="clicked" stats={stats} />
        </Card>
      )}

      {/* Non-clicked contacts tab */}
      {activeTab === 'non_clicked' && hasSent && (
        <Card>
          <CardHeader
            title="Non-Clicked Contacts"
            subtitle="Delivered messages where the link was not clicked"
          />
          <ContactsSubTable campaignId={id} type="non_clicked" stats={stats} />
        </Card>
      )}

      {(activeTab === 'clicked' || activeTab === 'non_clicked') && !hasSent && (
        <Card>
          <EmptyState
            icon={MousePointerClick}
            title="Campaign not yet sent"
            description="Click tracking data will be available after the campaign is sent."
          />
        </Card>
      )}
    </div>
  )
}
