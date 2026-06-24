import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { Tag, Plus, Edit2, Trash2 } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Badge } from '../components/ui/Badge'
import { Modal } from '../components/ui/Modal'
import { EmptyState } from '../components/ui/EmptyState'
import { Skeleton } from '../components/ui/Skeleton'

const PRESET_COLORS = ['#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#14b8a6', '#f97316']

export default function TagsPage() {
  const { toast }             = useToast()
  const [tags, setTags]       = useState([])
  const [loading, setLoading] = useState(true)
  const [modal, setModal]     = useState(null) // null | 'create' | tag object
  const [form, setForm]       = useState({ name: '', color: '#6366f1' })
  const [saving, setSaving]   = useState(false)

  const load = () => {
    setLoading(true)
    api.get('/tags').then(r => setTags(r.data.data ?? [])).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const openCreate = () => { setForm({ name: '', color: '#6366f1' }); setModal('create') }
  const openEdit   = (t) => { setForm({ name: t.name, color: t.color ?? '#6366f1' }); setModal(t) }

  const save = async () => {
    setSaving(true)
    try {
      if (modal === 'create') {
        await api.post('/tags', form)
        toast.success('Tag created')
      } else {
        await api.put(`/tags/${modal.id}`, form)
        toast.success('Tag updated')
      }
      setModal(null)
      load()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const remove = async (t) => {
    if (!confirm(`Delete tag "${t.name}"? This removes it from all contacts.`)) return
    try {
      await api.delete(`/tags/${t.id}`)
      toast.success('Tag deleted')
      load()
    } catch {
      toast.error('Delete failed')
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader title="Tags" subtitle={`${tags.length} tags`}
        action={<Button leftIcon={<Plus size={14} />} onClick={openCreate}>New Tag</Button>} />

      {loading ? (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {[...Array(6)].map((_, i) => <Skeleton key={i} className="h-16 rounded-xl" />)}
        </div>
      ) : !tags.length ? (
        <Card><EmptyState icon={Tag} title="No tags yet" description="Tags help you segment and organize contacts." /></Card>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {tags.map((t, i) => (
            <motion.div
              key={t.id}
              initial={{ opacity: 0, scale: 0.97 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: i * 0.03 }}
              className="flex items-center gap-3 rounded-xl bg-[var(--surface)] border border-[var(--border)] px-4 py-3 group hover:shadow-[var(--shadow-sm)] transition-shadow"
            >
              <div className="h-9 w-9 rounded-full shrink-0 flex items-center justify-center text-white font-bold text-sm"
                style={{ background: t.color ?? '#6366f1' }}>
                {t.name[0]?.toUpperCase()}
              </div>
              <div className="flex-1 min-w-0">
                <p className="font-semibold text-sm text-[var(--text-primary)] truncate">{t.name}</p>
                <p className="text-xs text-[var(--text-tertiary)]">{t.contacts_count ?? 0} contacts</p>
              </div>
              <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <button onClick={() => openEdit(t)} className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors">
                  <Edit2 size={13} />
                </button>
                <button onClick={() => remove(t)} className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors">
                  <Trash2 size={13} />
                </button>
              </div>
            </motion.div>
          ))}
        </div>
      )}

      <Modal
        open={!!modal}
        onClose={() => setModal(null)}
        title={modal === 'create' ? 'New Tag' : 'Edit Tag'}
        size="sm"
        footer={
          <>
            <Button variant="secondary" onClick={() => setModal(null)}>Cancel</Button>
            <Button loading={saving} onClick={save}>Save Tag</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input label="Tag name" required value={form.name}
            onChange={e => setForm(p => ({ ...p, name: e.target.value }))} />
          <div>
            <p className="text-sm font-medium text-[var(--text-primary)] mb-2">Color</p>
            <div className="flex gap-2 flex-wrap">
              {PRESET_COLORS.map(c => (
                <button key={c} type="button"
                  onClick={() => setForm(p => ({ ...p, color: c }))}
                  className="h-7 w-7 rounded-full border-2 transition-all"
                  style={{
                    background: c,
                    borderColor: form.color === c ? '#fff' : 'transparent',
                    boxShadow: form.color === c ? `0 0 0 2px ${c}` : 'none',
                  }}
                />
              ))}
              <input type="color" value={form.color}
                onChange={e => setForm(p => ({ ...p, color: e.target.value }))}
                className="h-7 w-7 rounded-full cursor-pointer border border-[var(--border)] p-0.5" />
            </div>
          </div>
          <div className="flex items-center gap-2 pt-1">
            <p className="text-sm text-[var(--text-secondary)]">Preview:</p>
            <Badge size="md" style={{ background: form.color + '22', color: form.color, borderColor: form.color + '44' }}>
              {form.name || 'Tag name'}
            </Badge>
          </div>
        </div>
      </Modal>
    </div>
  )
}
