import React, { useState } from 'react'
import { UserPlus } from 'lucide-react'
import api from '../../services/api'
import { useToast } from '../../context/ToastContext'
import { Modal } from '../../components/ui/Modal'
import { Button } from '../../components/ui/Button'
import { Input, Textarea } from '../../components/ui/Input'
import { cn } from '../../lib/cn'

const EMPTY = { name: '', phone: '', email: '', notes: '', opted_in: true, tags: [] }

export function AddContactModal({ open, onClose, onCreated, tags = [] }) {
  const { toast }           = useToast()
  const [saving, setSaving] = useState(false)
  const [errors, setErrors] = useState({})
  const [form, setForm]     = useState(EMPTY)

  const set = (key, val) => {
    setForm(p => ({ ...p, [key]: val }))
    if (errors[key]) setErrors(p => ({ ...p, [key]: null }))
  }

  const toggleTag = (id) =>
    setForm(p => ({
      ...p,
      tags: p.tags.includes(id) ? p.tags.filter(t => t !== id) : [...p.tags, id],
    }))

  const handleClose = () => {
    setForm(EMPTY)
    setErrors({})
    onClose()
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSaving(true)
    setErrors({})
    try {
      const res = await api.post('/contacts', {
        name:     form.name.trim(),
        phone:    form.phone.trim(),
        email:    form.email.trim() || null,
        notes:    form.notes.trim() || null,
        opted_in: form.opted_in,
        tags:     form.tags,
      })
      toast.success(`Contact "${res.data.data.name}" created successfully`)
      onCreated?.(res.data.data)
      handleClose()
    } catch (err) {
      const data = err?.response?.data
      if (data?.errors) {
        setErrors(data.errors)
      } else {
        toast.error(data?.message ?? 'Failed to create contact')
      }
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={handleClose}
      title="Add Contact"
      size="md"
      footer={
        <>
          <Button variant="ghost" onClick={handleClose} disabled={saving}>Cancel</Button>
          <Button form="add-contact-form" type="submit" loading={saving} leftIcon={<UserPlus size={14} />}>
            Create Contact
          </Button>
        </>
      }
    >
      <form id="add-contact-form" onSubmit={handleSubmit} className="space-y-4">
        <Input
          label="Full Name"
          required
          placeholder="e.g. John Smith"
          value={form.name}
          onChange={e => set('name', e.target.value)}
          error={errors.name?.[0]}
        />

        <Input
          label="Phone Number"
          required
          placeholder="+44 7700 900000"
          value={form.phone}
          onChange={e => set('phone', e.target.value)}
          error={errors.phone?.[0]}
          hint="Include country code, e.g. +44 or +1"
        />

        <Input
          label="Email"
          type="email"
          placeholder="patient@example.com"
          value={form.email}
          onChange={e => set('email', e.target.value)}
          error={errors.email?.[0]}
        />

        {/* Opted-in toggle */}
        <div className="flex items-center justify-between rounded-lg border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
          <div>
            <p className="text-sm font-medium text-[var(--text-primary)]">Opted In</p>
            <p className="text-xs text-[var(--text-tertiary)] mt-0.5">Contact has consented to receive SMS</p>
          </div>
          <button
            type="button"
            onClick={() => set('opted_in', !form.opted_in)}
            className={cn(
              'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full',
              'transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
              form.opted_in ? 'bg-brand-500' : 'bg-[var(--border)]'
            )}
            aria-checked={form.opted_in}
            role="switch"
          >
            <span
              className={cn(
                'inline-block h-3.5 w-3.5 rounded-full bg-white shadow-sm transition-transform duration-200',
                form.opted_in ? 'translate-x-[18px]' : 'translate-x-0.5'
              )}
            />
          </button>
        </div>

        {/* Tags */}
        {tags.length > 0 && (
          <div>
            <p className="text-sm font-medium text-[var(--text-primary)] mb-2">Tags</p>
            <div className="flex flex-wrap gap-1.5">
              {tags.map(t => (
                <button
                  key={t.id}
                  type="button"
                  onClick={() => toggleTag(t.id)}
                  className={cn(
                    'text-xs px-2.5 py-1 rounded-full border transition-colors',
                    form.tags.includes(t.id)
                      ? 'bg-brand-500 border-brand-500 text-white'
                      : 'bg-[var(--surface)] border-[var(--border)] text-[var(--text-secondary)] hover:border-brand-400'
                  )}
                >
                  {t.name}
                </button>
              ))}
            </div>
          </div>
        )}

        <Textarea
          label="Notes"
          placeholder="Optional notes about this contact…"
          value={form.notes}
          onChange={e => set('notes', e.target.value)}
          error={errors.notes?.[0]}
        />
      </form>
    </Modal>
  )
}
