import React, { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Phone, Mail, Calendar, Tag, MessageSquare, Trash2, Plus,
  StickyNote, Clock, CheckCircle2, AlertCircle, Send,
} from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Avatar } from '../components/ui/Avatar'
import { Skeleton } from '../components/ui/Skeleton'
import { Tabs } from '../components/ui/Tabs'
import { Pagination } from '../components/ui/Pagination'
import { staggerContainer, staggerItem } from '../lib/animations'

function InfoRow({ icon: Icon, label, value }) {
  if (!value) return null
  return (
    <div className="flex items-start gap-3 py-3 border-b border-[var(--border)] last:border-0">
      <div className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[var(--surface-2)] text-[var(--text-tertiary)] mt-0.5">
        <Icon size={13} />
      </div>
      <div>
        <p className="text-xs text-[var(--text-tertiary)] mb-0.5">{label}</p>
        <p className="text-sm text-[var(--text-primary)]">{value}</p>
      </div>
    </div>
  )
}

const MSG_ICON = {
  delivered:   { icon: CheckCircle2, color: 'text-emerald-500' },
  failed:      { icon: AlertCircle,  color: 'text-red-500' },
  undelivered: { icon: AlertCircle,  color: 'text-orange-400' },
  sent:        { icon: Send,         color: 'text-brand-500' },
}

export default function ContactDetailPage() {
  const { id }    = useParams()
  const navigate  = useNavigate()
  const { toast } = useToast()

  const [contact, setContact]   = useState(null)
  const [loading, setLoading]   = useState(true)
  const [tab, setTab]           = useState('info')
  const [note, setNote]         = useState('')
  const [savingNote, setSavingNote] = useState(false)
  const [toggling, setToggling] = useState(false)

  // Messages tab state
  const [messages, setMessages]   = useState(null)
  const [msgPage, setMsgPage]     = useState(1)
  const [msgLoading, setMsgLoading] = useState(false)

  const load = () => {
    setLoading(true)
    api.get(`/contacts/${id}`)
      .then(r => setContact(r.data.data))
      .catch(() => toast.error('Failed to load contact'))
      .finally(() => setLoading(false))
  }

  const loadMessages = (p = 1) => {
    setMsgLoading(true)
    api.get(`/contacts/${id}/messages`, { params: { page: p } })
      .then(r => setMessages(r.data))
      .finally(() => setMsgLoading(false))
  }

  useEffect(() => { load() }, [id])
  useEffect(() => {
    if (tab === 'messages' && !messages) loadMessages(1)
  }, [tab])
  useEffect(() => {
    if (tab === 'messages') loadMessages(msgPage)
  }, [msgPage])

  const toggleOptIn = async () => {
    setToggling(true)
    try {
      const res = await api.post(`/contacts/${id}/toggle-opt-in`)
      setContact(p => ({ ...p, opted_in: res.data.opted_in }))
      toast.success(res.data.opted_in ? 'Contact opted in' : 'Contact opted out')
    } catch {
      toast.error('Action failed')
    } finally {
      setToggling(false)
    }
  }

  const handleDelete = async () => {
    if (!confirm('Delete this contact? This cannot be undone.')) return
    try {
      await api.delete(`/contacts/${id}`)
      toast.success('Contact deleted')
      navigate('/contacts')
    } catch {
      toast.error('Delete failed')
    }
  }

  const saveNote = async (e) => {
    e.preventDefault()
    if (!note.trim()) return
    setSavingNote(true)
    try {
      await api.post(`/contacts/${id}/notes`, { note })
      setNote('')
      load()
    } catch {
      toast.error('Failed to save note')
    } finally {
      setSavingNote(false)
    }
  }

  if (loading) {
    return (
      <div className="max-w-4xl space-y-6">
        <Skeleton className="h-8 w-48 rounded-lg" />
        <div className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-6">
          <Skeleton className="h-16 w-16 rounded-full mb-4" />
          <Skeleton className="h-6 w-40 mb-2 rounded" />
          <Skeleton className="h-4 w-24 rounded" />
        </div>
      </div>
    )
  }

  if (!contact) return null

  const tabs = [
    { value: 'info',     label: 'Info',     icon: Phone },
    { value: 'notes',    label: 'Notes',    icon: StickyNote, count: contact.contact_notes?.length },
    { value: 'messages', label: 'Messages', icon: MessageSquare, count: contact.messages_count ?? undefined },
  ]

  return (
    <div className="max-w-4xl space-y-6">
      <PageHeader
        title={contact.name}
        subtitle={contact.phone}
        breadcrumbs={[{ label: 'Contacts', href: '/contacts' }, { label: contact.name }]}
        action={
          <div className="flex items-center gap-2">
            <Button variant="secondary" size="sm" loading={toggling} onClick={toggleOptIn}>
              {contact.opted_in ? 'Opt Out' : 'Opt In'}
            </Button>
            <Button variant="danger" size="sm" leftIcon={<Trash2 size={13} />} onClick={handleDelete}>
              Delete
            </Button>
          </div>
        }
      />

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left: profile card (always visible) */}
        <div className="lg:col-span-1 space-y-4">
          <Card>
            <div className="flex flex-col items-center text-center pb-4 mb-4 border-b border-[var(--border)]">
              <Avatar name={contact.name} size="xl" className="mb-3" />
              <h2 className="font-bold text-base text-[var(--text-primary)]">{contact.name}</h2>
              <StatusBadge status={contact.opted_in ? 'opted_in' : 'opted_out'} className="mt-1" />
            </div>

            <div>
              <InfoRow icon={Phone}    label="Phone"   value={contact.phone} />
              <InfoRow icon={Mail}     label="Email"   value={contact.email} />
              <InfoRow icon={Calendar} label="Added"   value={new Date(contact.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} />
            </div>

            {contact.tags?.length > 0 && (
              <div className="mt-4 pt-4 border-t border-[var(--border)]">
                <p className="text-xs text-[var(--text-tertiary)] mb-2 flex items-center gap-1.5"><Tag size={11} /> Tags</p>
                <div className="flex flex-wrap gap-1.5">
                  {contact.tags.map(t => (
                    <Badge
                      key={t.id}
                      variant="brand"
                      size="sm"
                      style={{ background: (t.color ?? '#6366f1') + '22', color: t.color ?? '#6366f1', borderColor: (t.color ?? '#6366f1') + '44' }}
                    >
                      {t.name}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
          </Card>

          {/* Quick stat tiles */}
          <Card>
            <CardHeader title="Quick Stats" />
            <div className="grid grid-cols-2 gap-3">
              {[
                { label: 'Messages', value: contact.messages_count ?? 0, icon: MessageSquare },
                { label: 'Notes',    value: contact.contact_notes?.length ?? 0, icon: StickyNote },
              ].map(s => (
                <div key={s.label} className="rounded-xl bg-[var(--surface-2)] p-3 text-center">
                  <p className="text-2xl font-bold text-[var(--text-primary)] tabular">{s.value}</p>
                  <p className="text-xs text-[var(--text-tertiary)] mt-0.5 flex items-center justify-center gap-1">
                    <s.icon size={11} /> {s.label}
                  </p>
                </div>
              ))}
            </div>
          </Card>
        </div>

        {/* Right: tabbed content */}
        <div className="lg:col-span-2">
          <Card>
            <Tabs
              tabs={tabs}
              active={tab}
              onChange={setTab}
              className="mb-5"
            />

            {/* Info tab */}
            {tab === 'info' && (
              <motion.div
                key="info"
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.16 }}
                className="space-y-4"
              >
                <div className="rounded-xl border border-[var(--border)] p-4 space-y-0">
                  <InfoRow icon={Phone}    label="Phone"   value={contact.phone} />
                  <InfoRow icon={Mail}     label="Email"   value={contact.email} />
                  <InfoRow icon={Calendar} label="Created" value={new Date(contact.created_at).toLocaleString()} />
                </div>
                {contact.custom_fields && Object.keys(contact.custom_fields).length > 0 && (
                  <div>
                    <p className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wider mb-3">Custom Fields</p>
                    <div className="rounded-xl border border-[var(--border)] p-4 space-y-0">
                      {Object.entries(contact.custom_fields).map(([k, v]) => (
                        <div key={k} className="flex items-center justify-between py-2 border-b border-[var(--border)] last:border-0">
                          <span className="text-xs text-[var(--text-tertiary)] capitalize">{k.replace(/_/g, ' ')}</span>
                          <span className="text-sm text-[var(--text-primary)]">{String(v)}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
                {!contact.email && !contact.custom_fields && (
                  <p className="text-sm text-[var(--text-tertiary)] py-4 text-center">No additional info on file.</p>
                )}
              </motion.div>
            )}

            {/* Notes tab */}
            {tab === 'notes' && (
              <motion.div
                key="notes"
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.16 }}
              >
                <form onSubmit={saveNote} className="flex gap-2 mb-5">
                  <Input
                    placeholder="Add a note…"
                    value={note}
                    onChange={e => setNote(e.target.value)}
                    className="flex-1"
                  />
                  <Button type="submit" size="sm" loading={savingNote} leftIcon={<Plus size={12} />}>
                    Add
                  </Button>
                </form>

                {contact.contact_notes?.length ? (
                  <motion.div
                    className="space-y-3"
                    variants={staggerContainer}
                    initial="initial"
                    animate="animate"
                  >
                    {contact.contact_notes.map(n => (
                      <motion.div
                        key={n.id}
                        variants={staggerItem}
                        className="rounded-xl bg-[var(--surface-2)] border border-[var(--border)] p-4"
                      >
                        <p className="text-sm text-[var(--text-primary)] leading-relaxed">{n.note}</p>
                        <p className="text-xs text-[var(--text-tertiary)] mt-2 flex items-center gap-1.5">
                          <span className="font-medium">{n.author?.name ?? 'Unknown'}</span>
                          <span>·</span>
                          <span>{new Date(n.created_at).toLocaleString()}</span>
                        </p>
                      </motion.div>
                    ))}
                  </motion.div>
                ) : (
                  <div className="py-8 text-center">
                    <p className="text-sm text-[var(--text-tertiary)]">No notes yet. Add one above.</p>
                  </div>
                )}
              </motion.div>
            )}

            {/* Messages tab */}
            {tab === 'messages' && (
              <motion.div
                key="messages"
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.16 }}
              >
                {msgLoading ? (
                  <div className="space-y-3">
                    {[...Array(5)].map((_, i) => (
                      <div key={i} className="flex items-center gap-3 py-3 border-b border-[var(--border)]">
                        <Skeleton className="h-6 w-6 rounded-full shrink-0" />
                        <Skeleton className="flex-1 h-4" />
                        <Skeleton className="h-5 w-20 rounded-full" />
                        <Skeleton className="h-3 w-24" />
                      </div>
                    ))}
                  </div>
                ) : messages?.data?.length ? (
                  <>
                    <div className="divide-y divide-[var(--border)]">
                      {messages.data.map(m => {
                        const cfg  = MSG_ICON[m.status] ?? MSG_ICON.sent
                        const Icon = cfg.icon
                        return (
                          <div key={m.id} className="flex items-start gap-3 py-3">
                            <Icon size={16} className={`shrink-0 mt-0.5 ${cfg.color}`} />
                            <div className="flex-1 min-w-0">
                              <p className="text-sm text-[var(--text-primary)] font-medium truncate">
                                {m.campaign?.name ?? 'Direct message'}
                              </p>
                              <p className="text-xs text-[var(--text-tertiary)] line-clamp-1 mt-0.5">
                                {m.message_body}
                              </p>
                            </div>
                            <div className="shrink-0 text-right space-y-1">
                              <StatusBadge status={m.status} />
                              <p className="text-[10px] text-[var(--text-tertiary)] flex items-center gap-1 justify-end">
                                <Clock size={9} />
                                {new Date(m.updated_at).toLocaleDateString()}
                              </p>
                            </div>
                          </div>
                        )
                      })}
                    </div>
                    <Pagination meta={messages?.meta} onPageChange={p => setMsgPage(p)} />
                  </>
                ) : (
                  <div className="py-8 text-center">
                    <p className="text-sm text-[var(--text-tertiary)]">No messages found for this contact.</p>
                  </div>
                )}
              </motion.div>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}
