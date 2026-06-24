import React, { useState } from 'react'
import { MessageSquare, Tag as TagIcon, ToggleLeft, Loader2 } from 'lucide-react'
import api from '../../services/api'
import { useToast } from '../../context/ToastContext'
import { Modal } from '../../components/ui/Modal'
import { Button } from '../../components/ui/Button'
import { cn } from '../../lib/cn'

const ACTIONS = [
  {
    key:   'add_note',
    icon:  MessageSquare,
    label: 'Add Note',
    desc:  'Append a note to all selected contacts',
  },
  {
    key:   'add_tags',
    icon:  TagIcon,
    label: 'Add Tags',
    desc:  'Assign tags without removing existing ones',
  },
  {
    key:   'opted_out',
    icon:  ToggleLeft,
    label: 'Mark Opted Out',
    desc:  'Set all selected contacts to opted out',
    color: 'red',
  },
  {
    key:   'opted_in',
    icon:  ToggleLeft,
    label: 'Mark Opted In',
    desc:  'Set all selected contacts to opted in',
    color: 'green',
  },
]

export function BulkUpdateModal({ open, onClose, selectedIds, onUpdated, tags = [] }) {
  const { toast }           = useToast()
  const [action, setAction] = useState('add_note')
  const [note, setNote]     = useState('')
  const [tagIds, setTagIds] = useState([])
  const [saving, setSaving] = useState(false)

  const count = selectedIds?.length ?? 0

  const toggleTag = (id) =>
    setTagIds(p => p.includes(id) ? p.filter(t => t !== id) : [...p, id])

  const handleClose = () => {
    setAction('add_note')
    setNote('')
    setTagIds([])
    onClose()
  }

  const canSubmit = () => {
    if (action === 'add_note') return note.trim().length > 0
    if (action === 'add_tags') return tagIds.length > 0
    return true // opted_in / opted_out
  }

  const handleSubmit = async () => {
    if (!canSubmit()) return
    setSaving(true)
    try {
      const payload = { ids: selectedIds, action }
      if (action === 'add_note') payload.note    = note.trim()
      if (action === 'add_tags') payload.tag_ids = tagIds

      const res = await api.post('/contacts/bulk-update', payload)
      toast.success(res.data.message)
      onUpdated?.()
      handleClose()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Bulk update failed')
    } finally {
      setSaving(false)
    }
  }

  const activeAction = ACTIONS.find(a => a.key === action)

  return (
    <Modal
      open={open}
      onClose={handleClose}
      title="Mass Update"
      size="md"
      footer={
        <>
          <Button variant="ghost" onClick={handleClose} disabled={saving}>Cancel</Button>
          <Button
            onClick={handleSubmit}
            loading={saving}
            disabled={!canSubmit()}
            variant={activeAction?.color === 'red' ? 'danger' : 'primary'}
          >
            Apply to {count} contact{count !== 1 ? 's' : ''}
          </Button>
        </>
      }
    >
      <div className="space-y-5">
        {/* Selected count badge */}
        <div className="flex items-center gap-2 px-3 py-2 rounded-lg bg-brand-50 dark:bg-brand-950/40 border border-brand-200 dark:border-brand-800">
          <span className="text-sm font-semibold text-brand-700 dark:text-brand-300">
            {count} contact{count !== 1 ? 's' : ''} selected
          </span>
        </div>

        {/* Action picker */}
        <div>
          <p className="text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide mb-2">
            Choose Action
          </p>
          <div className="grid grid-cols-2 gap-2">
            {ACTIONS.map(({ key, icon: Icon, label, desc, color }) => {
              const isActive = action === key
              return (
                <button
                  key={key}
                  type="button"
                  onClick={() => setAction(key)}
                  className={cn(
                    'flex flex-col items-start gap-1 rounded-xl border p-3 text-left transition-all',
                    isActive
                      ? color === 'red'
                        ? 'border-red-400 bg-red-50 dark:bg-red-950/40'
                        : color === 'green'
                          ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-950/40'
                          : 'border-brand-400 bg-brand-50 dark:bg-brand-950/40'
                      : 'border-[var(--border)] bg-[var(--surface)] hover:bg-[var(--surface-2)]'
                  )}
                >
                  <Icon
                    size={16}
                    className={cn(
                      isActive
                        ? color === 'red'
                          ? 'text-red-500'
                          : color === 'green'
                            ? 'text-emerald-500'
                            : 'text-brand-500'
                        : 'text-[var(--text-tertiary)]'
                    )}
                  />
                  <span className={cn(
                    'text-xs font-semibold',
                    isActive ? 'text-[var(--text-primary)]' : 'text-[var(--text-secondary)]'
                  )}>
                    {label}
                  </span>
                  <span className="text-[11px] text-[var(--text-tertiary)] leading-tight">{desc}</span>
                </button>
              )
            })}
          </div>
        </div>

        {/* Dynamic input */}
        {action === 'add_note' && (
          <div>
            <label className="block text-sm font-medium text-[var(--text-primary)] mb-1.5">
              Note <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={3}
              placeholder="Write a note to add to all selected contacts…"
              value={note}
              onChange={e => setNote(e.target.value)}
              className="w-full rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 py-2 text-sm text-[var(--text-primary)] placeholder:text-[var(--text-tertiary)] focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 resize-none"
            />
            <p className="text-xs text-[var(--text-tertiary)] mt-1">
              This note will be appended to each selected contact.
            </p>
          </div>
        )}

        {action === 'add_tags' && (
          <div>
            <label className="block text-sm font-medium text-[var(--text-primary)] mb-1.5">
              Select Tags <span className="text-red-500">*</span>
            </label>
            {tags.length === 0 ? (
              <p className="text-sm text-[var(--text-tertiary)] italic">No tags available. Create tags first.</p>
            ) : (
              <div className="flex flex-wrap gap-1.5">
                {tags.map(t => (
                  <button
                    key={t.id}
                    type="button"
                    onClick={() => toggleTag(t.id)}
                    className={cn(
                      'text-xs px-3 py-1.5 rounded-full border transition-colors',
                      tagIds.includes(t.id)
                        ? 'bg-brand-500 border-brand-500 text-white shadow-sm'
                        : 'bg-[var(--surface)] border-[var(--border)] text-[var(--text-secondary)] hover:border-brand-400'
                    )}
                  >
                    {t.name}
                  </button>
                ))}
              </div>
            )}
            <p className="text-xs text-[var(--text-tertiary)] mt-2">
              Tags will be added without removing existing ones.
            </p>
          </div>
        )}

        {(action === 'opted_out' || action === 'opted_in') && (
          <div className={cn(
            'rounded-xl border p-4',
            action === 'opted_out'
              ? 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800'
              : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800'
          )}>
            <p className={cn(
              'text-sm font-medium',
              action === 'opted_out' ? 'text-red-700 dark:text-red-300' : 'text-emerald-700 dark:text-emerald-300'
            )}>
              {action === 'opted_out'
                ? `This will mark all ${count} selected contact${count !== 1 ? 's' : ''} as opted out. They will no longer receive SMS messages.`
                : `This will mark all ${count} selected contact${count !== 1 ? 's' : ''} as opted in. They will be eligible to receive SMS messages.`
              }
            </p>
          </div>
        )}
      </div>
    </Modal>
  )
}
