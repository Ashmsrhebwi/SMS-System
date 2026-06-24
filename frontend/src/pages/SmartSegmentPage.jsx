import React, { useEffect, useState } from 'react'
import { Layers, Zap, Users, Target, AlertTriangle } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Spinner } from '../components/ui/Spinner'
import { EmptyState } from '../components/ui/EmptyState'
import { CONTACT_STATUSES, CONTACT_SOURCES } from '../features/contacts/ContactFiltersPanel'

const GROUP_SIZE_PRESETS = [10, 20, 50, 100, 200, 500]

export default function SmartSegmentPage() {
  const { user }  = useAuth()
  const { toast } = useToast()

  const [form, setForm] = useState({
    name_prefix: '',
    group_size:  100,
    country:     '',
    language:    '',
    status:      '',
    tag_id:      '',
    opted_in:    true,
  })
  const [tags, setTags]         = useState([])
  const [preview, setPreview]   = useState(null)
  const [previewing, setPrev]   = useState(false)
  const [distributing, setDist] = useState(false)
  const [results, setResults]   = useState(null)

  useEffect(() => {
    api.get('/tags').then(r => setTags(r.data.data ?? r.data ?? []))
  }, [])

  const handlePreview = async () => {
    setPrev(true)
    setPreview(null)
    try {
      const params = {
        group_size: Number(form.group_size),
        country:    form.country  || undefined,
        language:   form.language || undefined,
        status:     form.status   || undefined,
        tag_id:     form.tag_id   || undefined,
        opted_in:   form.opted_in,
      }
      const r = await api.post('/smart-segments/preview', params)
      setPreview(r.data)
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Preview failed')
    } finally {
      setPrev(false)
    }
  }

  const handleDistribute = async () => {
    if (!form.name_prefix.trim()) {
      toast.error('Please enter a segment name prefix')
      return
    }
    if (!preview || preview.groups_count === 0) {
      toast.error('Run preview first to confirm group count')
      return
    }
    if (preview.groups_count > 500) {
      toast.error('Too many groups. Increase group size.')
      return
    }
    if (!confirm(`This will create ${preview.groups_count} segments. Continue?`)) return

    setDist(true)
    setResults(null)
    try {
      const r = await api.post('/smart-segments/distribute', {
        name_prefix: form.name_prefix,
        group_size:  Number(form.group_size),
        country:     form.country  || undefined,
        language:    form.language || undefined,
        status:      form.status   || undefined,
        tag_id:      form.tag_id   || undefined,
        opted_in:    form.opted_in,
      })
      setResults(r.data)
      toast.success(r.data.message)
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Distribution failed')
    } finally {
      setDist(false)
    }
  }

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Smart Segmentation" />
        <Card><EmptyState icon={Layers} title="Admin access required" description="Smart segmentation is available to administrators only." /></Card>
      </div>
    )
  }

  return (
    <div className="space-y-6 max-w-3xl">
      <PageHeader
        title="Smart Segmentation"
        subtitle="Auto-distribute contacts into equal-sized groups for A/B testing or batch campaigns"
      />

      <Card>
        <CardHeader title="Filter Contacts" subtitle="Choose which contacts to distribute" />
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Country</label>
            <Input placeholder="e.g. United Kingdom" value={form.country} onChange={e => setForm(f => ({ ...f, country: e.target.value }))} />
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Language</label>
            <Input placeholder="e.g. English" value={form.language} onChange={e => setForm(f => ({ ...f, language: e.target.value }))} />
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Status</label>
            <Select value={form.status} onChange={e => setForm(f => ({ ...f, status: e.target.value }))}>
              <option value="">Any status</option>
              {CONTACT_STATUSES.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
            </Select>
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Tag</label>
            <Select value={form.tag_id} onChange={e => setForm(f => ({ ...f, tag_id: e.target.value }))}>
              <option value="">Any tag</option>
              {tags.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
            </Select>
          </div>
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Opt-in Status</label>
            <Select value={form.opted_in ? '1' : '0'} onChange={e => setForm(f => ({ ...f, opted_in: e.target.value === '1' }))}>
              <option value="1">Opted in only</option>
              <option value="0">Opted out only</option>
            </Select>
          </div>
        </div>
      </Card>

      <Card>
        <CardHeader title="Distribution Settings" />
        <div className="space-y-4">
          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Contacts per Group</label>
            <div className="flex items-center gap-2 flex-wrap">
              {GROUP_SIZE_PRESETS.map(size => (
                <button
                  key={size}
                  type="button"
                  onClick={() => setForm(f => ({ ...f, group_size: size }))}
                  className={`px-4 py-2 rounded-lg border text-sm font-medium transition-colors ${
                    Number(form.group_size) === size
                      ? 'border-brand-500 bg-brand-50 dark:bg-brand-950 text-brand-700 dark:text-brand-300'
                      : 'border-[var(--border)] text-[var(--text-secondary)] hover:border-brand-300 hover:text-[var(--text-primary)]'
                  }`}
                >
                  {size}
                </button>
              ))}
              <Input
                type="number"
                min="1"
                max="10000"
                value={form.group_size}
                onChange={e => setForm(f => ({ ...f, group_size: e.target.value }))}
                className="w-24"
                placeholder="Custom"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-[var(--text-secondary)] mb-1.5">Segment Name Prefix</label>
            <Input
              placeholder="e.g. June Campaign"
              value={form.name_prefix}
              onChange={e => setForm(f => ({ ...f, name_prefix: e.target.value }))}
            />
            <p className="text-xs text-[var(--text-tertiary)] mt-1">
              Groups will be named: <span className="font-mono">{form.name_prefix || 'Prefix'} — Group 01</span>, <span className="font-mono">{form.name_prefix || 'Prefix'} — Group 02</span>, …
            </p>
          </div>
        </div>
      </Card>

      {/* Preview */}
      <div className="flex items-center gap-3">
        <Button
          variant="secondary"
          leftIcon={previewing ? <Spinner size="xs" /> : <Users size={14} />}
          onClick={handlePreview}
          disabled={previewing}
        >
          Preview Groups
        </Button>
        <Button
          leftIcon={distributing ? <Spinner size="xs" /> : <Zap size={14} />}
          onClick={handleDistribute}
          disabled={distributing || !preview || preview.groups_count === 0}
        >
          Distribute Now
        </Button>
      </div>

      {preview && (
        <Card>
          <div className="flex items-start gap-4">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-950">
              <Target size={18} className="text-brand-600 dark:text-brand-400" />
            </div>
            <div className="flex-1">
              <h3 className="text-sm font-semibold text-[var(--text-primary)] mb-3">Distribution Preview</h3>
              <div className="grid grid-cols-3 gap-4">
                <div>
                  <p className="text-xs text-[var(--text-tertiary)] uppercase tracking-wide font-semibold mb-0.5">Matching Contacts</p>
                  <p className="text-2xl font-bold text-[var(--text-primary)] tabular">{Number(preview.total_contacts).toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs text-[var(--text-tertiary)] uppercase tracking-wide font-semibold mb-0.5">Group Size</p>
                  <p className="text-2xl font-bold text-[var(--text-primary)] tabular">{Number(preview.group_size).toLocaleString()}</p>
                </div>
                <div>
                  <p className="text-xs text-[var(--text-tertiary)] uppercase tracking-wide font-semibold mb-0.5">Groups to Create</p>
                  <p className={`text-2xl font-bold tabular ${preview.groups_count > 500 ? 'text-red-500' : 'text-brand-600 dark:text-brand-400'}`}>
                    {Number(preview.groups_count).toLocaleString()}
                  </p>
                </div>
              </div>
              {preview.groups_count > 500 && (
                <div className="mt-3 flex items-center gap-2 text-xs text-red-600 dark:text-red-400">
                  <AlertTriangle size={13} />
                  Too many groups (max 500). Please increase the group size.
                </div>
              )}
              {preview.total_contacts === 0 && (
                <div className="mt-3 text-xs text-[var(--text-tertiary)]">
                  No contacts match the current filters.
                </div>
              )}
            </div>
          </div>
        </Card>
      )}

      {results && (
        <Card padding={false}>
          <div className="px-5 py-4 border-b border-[var(--border)]">
            <h3 className="text-sm font-semibold text-[var(--text-primary)]">
              Created {results.groups_created} Segments
            </h3>
            <p className="text-xs text-[var(--text-tertiary)] mt-0.5">
              {Number(results.total_contacts).toLocaleString()} contacts distributed
            </p>
          </div>
          <div className="max-h-80 overflow-y-auto divide-y divide-[var(--border)]">
            {results.segments?.map((seg, i) => (
              <div key={seg.id} className="flex items-center justify-between px-5 py-3">
                <span className="text-sm text-[var(--text-primary)]">{seg.name}</span>
                <span className="text-xs tabular text-[var(--text-tertiary)]">{Number(seg.count).toLocaleString()} contacts</span>
              </div>
            ))}
          </div>
        </Card>
      )}
    </div>
  )
}
