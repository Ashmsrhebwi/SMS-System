import React from 'react'
import { Plus, Trash2, SlidersHorizontal } from 'lucide-react'
import { Button } from '../../components/ui/Button'
import { Input, Select } from '../../components/ui/Input'
import { cn } from '../../lib/cn'

// ─── Field & operator config ──────────────────────────────────────────────────

export const FILTER_FIELDS = [
  { value: 'name',       label: 'Name',           valueType: 'text' },
  { value: 'phone',      label: 'Phone',          valueType: 'text' },
  { value: 'country',    label: 'Country (code)', valueType: 'text' },
  { value: 'email',      label: 'Email',          valueType: 'email' },
  { value: 'opted_in',   label: 'Opt-in status',  valueType: 'opted_in' },
  { value: 'tag',        label: 'Tag',            valueType: 'tag' },
  { value: 'created_at', label: 'Date added',     valueType: 'date' },
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
  ],
  opted_in: [
    { value: 'is', label: 'is' },
  ],
  tag: [
    { value: 'has',      label: 'has specific tag' },
    { value: 'not_has',  label: 'does not have tag' },
    { value: 'has_any',  label: 'has any tag' },
    { value: 'has_none', label: 'has no tags' },
  ],
  date: [
    { value: 'after',       label: 'after' },
    { value: 'before',      label: 'before' },
    { value: 'within_days', label: 'within last N days' },
  ],
}

export const FIELD_MAP = Object.fromEntries(FILTER_FIELDS.map(f => [f.value, f]))

// Operators that truly need NO value input (field-aware)
export function needsNoValue(field, operator) {
  if (operator === 'has_any' || operator === 'has_none') return true
  if ((field === 'email') && (operator === 'has' || operator === 'not_has')) return true
  return false
}

const defaultOperator = (field) => OPERATORS[FIELD_MAP[field]?.valueType ?? 'text']?.[0]?.value ?? 'contains'
export const blankCondition = () => ({ field: 'name', operator: 'contains', value: '' })

// Human-readable label for active filter chips
export function conditionLabel(c, tags) {
  const field   = FIELD_MAP[c.field]?.label ?? c.field
  const vt      = FIELD_MAP[c.field]?.valueType ?? 'text'
  const ops     = OPERATORS[vt] ?? []
  const opLabel = ops.find(o => o.value === c.operator)?.label ?? c.operator
  if (needsNoValue(c.field, c.operator)) return `${field}: ${opLabel}`
  if (c.field === 'opted_in')            return `Status: ${c.value === '1' ? 'Opted in' : 'Opted out'}`
  if (c.field === 'tag') {
    const tag = tags?.find(t => String(t.id) === String(c.value))
    return `Tag ${opLabel}: ${tag?.name ?? c.value}`
  }
  return `${field} ${opLabel} "${c.value}"`
}

// ─── Single condition row ─────────────────────────────────────────────────────

function ConditionRow({ condition, index, logic, tags, onChange, onRemove }) {
  const vt      = FIELD_MAP[condition.field]?.valueType ?? 'text'
  const ops     = OPERATORS[vt] ?? OPERATORS.text
  const noValue = needsNoValue(condition.field, condition.operator)

  const handleFieldChange = (newField) => {
    onChange({ field: newField, operator: defaultOperator(newField), value: '' })
  }

  return (
    <div className="flex items-center gap-2 flex-wrap">
      {/* AND / OR chip */}
      <div className="w-14 shrink-0 flex justify-center">
        {index === 0 ? (
          <span className="text-[10px] font-bold text-[var(--text-tertiary)] uppercase tracking-wider">Where</span>
        ) : (
          <span className={cn(
            'text-[10px] font-bold px-2 py-0.5 rounded-md',
            logic === 'or'
              ? 'bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400'
              : 'bg-brand-50 dark:bg-brand-950/50 text-brand-700 dark:text-brand-400'
          )}>
            {logic.toUpperCase()}
          </span>
        )}
      </div>

      {/* Field selector */}
      <Select
        value={condition.field}
        onChange={e => handleFieldChange(e.target.value)}
        className="flex-1 min-w-[120px]"
      >
        {FILTER_FIELDS.map(f => <option key={f.value} value={f.value}>{f.label}</option>)}
      </Select>

      {/* Operator selector */}
      <Select
        value={condition.operator}
        onChange={e => onChange({
          ...condition,
          operator: e.target.value,
          value: needsNoValue(condition.field, e.target.value) ? '' : condition.value,
        })}
        className="flex-1 min-w-[155px]"
      >
        {ops.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
      </Select>

      {/* Value input — hidden when operator needs no value */}
      {!noValue && (
        <div className="flex-1 min-w-[120px]">
          {vt === 'opted_in' && (
            <Select
              value={condition.value || '1'}
              onChange={e => onChange({ ...condition, value: e.target.value })}
            >
              <option value="1">Opted in</option>
              <option value="0">Opted out</option>
            </Select>
          )}
          {vt === 'tag' && (
            <Select
              value={condition.value}
              onChange={e => onChange({ ...condition, value: e.target.value })}
            >
              <option value="">Select tag…</option>
              {tags?.map(t => <option key={t.id} value={String(t.id)}>{t.name}</option>)}
            </Select>
          )}
          {vt === 'date' && (
            condition.operator === 'within_days'
              ? <Input
                  type="number"
                  min="1"
                  placeholder="e.g. 30"
                  value={condition.value}
                  onChange={e => onChange({ ...condition, value: e.target.value })}
                />
              : <Input
                  type="date"
                  value={condition.value}
                  onChange={e => onChange({ ...condition, value: e.target.value })}
                />
          )}
          {(vt === 'text' || vt === 'email') && (
            <Input
              placeholder={
                condition.field === 'country' ? 'e.g. +44' :
                condition.field === 'phone'   ? 'e.g. +447' :
                condition.field === 'email'   ? 'e.g. @gmail.com' : 'value…'
              }
              value={condition.value}
              onChange={e => onChange({ ...condition, value: e.target.value })}
            />
          )}
        </div>
      )}

      {/* Remove button */}
      <button
        type="button"
        onClick={onRemove}
        className="h-9 w-9 shrink-0 flex items-center justify-center rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-500 transition-colors"
      >
        <Trash2 size={13} />
      </button>
    </div>
  )
}

// ─── Main panel ───────────────────────────────────────────────────────────────

export function ContactFiltersPanel({ conditions, logic, tags, onChange, onLogicChange, onApply, onReset }) {
  const add    = () => onChange([...conditions, blankCondition()])
  const remove = (i) => onChange(conditions.filter((_, idx) => idx !== i))
  const update = (i, c) => onChange(conditions.map((r, idx) => idx === i ? c : r))

  const hasIncomplete = conditions.some(c => {
    const vt = FIELD_MAP[c.field]?.valueType ?? 'text'
    if (needsNoValue(c.field, c.operator)) return false
    if (vt === 'opted_in') return false
    return !c.value?.trim()
  })

  return (
    <div className="rounded-xl border border-[var(--border)] bg-[var(--surface)] shadow-[var(--shadow-sm)] overflow-hidden">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 bg-[var(--surface-2)] border-b border-[var(--border)]">
        <div className="flex items-center gap-2">
          <SlidersHorizontal size={14} className="text-brand-500" />
          <span className="text-sm font-semibold text-[var(--text-primary)]">Advanced Filters</span>
          {conditions.length > 0 && (
            <span className="text-xs text-[var(--text-tertiary)]">
              {conditions.length} condition{conditions.length !== 1 ? 's' : ''}
            </span>
          )}
        </div>

        {/* ALL / ANY toggle */}
        <div className="flex items-center gap-2">
          <span className="text-xs text-[var(--text-tertiary)]">Match</span>
          <div className="flex rounded-lg border border-[var(--border)] overflow-hidden text-xs">
            {['and', 'or'].map(l => (
              <button
                key={l}
                type="button"
                onClick={() => onLogicChange(l)}
                className={cn(
                  'px-3 py-1 font-semibold transition-colors',
                  logic === l
                    ? l === 'or' ? 'bg-amber-500 text-white' : 'bg-brand-500 text-white'
                    : 'bg-[var(--surface)] text-[var(--text-secondary)] hover:bg-[var(--surface-2)]'
                )}
              >
                {l === 'and' ? 'ALL' : 'ANY'}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Condition rows */}
      <div className="p-4 space-y-2.5">
        {conditions.length === 0 ? (
          <div className="text-center py-4">
            <p className="text-sm text-[var(--text-tertiary)]">No conditions yet.</p>
            <p className="text-xs text-[var(--text-tertiary)] mt-0.5">Click "Add condition" to start filtering.</p>
          </div>
        ) : (
          conditions.map((c, i) => (
            <ConditionRow
              key={i}
              condition={c}
              index={i}
              logic={logic}
              tags={tags}
              onChange={updated => update(i, updated)}
              onRemove={() => remove(i)}
            />
          ))
        )}
      </div>

      {/* Footer */}
      <div className="flex items-center justify-between px-4 py-3 border-t border-[var(--border)] bg-[var(--surface-2)]">
        <Button variant="ghost" size="sm" leftIcon={<Plus size={12} />} onClick={add}>
          Add condition
        </Button>
        <div className="flex items-center gap-2">
          <Button variant="ghost" size="sm" onClick={onReset}>
            Reset
          </Button>
          <Button
            size="sm"
            onClick={onApply}
            disabled={hasIncomplete}
          >
            Apply filters
          </Button>
        </div>
      </div>
    </div>
  )
}
