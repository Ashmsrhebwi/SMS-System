import React, { useEffect, useState, useCallback } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import {
  Search, Filter, MessageSquare, CheckCircle2, XCircle,
  AlertTriangle, Clock, MousePointerClick, X, ChevronRight,
  Calendar, ExternalLink, Phone, User2, Send,
} from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Input } from '../components/ui/Input'
import { Badge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'

const STATUS_CONFIG = {
  delivered:   { variant: 'success',  icon: CheckCircle2,    label: 'Delivered'   },
  sent:        { variant: 'brand',    icon: Send,            label: 'Sent'        },
  queued:      { variant: 'warning',  icon: Clock,           label: 'Queued'      },
  pending:     { variant: 'default',  icon: Clock,           label: 'Pending'     },
  failed:      { variant: 'danger',   icon: XCircle,         label: 'Failed'      },
  undelivered: { variant: 'danger',   icon: AlertTriangle,   label: 'Undelivered' },
}

function StatusBadge({ status }) {
  const cfg = STATUS_CONFIG[status] ?? { variant: 'default', label: status }
  return <Badge variant={cfg.variant} size="sm">{cfg.label}</Badge>
}

function MessageDetail({ msg, onClose, isAdmin }) {
  if (!msg) return null

  const timeline = msg.timeline ?? []

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 z-50 flex"
        onClick={onClose}
      >
        <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" />
        <motion.div
          initial={{ x: '100%' }}
          animate={{ x: 0 }}
          exit={{ x: '100%' }}
          transition={{ type: 'spring', damping: 28, stiffness: 250 }}
          className="ml-auto relative w-full max-w-xl h-full bg-[var(--surface)] shadow-2xl overflow-y-auto"
          onClick={e => e.stopPropagation()}
        >
          {/* Header */}
          <div className="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-[var(--border)] bg-[var(--surface)]">
            <div>
              <p className="text-sm font-semibold text-[var(--text-primary)]">Message Detail</p>
              {msg.twilio_sid && (
                <p className="text-xs font-mono text-[var(--text-tertiary)] mt-0.5">{msg.twilio_sid}</p>
              )}
            </div>
            <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)]">
              <X size={16} />
            </button>
          </div>

          <div className="p-6 space-y-6">
            {/* Status + meta */}
            <div className="flex items-center gap-3 flex-wrap">
              <StatusBadge status={msg.status} />
              {msg.click?.click_count > 0 && (
                <Badge variant="brand" size="sm"><MousePointerClick size={11} className="inline mr-1" />{msg.click.click_count} click{msg.click.click_count !== 1 ? 's' : ''}</Badge>
              )}
              {msg.resend_count > 0 && (
                <Badge variant="warning" size="sm">Resent {msg.resend_count}×</Badge>
              )}
            </div>

            {/* Campaign + Contact */}
            <div className="grid grid-cols-2 gap-3">
              <div className="rounded-xl border border-[var(--border)] p-3">
                <p className="text-[10px] font-semibold text-[var(--text-tertiary)] uppercase tracking-wide mb-1">Campaign</p>
                <p className="text-sm font-medium text-[var(--text-primary)] truncate">{msg.campaign?.name ?? '—'}</p>
                {msg.campaign?.created_by && <p className="text-xs text-[var(--text-tertiary)] mt-0.5">by {msg.campaign.created_by}</p>}
              </div>
              <div className="rounded-xl border border-[var(--border)] p-3">
                <p className="text-[10px] font-semibold text-[var(--text-tertiary)] uppercase tracking-wide mb-1">Recipient</p>
                <p className="text-sm font-medium text-[var(--text-primary)] truncate">{msg.contact?.name ?? '—'}</p>
                <p className="text-xs font-mono text-[var(--text-tertiary)] mt-0.5">{msg.contact?.phone ?? '—'}</p>
              </div>
            </div>

            {/* Error info */}
            {(msg.error_code || msg.error_message) && (
              <div className="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/10 dark:border-red-800 p-4">
                <p className="text-xs font-semibold text-red-700 dark:text-red-400 mb-1">Error Details</p>
                {msg.error_code && <p className="text-xs font-mono text-red-600 dark:text-red-400">Code: {msg.error_code}</p>}
                {msg.error_message && <p className="text-xs text-red-600 dark:text-red-400 mt-1">{msg.error_message}</p>}
              </div>
            )}

            {/* Message body */}
            {msg.message_body && (
              <div>
                <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-2">Message Body</p>
                <div className="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] p-3 text-sm text-[var(--text-primary)] whitespace-pre-wrap leading-relaxed">
                  {msg.message_body}
                </div>
              </div>
            )}

            {/* Cost Details */}
            <div>
              <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-2">Cost & Delivery</p>
              <div className="rounded-xl border border-[var(--border)] divide-y divide-[var(--border)]">
                {[
                  ['SMS Segments',      msg.sms_segments ?? '—'],
                  ['Cost',             msg.cost != null ? `$${parseFloat(msg.cost).toFixed(4)}` : '—'],
                  ['Country',          msg.contact?.country ?? '—'],
                  ['Language',         msg.contact?.language ?? '—'],
                ].map(([l, v]) => (
                  <div key={l} className="flex justify-between px-3 py-2">
                    <span className="text-xs text-[var(--text-tertiary)]">{l}</span>
                    <span className="text-xs font-medium text-[var(--text-primary)]">{v}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Timeline */}
            <div>
              <p className="text-xs font-semibold text-[var(--text-tertiary)] mb-3">Delivery Timeline</p>
              <div className="relative pl-5">
                <div className="absolute left-2 top-0 bottom-0 w-px bg-[var(--border)]" />
                {timeline.map((event, i) => (
                  <div key={i} className="relative mb-4 last:mb-0">
                    <div className={`absolute -left-[13px] top-1 h-2 w-2 rounded-full border-2 border-[var(--surface)]
                      ${event.event === 'failed' ? 'bg-red-500' : event.event === 'delivered' ? 'bg-emerald-500' : event.event === 'clicked' ? 'bg-brand-600' : 'bg-[var(--text-tertiary)]'}`} />
                    <p className="text-xs font-semibold text-[var(--text-primary)] capitalize">{event.event}</p>
                    <p className="text-[10px] text-[var(--text-tertiary)]">{new Date(event.at).toLocaleString()}</p>
                    <p className="text-[10px] text-[var(--text-secondary)] mt-0.5">{event.note}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  )
}

const STATUSES = ['delivered', 'sent', 'queued', 'pending', 'failed', 'undelivered']

export default function MessagesPage() {
  const { user }              = useAuth()
  const [data, setData]       = useState(null)
  const [search, setSearch]   = useState('')
  const [status, setStatus]   = useState('')
  const [dateFrom, setFrom]   = useState('')
  const [dateTo, setTo]       = useState('')
  const [clicked, setClicked] = useState('')
  const [page, setPage]       = useState(1)
  const [loading, setLoad]    = useState(true)
  const [detail, setDetail]   = useState(null)
  const [detailLoad, setDL]   = useState(false)
  const isAdmin               = user?.role === 'admin'

  const load = useCallback((p = 1) => {
    setLoad(true)
    const params = { page: p, per_page: 50 }
    if (search)  params.search   = search
    if (status)  params.status   = status
    if (dateFrom) params.date_from = dateFrom
    if (dateTo)   params.date_to   = dateTo
    if (clicked)  params.clicked   = clicked

    api.get('/messages', { params })
      .then(r => setData(r.data))
      .finally(() => setLoad(false))
  }, [search, status, dateFrom, dateTo, clicked])

  useEffect(() => { load(1); setPage(1) }, [search, status, dateFrom, dateTo, clicked])
  useEffect(() => { if (page > 1) load(page) }, [page])

  const openDetail = async (id) => {
    setDL(true)
    try {
      const r = await api.get(`/messages/${id}`)
      setDetail(r.data)
    } finally {
      setDL(false)
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Message Log"
        subtitle={data?.total != null ? `${data.total.toLocaleString()} total messages` : undefined}
        icon={<MessageSquare size={18} />}
      />

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <Input
          placeholder="Search SID, contact, campaign…"
          leftIcon={<Search size={14} />}
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="w-64"
        />

        <select
          value={status}
          onChange={e => setStatus(e.target.value)}
          className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
          <option value="">All statuses</option>
          {STATUSES.map(s => <option key={s} value={s} className="capitalize">{s}</option>)}
        </select>

        <select
          value={clicked}
          onChange={e => setClicked(e.target.value)}
          className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
          <option value="">All clicks</option>
          <option value="1">Clicked</option>
          <option value="0">Not clicked</option>
        </select>

        <div className="flex items-center gap-1.5">
          <Calendar size={13} className="text-[var(--text-tertiary)]" />
          <input
            type="date"
            value={dateFrom}
            onChange={e => setFrom(e.target.value)}
            className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
          <span className="text-xs text-[var(--text-tertiary)]">–</span>
          <input
            type="date"
            value={dateTo}
            onChange={e => setTo(e.target.value)}
            className="h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-2 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
          />
        </div>

        {(search || status || dateFrom || dateTo || clicked) && (
          <Button
            size="sm"
            variant="ghost"
            leftIcon={<X size={13} />}
            onClick={() => { setSearch(''); setStatus(''); setFrom(''); setTo(''); setClicked('') }}
          >
            Clear
          </Button>
        )}
      </div>

      {/* Table */}
      {loading ? (
        <SkeletonTable rows={10} />
      ) : !data?.data?.length ? (
        <Card>
          <EmptyState icon={MessageSquare} title="No messages found" description="No messages match the current filters." />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[900px]">
              <thead>
                <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                  {['Status', 'Campaign', 'Contact', 'Phone', 'Country', 'Segments', 'Cost', 'Click', 'Sent At', ''].map(h => (
                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide whitespace-nowrap">{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]">
                {data.data.map((msg, i) => (
                  <motion.tr
                    key={msg.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: i * 0.01 }}
                    className="hover:bg-[var(--surface-2)] transition-colors cursor-pointer"
                    onClick={() => openDetail(msg.id)}
                  >
                    <td className="px-4 py-3 whitespace-nowrap">
                      <StatusBadge status={msg.status} />
                    </td>
                    <td className="px-4 py-3 max-w-[160px]">
                      <p className="text-sm text-[var(--text-primary)] truncate">{msg.campaign?.name ?? '—'}</p>
                    </td>
                    <td className="px-4 py-3 max-w-[140px]">
                      <p className="text-sm text-[var(--text-secondary)] truncate">{msg.contact?.name ?? '—'}</p>
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap">
                      <span className="text-xs font-mono text-[var(--text-tertiary)]">{msg.contact?.phone ?? '—'}</span>
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap">
                      <span className="text-xs text-[var(--text-tertiary)]">{msg.contact?.country ?? '—'}</span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <span className="text-xs font-medium text-[var(--text-secondary)]">{msg.sms_segments ?? '—'}</span>
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap">
                      <span className="text-xs font-mono text-[var(--text-tertiary)]">
                        {msg.cost != null ? `$${parseFloat(msg.cost).toFixed(4)}` : '—'}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-center">
                      {msg.click?.click_count > 0 ? (
                        <Badge variant="brand" size="sm"><MousePointerClick size={10} className="inline mr-0.5" />{msg.click.click_count}</Badge>
                      ) : (
                        <span className="text-xs text-[var(--text-tertiary)]">—</span>
                      )}
                    </td>
                    <td className="px-4 py-3 whitespace-nowrap">
                      <span className="text-xs text-[var(--text-tertiary)]">
                        {msg.sent_at ? new Date(msg.sent_at).toLocaleString() : '—'}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <ChevronRight size={14} className="text-[var(--text-tertiary)]" />
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
          <Pagination
            meta={{ current_page: data.current_page, last_page: data.last_page, total: data.total, per_page: data.per_page }}
            onPageChange={setPage}
          />
        </Card>
      )}

      {/* Detail drawer */}
      {detail && <MessageDetail msg={detail} onClose={() => setDetail(null)} isAdmin={isAdmin} />}
    </div>
  )
}
