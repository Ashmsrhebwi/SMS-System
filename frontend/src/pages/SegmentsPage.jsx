import React, { useCallback, useEffect, useRef, useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import {
  Filter, Plus, Trash2, Users, ChevronDown, ChevronUp,
  Edit2, Copy, RefreshCw, GitMerge, Layers
} from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Modal } from '../components/ui/Modal'
import { EmptyState } from '../components/ui/EmptyState'
import { Skeleton } from '../components/ui/Skeleton'
import { Badge } from '../components/ui/Badge'
import { cn } from '../lib/cn'

// ─── Field definitions ───────────────────────────────────────────────────────

const FIELDS = [
  { value: 'opted_in',       label: 'Opt-in status',    group: 'Contact',  valueType: 'opted_in' },
  { value: 'tag',            label: 'Tag',              group: 'Contact',  valueType: 'tag' },
  { value: 'name',           label: 'Name',             group: 'Contact',  valueType: 'text' },
  { value: 'phone',          label: 'Phone number',     group: 'Contact',  valueType: 'text' },
  { value: 'country',        label: 'Country (prefix)', group: 'Contact',  valueType: 'text' },
  { value: 'email',          label: 'Email',            group: 'Contact',  valueType: 'email' },
  { value: 'created_at',     label: 'Date added',       group: 'Contact',  valueType: 'date' },
  { value: 'message_count',  label: 'Messages sent',    group: 'Activity', valueType: 'number' },
  { value: 'last_messaged',  label: 'Last messaged',    group: 'Activity', valueType: 'last_messaged' },
]

const OPERATORS = {
  text: [
    { value: 'contains',     label: 'contains' },
    { value: 'not_contains', label: 'does not contain' },
    { value: 'starts_with',  label: 'starts with' },
    { value: 'ends_with',    label: 'ends with' },
    { value: 'is',           label: 'is exactly' },
    { value: 'is_not',       label: 'is not' },
  ],
  email: [
    { value: 'has',          label: 'has an email' },
    { value: 'not_has',      label: 'has no email' },
    { value: 'contains',     label: 'contains' },
    { value: 'not_contains', label: 'does not contain' },
    { value: 'is',           label: 'is exactly' },
  ],
  opted_in: [
    { value: 'is', label: 'is' },
  ],
  tag: [
    { value: 'has',      label: 'has tag' },
    { value: 'not_has',  label: 'does not have tag' },
    { value: 'has_any',  label: 'has any tag' },
    { value: 'has_none', label: 'has no tags' },
  ],
  date: [
    { value: 'before',      label: 'before' },
    { value: 'after',       label: 'after' },
    { value: 'within_days', label: 'within last N days' },
  ],
  number: [
    { value: 'eq',  label: '= equals' },
    { value: 'gt',  label: '> greater than' },
    { value: 'gte', label: '≥ at least' },
    { value: 'lt',  label: '< less than' },
    { value: 'lte', label: '≤ at most' },
  ],
  last_messaged: [
    { value: 'never',       label: 'never messaged' },
    { value: 'ever',        label: 'ever messaged' },
    { value: 'within_days', label: 'within last N days' },
    { value: 'before',      label: 'before date' },
    { value: 'after',       label: 'after date' },
  ],
}

const FIELD_MAP = Object.fromEntries(FIELDS.map(f => [f.value, f]))

const defaultOperator = (field) => {
  const vt = FIELD_MAP[field]?.valueType ?? 'text'
  return OPERATORS[vt]?.[0]?.value ?? 'is'
}

const defaultCondition = () => ({ field: 'opted_in', operator: 'is', value: '1' })

const needsValue = (operator) => !['has', 'not_has', 'has_any', 'has_none', 'never', 'ever'].includes(operator)

// ─── Condition Row ────────────────────────────────────────────────────────────

function ConditionRow({ condition, index, tags, logic, isLast, onChange, onRemove }) {
  const field    = FIELD_MAP[condition.field] ?? FIELD_MAP['opted_in']
  const vt       = field.valueType
  const ops      = OPERATORS[vt] ?? OPERATORS.text
  const showVal  = needsValue(condition.operator)

  const handleFieldChange = (newField) => {
    onChange({ field: newField, operator: defaultOperator(newField), value: '' })
  }

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-start gap-2 flex-wrap">
        {/* Logic badge */}
        {index > 0 && (
          <div className="flex items-center h-9 shrink-0">
            <span className={cn(
              'text-[11px] font-bold px-2 py-1 rounded-md border w-10 text-center',
              logic === 'or'
                ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400'
                : 'bg-brand-50 dark:bg-brand-950/40 border-brand-200 dark:border-brand-700 text-brand-700 dark:text-brand-400'
            )}>
              {logic.toUpperCase()}
            </span>
          </div>
        )}

        {/* Row number */}
        {index === 0 && (
          <div className="flex items-center h-9 shrink-0">
            <span className="text-[11px] font-bold px-2 py-1 rounded-md border w-10 text-center bg-[var(--surface-2)] border-[var(--border)] text-[var(--text-tertiary)]">
              WHERE
            </span>
          </div>
        )}

        {/* Field selector */}
        <Select
          value={condition.field}
          onChange={e => handleFieldChange(e.target.value)}
          className="flex-1 min-w-[140px]"
        >
          {['Contact', 'Activity'].map(group => (
            <optgroup key={group} label={group}>
              {FIELDS.filter(f => f.group === group).map(f => (
                <option key={f.value} value={f.value}>{f.label}</option>
              ))}
            </optgroup>
          ))}
        </Select>

        {/* Operator selector */}
        <Select
          value={condition.operator}
          onChange={e => onChange({ ...condition, operator: e.target.value, value: needsValue(e.target.value) ? condition.value : '' })}
          className="flex-1 min-w-[150px]"
        >
          {ops.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
        </Select>

        {/* Value input */}
        {showVal && (
          <div className="flex-1 min-w-[120px]">
            {vt === 'opted_in' && (
              <Select value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })}>
                <option value="1">Opted in</option>
                <option value="0">Opted out</option>
              </Select>
            )}
            {vt === 'tag' && (
              <Select value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })}>
                <option value="">Select tag…</option>
                {tags.map(t => <option key={t.id} value={String(t.id)}>{t.name}</option>)}
              </Select>
            )}
            {vt === 'date' && (
              condition.operator === 'within_days'
                ? <Input type="number" min="1" placeholder="days" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} />
                : <Input type="date" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} />
            )}
            {vt === 'last_messaged' && (
              condition.operator === 'within_days'
                ? <Input type="number" min="1" placeholder="days" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} />
                : ['before', 'after'].includes(condition.operator)
                  ? <Input type="date" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} />
                  : null
            )}
            {vt === 'number' && (
              <Input type="number" min="0" placeholder="0" value={condition.value} onChange={e => onChange({ ...condition, value: e.target.value })} />
            )}
            {(vt === 'text' || vt === 'email') && (
              <Input
                placeholder={
                  condition.field === 'country'  ? 'e.g. +44' :
                  condition.field === 'phone'    ? 'e.g. +447' :
                  condition.field === 'email'    ? 'e.g. @gmail.com' :
                  'value…'
                }
                value={condition.value}
                onChange={e => onChange({ ...condition, value: e.target.value })}
              />
            )}
          </div>
        )}

        {/* Remove */}
        <button
          type="button"
          onClick={onRemove}
          className="h-9 w-9 shrink-0 flex items-center justify-center rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors"
        >
          <Trash2 size={13} />
        </button>
      </div>
    </div>
  )
}

// ─── Condition readable summary ───────────────────────────────────────────────

function conditionSummary(c, tags) {
  const fieldLabel = FIELD_MAP[c.field]?.label ?? c.field
  const ops        = OPERATORS[FIELD_MAP[c.field]?.valueType ?? 'text'] ?? []
  const opLabel    = ops.find(o => o.value === c.operator)?.label ?? c.operator
  let value        = c.value

  if (c.field === 'opted_in') value = c.value === '1' ? 'Opted in' : 'Opted out'
  if (c.field === 'tag') {
    const tag = tags.find(t => String(t.id) === String(c.value))
    value = tag?.name ?? c.value
  }
  if (['has', 'not_has', 'has_any', 'has_none', 'never', 'ever'].includes(c.operator)) {
    return `${fieldLabel} — ${opLabel}`
  }
  return `${fieldLabel} ${opLabel} "${value}"`
}

// ─── Segment Form Modal ───────────────────────────────────────────────────────

function SegmentModal({ open, onClose, onSaved, tags, editSegment }) {
  const { toast }                  = useToast()
  const [form, setForm]            = useState({ name: '', description: '', logic: 'and', conditions: [defaultCondition()] })
  const [saving, setSaving]        = useState(false)
  const [previewCount, setPreview] = useState(null)
  const [previewing, setPreviewing] = useState(false)
  const previewTimer               = useRef(null)

  useEffect(() => {
    if (!open) return
    if (editSegment) {
      setForm({
        name:        editSegment.name        ?? '',
        description: editSegment.description ?? '',
        logic:       editSegment.logic       ?? 'and',
        conditions:  editSegment.conditions?.length ? editSegment.conditions : [defaultCondition()],
      })
    } else {
      setForm({ name: '', description: '', logic: 'and', conditions: [defaultCondition()] })
    }
    setPreview(null)
  }, [open, editSegment])

  const fetchPreview = useCallback((currentForm) => {
    clearTimeout(previewTimer.current)
    previewTimer.current = setTimeout(async () => {
      setPreviewing(true)
      try {
        const res = await api.post('/segments/preview', {
          conditions: currentForm.conditions,
          logic:      currentForm.logic,
        })
        setPreview(res.data.count ?? null)
      } catch {
        setPreview(null)
      } finally {
        setPreviewing(false)
      }
    }, 600)
  }, [])

  const handleFormChange = (updated) => {
    setForm(updated)
    fetchPreview(updated)
  }

  const updateCondition = (i, c) => handleFormChange({ ...form, conditions: form.conditions.map((r, idx) => idx === i ? c : r) })
  const removeCondition = (i) => handleFormChange({ ...form, conditions: form.conditions.filter((_, idx) => idx !== i) })
  const addCondition    = () => handleFormChange({ ...form, conditions: [...form.conditions, defaultCondition()] })

  const save = async () => {
    if (!form.name.trim()) { toast.error('Segment name is required'); return }
    setSaving(true)
    try {
      if (editSegment) {
        await api.put(`/segments/${editSegment.id}`, form)
        toast.success('Segment updated')
      } else {
        await api.post('/segments', form)
        toast.success('Segment created')
      }
      onSaved()
      onClose()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title={editSegment ? 'Edit Segment' : 'New Segment'}
      size="xl"
      footer={
        <div className="flex items-center gap-2 w-full">
          {/* Live count preview */}
          <div className="flex-1">
            {previewing ? (
              <span className="text-xs text-[var(--text-tertiary)] flex items-center gap-1">
                <RefreshCw size={11} className="animate-spin" />
                Calculating…
              </span>
            ) : previewCount !== null ? (
              <span className="text-xs font-semibold text-brand-600 dark:text-brand-400 flex items-center gap-1">
                <Users size={11} />
                ~{previewCount.toLocaleString()} contacts match
              </span>
            ) : null}
          </div>
          <Button variant="ghost" onClick={onClose} disabled={saving}>Cancel</Button>
          <Button onClick={save} loading={saving}>{editSegment ? 'Save Changes' : 'Create Segment'}</Button>
        </div>
      }
    >
      <div className="space-y-5">
        {/* Name + description */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            label="Segment name"
            required
            placeholder="e.g. Active UK patients"
            value={form.name}
            onChange={e => handleFormChange({ ...form, name: e.target.value })}
          />
          <Input
            label="Description (optional)"
            placeholder="What is this segment for?"
            value={form.description}
            onChange={e => handleFormChange({ ...form, description: e.target.value })}
          />
        </div>

        {/* AND / OR logic toggle */}
        <div className="flex items-center gap-3">
          <span className="text-sm font-medium text-[var(--text-primary)]">Match</span>
          <div className="flex rounded-lg border border-[var(--border)] overflow-hidden">
            {['and', 'or'].map(l => (
              <button
                key={l}
                type="button"
                onClick={() => handleFormChange({ ...form, logic: l })}
                className={cn(
                  'px-4 py-1.5 text-xs font-semibold transition-colors',
                  form.logic === l
                    ? l === 'or'
                      ? 'bg-amber-500 text-white'
                      : 'bg-brand-500 text-white'
                    : 'bg-[var(--surface)] text-[var(--text-secondary)] hover:bg-[var(--surface-2)]'
                )}
              >
                {l === 'and' ? 'ALL conditions (AND)' : 'ANY condition (OR)'}
              </button>
            ))}
          </div>
          <span className="text-xs text-[var(--text-tertiary)]">
            {form.logic === 'and' ? 'Contact must match every rule' : 'Contact must match at least one rule'}
          </span>
        </div>

        {/* Conditions */}
        <div>
          <div className="flex items-center justify-between mb-2">
            <p className="text-sm font-semibold text-[var(--text-primary)]">
              Filter conditions
              <span className="ml-2 text-xs font-normal text-[var(--text-tertiary)]">
                ({form.conditions.length} rule{form.conditions.length !== 1 ? 's' : ''})
              </span>
            </p>
            <Button variant="ghost" size="xs" leftIcon={<Plus size={12} />} onClick={addCondition}>
              Add rule
            </Button>
          </div>

          <div className="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] divide-y divide-[var(--border)] overflow-hidden">
            {form.conditions.map((c, i) => (
              <div key={i} className="px-4 py-3">
                <ConditionRow
                  condition={c}
                  index={i}
                  tags={tags}
                  logic={form.logic}
                  isLast={i === form.conditions.length - 1}
                  onChange={updated => updateCondition(i, updated)}
                  onRemove={() => removeCondition(i)}
                />
              </div>
            ))}
            {form.conditions.length === 0 && (
              <div className="px-4 py-5 text-center text-sm text-[var(--text-tertiary)]">
                No rules — all opted-in contacts will match.
              </div>
            )}
          </div>

          {form.conditions.length === 0 && (
            <p className="text-xs text-amber-600 dark:text-amber-400 mt-2">
              Without conditions, this segment will include all opted-in contacts.
            </p>
          )}
        </div>
      </div>
    </Modal>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function SegmentsPage() {
  const { toast }                = useToast()
  const [segments, setSegs]      = useState([])
  const [tags, setTags]          = useState([])
  const [loading, setLoading]    = useState(true)
  const [modalOpen, setModal]    = useState(false)
  const [editSegment, setEdit]   = useState(null)
  const [expanded, setExp]       = useState(null)
  const [deleting, setDeleting]  = useState(null)

  const load = () => {
    setLoading(true)
    Promise.all([
      api.get('/segments'),
      api.get('/tags'),
    ]).then(([s, t]) => {
      setSegs(s.data.data ?? [])
      setTags(t.data.data ?? [])
    }).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  const openCreate = () => { setEdit(null); setModal(true) }
  const openEdit   = (s) => { setEdit(s); setModal(true) }

  const duplicate  = async (s) => {
    try {
      await api.post('/segments', {
        name:       s.name + ' (copy)',
        description: s.description,
        logic:      s.logic ?? 'and',
        conditions: s.conditions ?? [],
      })
      toast.success('Segment duplicated')
      load()
    } catch {
      toast.error('Duplicate failed')
    }
  }

  const remove = async (s) => {
    if (!confirm(`Delete segment "${s.name}"? This cannot be undone.`)) return
    setDeleting(s.id)
    try {
      await api.delete(`/segments/${s.id}`)
      toast.success('Segment deleted')
      load()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Delete failed')
    } finally {
      setDeleting(null)
    }
  }

  const totalContacts = segments.reduce((sum, s) => sum + (s.eligible_count ?? 0), 0)

  return (
    <div className="space-y-5">
      <PageHeader
        title="Segments"
        subtitle={`${segments.length} segment${segments.length !== 1 ? 's' : ''}`}
        action={
          <Button leftIcon={<Plus size={14} />} onClick={openCreate}>
            New Segment
          </Button>
        }
      />

      {/* Stats row */}
      {segments.length > 0 && (
        <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <Card className="py-3 px-4">
            <p className="text-xs text-[var(--text-tertiary)]">Total Segments</p>
            <p className="text-2xl font-bold text-[var(--text-primary)] mt-0.5">{segments.length}</p>
          </Card>
          <Card className="py-3 px-4">
            <p className="text-xs text-[var(--text-tertiary)]">Contacts covered</p>
            <p className="text-2xl font-bold text-[var(--text-primary)] mt-0.5">{totalContacts.toLocaleString()}</p>
          </Card>
        </div>
      )}

      {loading ? (
        <div className="space-y-3">
          {[...Array(4)].map((_, i) => <Skeleton key={i} className="h-20 rounded-xl" />)}
        </div>
      ) : !segments.length ? (
        <Card>
          <EmptyState
            icon={Layers}
            title="No segments yet"
            description="Segments let you target specific groups of contacts using powerful rules — like tag, country, activity, and more."
            action={<Button leftIcon={<Plus size={14} />} onClick={openCreate}>Create First Segment</Button>}
          />
        </Card>
      ) : (
        <div className="space-y-2">
          {segments.map((s, i) => {
            const conds    = s.conditions ?? []
            const isExpanded = expanded === s.id
            const logic    = s.logic ?? 'and'

            return (
              <motion.div
                key={s.id}
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: i * 0.03 }}
                className="rounded-xl bg-[var(--surface)] border border-[var(--border)] shadow-[var(--shadow-sm)] overflow-hidden"
              >
                <div className="flex items-center gap-3 px-5 py-4">
                  {/* Icon */}
                  <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400">
                    <Filter size={16} />
                  </div>

                  {/* Info */}
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <p className="font-semibold text-sm text-[var(--text-primary)]">{s.name}</p>
                      {conds.length > 0 && (
                        <span className={cn(
                          'text-[10px] font-bold px-1.5 py-0.5 rounded-md border',
                          logic === 'or'
                            ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 text-amber-700 dark:text-amber-400'
                            : 'bg-brand-50 dark:bg-brand-950/40 border-brand-200 text-brand-700 dark:text-brand-400'
                        )}>
                          {logic.toUpperCase()}
                        </span>
                      )}
                    </div>
                    <div className="flex items-center gap-3 mt-0.5 flex-wrap">
                      <span className="text-xs text-[var(--text-tertiary)] flex items-center gap-1">
                        <Users size={10} />
                        {(s.eligible_count ?? 0).toLocaleString()} contacts
                      </span>
                      {(s.campaigns_count ?? 0) > 0 && (
                        <span className="text-xs text-[var(--text-tertiary)]">
                          {s.campaigns_count} campaign{s.campaigns_count !== 1 ? 's' : ''}
                        </span>
                      )}
                      {conds.length > 0 && (
                        <span className="text-xs text-[var(--text-tertiary)]">
                          {conds.length} rule{conds.length !== 1 ? 's' : ''}
                        </span>
                      )}
                      {s.description && (
                        <span className="text-xs text-[var(--text-tertiary)] truncate max-w-[200px]">
                          {s.description}
                        </span>
                      )}
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex items-center gap-1 shrink-0">
                    {conds.length > 0 && (
                      <button
                        onClick={() => setExp(isExpanded ? null : s.id)}
                        className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
                        title="View rules"
                      >
                        {isExpanded ? <ChevronUp size={13} /> : <ChevronDown size={13} />}
                      </button>
                    )}
                    <button
                      onClick={() => openEdit(s)}
                      className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
                      title="Edit segment"
                    >
                      <Edit2 size={13} />
                    </button>
                    <button
                      onClick={() => duplicate(s)}
                      className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
                      title="Duplicate segment"
                    >
                      <Copy size={13} />
                    </button>
                    <button
                      onClick={() => remove(s)}
                      disabled={deleting === s.id}
                      className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors disabled:opacity-50"
                      title="Delete segment"
                    >
                      <Trash2 size={13} />
                    </button>
                  </div>
                </div>

                {/* Expanded conditions */}
                <AnimatePresence>
                  {isExpanded && conds.length > 0 && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: 'auto', opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{ duration: 0.18 }}
                      className="border-t border-[var(--border)] bg-[var(--surface-2)] overflow-hidden"
                    >
                      <div className="px-5 py-3">
                        <p className="text-[10px] font-bold text-[var(--text-tertiary)] uppercase tracking-wide mb-2">
                          Rules ({logic === 'or' ? 'Match ANY' : 'Match ALL'})
                        </p>
                        <div className="flex flex-wrap gap-1.5">
                          {conds.map((c, ci) => (
                            <div key={ci} className="flex items-center gap-1">
                              {ci > 0 && (
                                <span className={cn(
                                  'text-[10px] font-bold',
                                  logic === 'or' ? 'text-amber-600' : 'text-brand-600'
                                )}>
                                  {logic.toUpperCase()}
                                </span>
                              )}
                              <Badge variant="default" size="sm" className="font-mono text-[11px]">
                                {conditionSummary(c, tags)}
                              </Badge>
                            </div>
                          ))}
                        </div>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
              </motion.div>
            )
          })}
        </div>
      )}

      <SegmentModal
        open={modalOpen}
        onClose={() => { setModal(false); setEdit(null) }}
        onSaved={load}
        tags={tags}
        editSegment={editSegment}
      />
    </div>
  )
}
