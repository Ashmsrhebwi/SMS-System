import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import {
  Phone, Mail, Calendar, Tag as TagIcon, MessageSquare,
  ToggleLeft, ToggleRight, ExternalLink, Send, Clock
} from 'lucide-react'
import api from '../../services/api'
import { useToast } from '../../context/ToastContext'
import { Drawer } from '../../components/ui/Drawer'
import { Button } from '../../components/ui/Button'
import { Badge, StatusBadge } from '../../components/ui/Badge'
import { Skeleton } from '../../components/ui/Skeleton'
import { Avatar } from '../../components/ui/Avatar'

function InfoRow({ icon: Icon, label, value, children }) {
  return (
    <div className="flex items-start gap-3 py-3 border-b border-[var(--border)] last:border-0">
      <Icon size={15} className="mt-0.5 shrink-0 text-[var(--text-tertiary)]" />
      <div className="flex-1 min-w-0">
        <p className="text-xs text-[var(--text-tertiary)] mb-0.5">{label}</p>
        {children ?? <p className="text-sm text-[var(--text-primary)] truncate">{value ?? '—'}</p>}
      </div>
    </div>
  )
}

export function ContactDrawer({ contactId, open, onClose, onUpdate }) {
  const { toast }               = useToast()
  const [contact, setContact]   = useState(null)
  const [loading, setLoading]   = useState(false)
  const [note, setNote]         = useState('')
  const [savingNote, setSavingNote] = useState(false)
  const [toggling, setToggling] = useState(false)

  useEffect(() => {
    if (!open || !contactId) return
    setLoading(true)
    setContact(null)
    api.get(`/contacts/${contactId}`)
      .then(r => setContact(r.data.data))
      .catch(() => toast.error('Failed to load contact'))
      .finally(() => setLoading(false))
  }, [contactId, open])

  const toggleOptIn = async () => {
    setToggling(true)
    try {
      const res = await api.post(`/contacts/${contactId}/toggle-opt-in`)
      setContact(p => ({ ...p, opted_in: res.data.opted_in }))
      toast.success(res.data.opted_in ? 'Contact opted in' : 'Contact opted out')
      onUpdate?.()
    } catch {
      toast.error('Failed to update opt-in status')
    } finally {
      setToggling(false)
    }
  }

  const saveNote = async (e) => {
    e.preventDefault()
    if (!note.trim()) return
    setSavingNote(true)
    try {
      await api.post(`/contacts/${contactId}/notes`, { note })
      setNote('')
      // Refresh contact
      const res = await api.get(`/contacts/${contactId}`)
      setContact(res.data.data)
      toast.success('Note added')
    } catch {
      toast.error('Failed to save note')
    } finally {
      setSavingNote(false)
    }
  }

  return (
    <Drawer
      open={open}
      onClose={onClose}
      title={loading ? 'Loading…' : (contact?.name ?? 'Contact')}
      subtitle={contact?.phone}
      size="md"
      footer={
        <div className="flex items-center justify-between w-full">
          <Button
            variant="ghost"
            size="sm"
            loading={toggling}
            leftIcon={contact?.opted_in ? <ToggleRight size={14} className="text-emerald-500" /> : <ToggleLeft size={14} />}
            onClick={toggleOptIn}
          >
            {contact?.opted_in ? 'Opted in' : 'Opted out'}
          </Button>
          <Link to={`/contacts/${contactId}`} onClick={onClose}>
            <Button variant="secondary" size="sm" leftIcon={<ExternalLink size={12} />}>
              Full profile
            </Button>
          </Link>
        </div>
      }
    >
      {loading ? (
        <div className="space-y-4">
          <div className="flex items-center gap-3">
            <Skeleton className="h-12 w-12 rounded-full" />
            <div className="space-y-2 flex-1">
              <Skeleton className="h-4 w-40" />
              <Skeleton className="h-3 w-28" />
            </div>
          </div>
          {[...Array(5)].map((_, i) => <Skeleton key={i} className="h-12 w-full" />)}
        </div>
      ) : contact ? (
        <div className="space-y-6">
          {/* Avatar + status */}
          <div className="flex items-center gap-4">
            <Avatar name={contact.name} size="xl" />
            <div>
              <h3 className="text-base font-semibold text-[var(--text-primary)]">{contact.name}</h3>
              <div className="flex items-center gap-2 mt-1">
                <StatusBadge status={contact.opted_in ? 'opted_in' : 'opted_out'} />
                {contact.tags?.slice(0, 2).map(t => (
                  <Badge key={t.id} variant="brand" size="sm">{t.name}</Badge>
                ))}
                {contact.tags?.length > 2 && (
                  <Badge variant="default" size="sm">+{contact.tags.length - 2}</Badge>
                )}
              </div>
            </div>
          </div>

          {/* Info rows */}
          <div className="rounded-xl bg-[var(--surface-2)] divide-y divide-[var(--border)]">
            <InfoRow icon={Phone} label="Phone"  value={contact.phone} />
            <InfoRow icon={Mail} label="Email"   value={contact.email} />
            <InfoRow icon={Calendar} label="Added" value={new Date(contact.created_at).toLocaleDateString()} />
            {contact.tags?.length > 0 && (
              <InfoRow icon={TagIcon} label="Tags">
                <div className="flex flex-wrap gap-1 mt-0.5">
                  {contact.tags.map(t => (
                    <Badge key={t.id} variant="brand" size="sm">{t.name}</Badge>
                  ))}
                </div>
              </InfoRow>
            )}
          </div>

          {/* Notes */}
          <div>
            <p className="text-sm font-semibold text-[var(--text-primary)] mb-3 flex items-center gap-1.5">
              <MessageSquare size={14} /> Notes
            </p>

            <form onSubmit={saveNote} className="flex gap-2 mb-3">
              <input
                className="flex-1 h-9 rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-tertiary)] focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"
                placeholder="Add a note…"
                value={note}
                onChange={e => setNote(e.target.value)}
              />
              <Button type="submit" size="sm" loading={savingNote} disabled={!note.trim()}>
                Add
              </Button>
            </form>

            <div className="space-y-2">
              {(contact.contact_notes ?? []).map(n => (
                <div key={n.id} className="rounded-lg bg-[var(--surface-2)] border border-[var(--border)] px-3 py-2.5">
                  <p className="text-sm text-[var(--text-primary)]">{n.note}</p>
                  <p className="text-xs text-[var(--text-tertiary)] mt-1 flex items-center gap-1">
                    <Clock size={10} />
                    {n.author?.name ?? 'Unknown'} · {new Date(n.created_at).toLocaleString()}
                  </p>
                </div>
              ))}
              {!contact.contact_notes?.length && (
                <p className="text-xs text-[var(--text-tertiary)] py-2">No notes yet.</p>
              )}
            </div>
          </div>
        </div>
      ) : null}
    </Drawer>
  )
}
