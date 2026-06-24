import React, { useEffect, useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Plus, FileText, Edit2, Trash2, X, Search } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Textarea, Select } from '../components/ui/Input'
import { Badge } from '../components/ui/Badge'
import { EmptyState } from '../components/ui/EmptyState'
import { Modal } from '../components/ui/Modal'
import { Skeleton } from '../components/ui/Skeleton'

function TemplateForm({ initial, categories, onSave, onCancel, saving }) {
  const [form, setForm] = useState(initial ?? { name: '', content: '', category_id: '' })
  const VARS = ['{first_name}', '{last_name}', '{clinic_name}', '{tracking_url}', '{date}']

  return (
    <div className="space-y-4">
      <Input label="Template name" required value={form.name}
        onChange={e => setForm(p => ({ ...p, name: e.target.value }))} />
      <Select label="Category" value={form.category_id}
        onChange={e => setForm(p => ({ ...p, category_id: e.target.value }))}>
        <option value="">No category</option>
        {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
      </Select>
      <div>
        <div className="flex flex-wrap gap-1.5 mb-2">
          {VARS.map(v => (
            <button key={v} type="button"
              onClick={() => setForm(p => ({ ...p, content: p.content + v }))}
              className="rounded-full bg-[var(--surface-2)] border border-[var(--border)] px-2 py-0.5 text-xs font-mono text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-950 transition-colors">
              {v}
            </button>
          ))}
        </div>
        <Textarea label="Content" required value={form.content}
          onChange={e => setForm(p => ({ ...p, content: e.target.value }))}
          className="font-mono text-sm min-h-[120px]" maxLength={1600}
          hint={`${form.content.length}/1600 · ${Math.ceil((form.content.length || 1) / 160)} segment(s)`} />
      </div>
      <div className="flex items-center justify-end gap-3 pt-2">
        <Button variant="secondary" onClick={onCancel}>Cancel</Button>
        <Button loading={saving} onClick={() => onSave(form)}>Save Template</Button>
      </div>
    </div>
  )
}

export default function TemplatesPage() {
  const { toast }               = useToast()
  const [templates, setTpls]    = useState([])
  const [categories, setCats]   = useState([])
  const [loading, setLoading]   = useState(true)
  const [search, setSearch]     = useState('')
  const [modal, setModal]       = useState(null) // null | 'create' | template object
  const [saving, setSaving]     = useState(false)

  const load = () => {
    setLoading(true)
    api.get('/templates').then(r => {
      setTpls(r.data.data?.data ?? r.data.data ?? [])
      setCats(r.data.categories ?? [])
    }).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const filtered = templates.filter(t =>
    t.name.toLowerCase().includes(search.toLowerCase()) ||
    t.content.toLowerCase().includes(search.toLowerCase())
  )

  const save = async (form) => {
    setSaving(true)
    try {
      if (modal === 'create') {
        await api.post('/templates', form)
        toast.success('Template created')
      } else {
        await api.put(`/templates/${modal.id}`, form)
        toast.success('Template updated')
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
    if (!confirm(`Delete template "${t.name}"?`)) return
    try {
      await api.delete(`/templates/${t.id}`)
      toast.success('Template deleted')
      load()
    } catch {
      toast.error('Delete failed')
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Templates"
        subtitle={`${templates.length} templates`}
        action={<Button leftIcon={<Plus size={14} />} onClick={() => setModal('create')}>New Template</Button>}
      />

      <Input placeholder="Search templates…" leftIcon={<Search size={14} />}
        value={search} onChange={e => setSearch(e.target.value)} className="w-72" />

      {loading ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {[...Array(6)].map((_, i) => (
            <div key={i} className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-4">
              <Skeleton className="h-4 w-32 mb-2" />
              <Skeleton className="h-3 w-full mb-1" />
              <Skeleton className="h-3 w-3/4" />
            </div>
          ))}
        </div>
      ) : !filtered.length ? (
        <Card><EmptyState icon={FileText} title="No templates" description="Create a reusable message template." /></Card>
      ) : (
        <motion.div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filtered.map((t, i) => (
            <motion.div
              key={t.id}
              initial={{ opacity: 0, y: 8 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: i * 0.04 }}
              className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-4 shadow-[var(--shadow-sm)] group hover:shadow-[var(--shadow-md)] transition-shadow"
            >
              <div className="flex items-start justify-between mb-2">
                <div>
                  <p className="font-semibold text-sm text-[var(--text-primary)]">{t.name}</p>
                  {t.category && <Badge variant="brand" size="sm" className="mt-1">{t.category.name}</Badge>}
                </div>
                <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button onClick={() => setModal(t)}
                    className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors">
                    <Edit2 size={13} />
                  </button>
                  <button onClick={() => remove(t)}
                    className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors">
                    <Trash2 size={13} />
                  </button>
                </div>
              </div>
              <p className="text-xs text-[var(--text-secondary)] font-mono truncate-2 leading-relaxed">
                {t.content}
              </p>
              <p className="text-xs text-[var(--text-tertiary)] mt-2.5">
                {t.content.length} chars · {Math.ceil(t.content.length / 160)} segment(s)
              </p>
            </motion.div>
          ))}
        </motion.div>
      )}

      <Modal
        open={!!modal}
        onClose={() => setModal(null)}
        title={modal === 'create' ? 'New Template' : 'Edit Template'}
        size="lg"
      >
        {modal && (
          <TemplateForm
            initial={modal !== 'create' ? { name: modal.name, content: modal.content, category_id: modal.category_id ?? '' } : undefined}
            categories={categories}
            onSave={save}
            onCancel={() => setModal(null)}
            saving={saving}
          />
        )}
      </Modal>
    </div>
  )
}
