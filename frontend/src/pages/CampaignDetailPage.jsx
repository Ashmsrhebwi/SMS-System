import React, { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Send, RotateCcw, Copy, Trash2, Download,
  CheckCircle2, XCircle, MousePointerClick, Users, Clock
} from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { StatusBadge } from '../components/ui/Badge'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonCard } from '../components/ui/Skeleton'
import { staggerContainer, staggerItem } from '../lib/animations'

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

// Live pulse dot for "sending" campaigns
function SendingIndicator() {
  return (
    <span className="relative flex h-2.5 w-2.5">
      <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75" />
      <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-brand-500" />
    </span>
  )
}

export default function CampaignDetailPage() {
  const { id }   = useParams()
  const navigate = useNavigate()
  const { toast } = useToast()

  const [data, setData]         = useState(null)
  const [messages, setMessages] = useState(null)
  const [msgPage, setMsgPage]   = useState(1)
  const [loading, setLoading]   = useState(true)
  const [actionLoading, setAL]  = useState('')

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
      api.get(`/campaigns/${id}/messages`, { params: { page: msgPage } })
        .then(r => setMessages(r.data))
    }
  }, [msgPage])

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
  const canSend  = ['draft', 'scheduled'].includes(campaign.status)
  const isSending = campaign.status === 'sending'

  return (
    <div className="space-y-6">
      <PageHeader
        title={campaign.name}
        subtitle={`Created by ${campaign.creator?.name ?? 'Unknown'} · ${new Date(campaign.created_at).toLocaleDateString()}`}
        breadcrumbs={[{ label: 'Campaigns', href: '/campaigns' }, { label: campaign.name }]}
        action={
          <div className="flex items-center gap-2">
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
          <span className="text-xs text-[var(--text-tertiary)]">
            Auto-refreshing every 8 s
          </span>
        )}
      </div>

      {/* Message body */}
      <Card>
        <CardHeader title="Message" subtitle="SMS body sent to recipients" />
        <p className="text-sm text-[var(--text-primary)] font-mono whitespace-pre-wrap leading-relaxed bg-[var(--surface-2)] rounded-lg px-4 py-3">
          {campaign.message_body}
        </p>
      </Card>

      {/* Stats with progress bars */}
      <motion.div
        className="grid grid-cols-2 gap-4 sm:grid-cols-4"
        variants={staggerContainer}
        initial="initial"
        animate="animate"
      >
        <motion.div variants={staggerItem}>
          <StatCard
            icon={Users}
            label="Total"
            value={stats.total}
            total={stats.total}
            barColor="bg-gray-300 dark:bg-gray-600"
            iconColor="bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
          />
        </motion.div>
        <motion.div variants={staggerItem}>
          <StatCard
            icon={CheckCircle2}
            label="Delivered"
            value={stats.delivered}
            rate={stats.delivery_rate}
            total={stats.total}
            barColor="bg-emerald-500"
            iconColor="bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400"
          />
        </motion.div>
        <motion.div variants={staggerItem}>
          <StatCard
            icon={XCircle}
            label="Failed"
            value={stats.failed}
            rate={stats.failure_rate}
            total={stats.total}
            barColor="bg-red-500"
            iconColor="bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400"
          />
        </motion.div>
        <motion.div variants={staggerItem}>
          <StatCard
            icon={MousePointerClick}
            label="Clicked"
            value={stats.clicked}
            rate={stats.click_rate}
            total={stats.total}
            barColor="bg-brand-500"
            iconColor="bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400"
          />
        </motion.div>
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

      {/* Messages table */}
      <Card padding={false}>
        <div className="px-5 py-4 border-b border-[var(--border)]">
          <h3 className="text-sm font-semibold text-[var(--text-primary)]">Messages</h3>
        </div>
        <table className="w-full">
          <thead>
            <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
              {['Contact', 'Phone', 'Status', 'Cost', 'Clicks', 'Updated'].map(h => (
                <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-[var(--border)]">
            {messages?.data?.map(m => (
              <tr key={m.id} className="hover:bg-[var(--surface-2)] transition-colors">
                <td className="px-4 py-3 text-sm text-[var(--text-primary)]">
                  {m.contact?.name ?? <span className="text-[var(--text-tertiary)] italic">Deleted</span>}
                </td>
                <td className="px-4 py-3 text-sm text-[var(--text-secondary)]">{m.contact?.phone ?? '—'}</td>
                <td className="px-4 py-3">
                  <StatusBadge status={m.status} />
                </td>
                <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{m.cost ? `$${m.cost}` : '—'}</td>
                <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{m.click?.click_count ?? 0}</td>
                <td className="px-4 py-3 text-xs text-[var(--text-tertiary)]">{new Date(m.updated_at).toLocaleString()}</td>
              </tr>
            ))}
            {messages?.data?.length === 0 && (
              <tr>
                <td colSpan={6} className="py-10 text-center text-sm text-[var(--text-tertiary)]">No messages found</td>
              </tr>
            )}
          </tbody>
        </table>
        <Pagination meta={messages?.meta} onPageChange={setMsgPage} />
      </Card>
    </div>
  )
}
