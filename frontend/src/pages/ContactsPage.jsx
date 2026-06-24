import React, { useCallback, useEffect, useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Search, Upload, Download, Plus, Users, Trash2, X, Pencil, Settings2, SlidersHorizontal } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { getPhoneCountry } from '../lib/phoneCountry'
import { ContactDrawer } from '../features/contacts/ContactDrawer'
import { AddContactModal } from '../features/contacts/AddContactModal'
import { EditContactModal } from '../features/contacts/EditContactModal'
import { ImportContactsModal } from '../features/contacts/ImportContactsModal'
import { BulkUpdateModal } from '../features/contacts/BulkUpdateModal'
import { ContactFiltersPanel, blankCondition, conditionLabel, needsNoValue, FIELD_MAP } from '../features/contacts/ContactFiltersPanel'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'
import { Avatar } from '../components/ui/Avatar'
import { cn } from '../lib/cn'

const PER_PAGE_OPTIONS = [10, 20, 30, 40, 50, 100]

export default function ContactsPage() {
  const { toast } = useToast()

  // ── Data & pagination ────────────────────────────────────────────────────────
  const [data,    setData]    = useState(null)
  const [tags,    setTags]    = useState([])
  const [page,    setPage]    = useState(1)
  const [loading, setLoading] = useState(true)

  // ── Simple toolbar filters ───────────────────────────────────────────────────
  const [perPage,   setPerPage]  = useState(50)
  const [search,    setSearch]   = useState('')
  const [tagFilter, setTag]      = useState('')
  const [optIn,     setOptIn]    = useState('')

  // ── Advanced filter panel ────────────────────────────────────────────────────
  const [filterOpen,      setFilterOpen]   = useState(false)
  const [draftConditions, setDraftConds]   = useState([])  // working state inside panel
  const [draftLogic,      setDraftLogic]   = useState('and')
  const [appliedConds,    setAppliedConds] = useState([])  // sent to API
  const [appliedLogic,    setAppliedLogic] = useState('and')

  // ── Selection & bulk actions ─────────────────────────────────────────────────
  const [drawerContact, setDrawer]     = useState(null)
  const [addOpen,       setAddOpen]    = useState(false)
  const [importOpen,    setImportOpen] = useState(false)
  const [editContact,   setEditContact]= useState(null)
  const [bulkOpen,      setBulkOpen]   = useState(false)
  const [selected,      setSelected]   = useState(new Set())
  const [deleting,      setDeleting]   = useState(false)

  const activeCount = appliedConds.length

  // ── Core load function ───────────────────────────────────────────────────────
  // useCallback recreates when ANY filter changes.
  // The [page, doLoad] useEffect below fires whenever page OR doLoad changes —
  // this guarantees a reload on every filter/page change with no stale closures.
  const doLoad = useCallback((p) => {
    setLoading(true)
    const params = { page: p, per_page: perPage, search, tag: tagFilter, opt_in: optIn }
    if (appliedConds.length > 0) {
      params.filters      = JSON.stringify(appliedConds)
      params.filter_logic = appliedLogic
    }
    api.get('/contacts', { params })
      .then(r => { setData(r.data); setSelected(new Set()) })
      .catch(() => toast.error('Failed to load contacts'))
      .finally(() => setLoading(false))
  }, [perPage, search, tagFilter, optIn, appliedConds, appliedLogic]) // eslint-disable-line react-hooks/exhaustive-deps

  // Reset to page 1 whenever any filter changes
  useEffect(() => {
    setPage(1)
  }, [search, tagFilter, optIn, perPage, appliedConds, appliedLogic])

  // Reload whenever page changes OR any filter changes (doLoad is recreated on filter change)
  useEffect(() => {
    doLoad(page)
  }, [page, doLoad]) // eslint-disable-line react-hooks/exhaustive-deps

  // Fetch tags once on mount
  useEffect(() => {
    api.get('/tags').then(r => setTags(r.data.data ?? []))
  }, [])

  // ── Apply / reset / remove chip ───────────────────────────────────────────────
  const applyFilters = () => {
    const valid = draftConditions.filter(c => {
      const vt = FIELD_MAP[c.field]?.valueType ?? 'text'
      if (needsNoValue(c.field, c.operator)) return true
      if (vt === 'opted_in') return true
      return c.value?.trim()
    })
    setAppliedConds(valid)
    setAppliedLogic(draftLogic)
    setFilterOpen(false)
    // State change triggers doLoad automatically via useEffect
  }

  const resetFilters = () => {
    setDraftConds([])
    setDraftLogic('and')
    setAppliedConds([])
    setAppliedLogic('and')
    setFilterOpen(false)
  }

  const removeChip = (i) => {
    const next = appliedConds.filter((_, idx) => idx !== i)
    setAppliedConds(next)
    setDraftConds([...next])
  }

  const openFilterPanel = () => {
    setDraftConds(appliedConds.length ? [...appliedConds] : [])
    setDraftLogic(appliedLogic)
    setFilterOpen(v => !v)
  }

  // ── Table helpers ─────────────────────────────────────────────────────────────
  const rows        = data?.data ?? []
  const allIds      = rows.map(c => c.id)
  const allChecked  = allIds.length > 0 && allIds.every(id => selected.has(id))
  const someChecked = allIds.some(id => selected.has(id)) && !allChecked

  const toggleOne = (id) =>
    setSelected(prev => { const n = new Set(prev); n.has(id) ? n.delete(id) : n.add(id); return n })

  const toggleAll = () => {
    if (allChecked) {
      setSelected(prev => { const n = new Set(prev); allIds.forEach(id => n.delete(id)); return n })
    } else {
      setSelected(prev => new Set([...prev, ...allIds]))
    }
  }

  const clearSelection = () => setSelected(new Set())

  const bulkDelete = async () => {
    if (!confirm(`Delete ${selected.size} contact${selected.size === 1 ? '' : 's'}? This cannot be undone.`)) return
    setDeleting(true)
    try {
      await api.post('/contacts/bulk-delete', { ids: [...selected] })
      toast.success(`Deleted ${selected.size} contact${selected.size === 1 ? '' : 's'}`)
      doLoad(page)
    } catch {
      toast.error('Bulk delete failed')
    } finally {
      setDeleting(false)
    }
  }

  const bulkExport        = () => window.open(`/api/v1/contacts/export?ids=${[...selected].join(',')}`, '_blank')
  const handleEditClick   = (e, contact) => { e.stopPropagation(); setEditContact(contact) }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Contacts"
        subtitle={`${data?.meta?.total?.toLocaleString() ?? 0} contacts`}
        action={
          <div className="flex items-center gap-2">
            <Button leftIcon={<Plus size={14} />} onClick={() => setAddOpen(true)}>Add Contact</Button>
            <button
              onClick={() => setImportOpen(true)}
              className="inline-flex items-center justify-center gap-2 h-9 px-4 text-sm font-medium rounded-lg border border-[var(--border)] bg-[var(--surface)] text-[var(--text-primary)] hover:bg-[var(--surface-2)] cursor-pointer select-none shadow-xs transition-colors duration-150"
            >
              <Upload size={14} />
              Import
            </button>
            <a href="/api/v1/contacts/export" target="_blank" rel="noreferrer">
              <Button variant="secondary" leftIcon={<Download size={14} />}>Export</Button>
            </a>
          </div>
        }
      />

      {/* ── Toolbar ──────────────────────────────────────────────────────────── */}
      <div className="flex flex-col sm:flex-row gap-3 flex-wrap">
        <Input
          placeholder="Search name, phone, email…"
          leftIcon={<Search size={14} />}
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="sm:w-72"
        />
        <Select value={tagFilter} onChange={e => setTag(e.target.value)} className="sm:w-40">
          <option value="">All tags</option>
          {tags.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={optIn} onChange={e => setOptIn(e.target.value)} className="sm:w-36">
          <option value="">All statuses</option>
          <option value="1">Opted in</option>
          <option value="0">Opted out</option>
        </Select>

        {/* Advanced filter toggle */}
        <button
          onClick={openFilterPanel}
          className={cn(
            'inline-flex items-center gap-2 h-9 px-3 text-sm font-medium rounded-lg border transition-colors',
            filterOpen || activeCount > 0
              ? 'border-brand-400 bg-brand-50 dark:bg-brand-950/50 text-brand-700 dark:text-brand-400'
              : 'border-[var(--border)] bg-[var(--surface)] text-[var(--text-secondary)] hover:bg-[var(--surface-2)]'
          )}
        >
          <SlidersHorizontal size={13} />
          Filters
          {activeCount > 0 && (
            <span className="flex h-4 w-4 items-center justify-center rounded-full bg-brand-500 text-[10px] font-bold text-white">
              {activeCount}
            </span>
          )}
        </button>

        <div className="flex items-center gap-2 sm:ml-auto">
          <span className="text-xs text-[var(--text-tertiary)] whitespace-nowrap">Rows per page</span>
          <Select value={perPage} onChange={e => setPerPage(Number(e.target.value))} className="w-20">
            {PER_PAGE_OPTIONS.map(n => <option key={n} value={n}>{n}</option>)}
          </Select>
        </div>
      </div>

      {/* ── Advanced filter panel ─────────────────────────────────────────────── */}
      <AnimatePresence>
        {filterOpen && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: 'auto' }}
            exit={{ opacity: 0, height: 0 }}
            transition={{ duration: 0.18 }}
            className="overflow-hidden"
          >
            <ContactFiltersPanel
              conditions={draftConditions}
              logic={draftLogic}
              tags={tags}
              onChange={setDraftConds}
              onLogicChange={setDraftLogic}
              onApply={applyFilters}
              onReset={resetFilters}
            />
          </motion.div>
        )}
      </AnimatePresence>

      {/* ── Active filter chips ───────────────────────────────────────────────── */}
      {activeCount > 0 && (
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-xs font-medium text-[var(--text-tertiary)]">Active:</span>
          {appliedConds.map((c, i) => (
            <span
              key={i}
              className="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-brand-50 dark:bg-brand-950/50 border border-brand-200 dark:border-brand-800 text-brand-700 dark:text-brand-300"
            >
              {conditionLabel(c, tags)}
              <button
                onClick={() => removeChip(i)}
                className="ml-0.5 hover:text-brand-900 dark:hover:text-white transition-colors"
                title="Remove filter"
              >
                <X size={10} />
              </button>
            </span>
          ))}
          {appliedConds.length > 1 && (
            <span className="text-xs text-[var(--text-tertiary)] font-medium px-1.5 py-0.5 rounded bg-[var(--surface-2)]">
              {appliedLogic === 'or' ? 'matching ANY' : 'matching ALL'}
            </span>
          )}
          <button
            onClick={resetFilters}
            className="text-xs text-[var(--text-tertiary)] hover:text-red-500 transition-colors ml-1"
          >
            Clear all
          </button>
        </div>
      )}

      {/* ── Table ────────────────────────────────────────────────────────────── */}
      {loading ? (
        <SkeletonTable rows={8} />
      ) : !rows.length ? (
        <Card>
          <EmptyState
            icon={Users}
            title="No contacts found"
            description={
              activeCount > 0 || search || tagFilter || optIn
                ? 'No contacts match your current filters.'
                : 'Import contacts or add them manually.'
            }
            action={
              (activeCount > 0 || search || tagFilter || optIn) ? (
                <Button variant="secondary" onClick={() => { setSearch(''); setTag(''); setOptIn(''); resetFilters() }}>
                  Clear all filters
                </Button>
              ) : null
            }
          />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                <th className="pl-4 pr-2 py-3 w-10">
                  <input
                    type="checkbox"
                    checked={allChecked}
                    ref={el => { if (el) el.indeterminate = someChecked }}
                    onChange={toggleAll}
                    className="h-4 w-4 rounded border-[var(--border)] text-brand-600 focus:ring-brand-500 cursor-pointer"
                  />
                </th>
                {['Contact', 'Phone', 'Email', 'Tags', 'Status', 'Added', ''].map((h, i) => (
                  <th key={i} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {rows.map((c, i) => {
                const isSelected = selected.has(c.id)
                return (
                  <motion.tr
                    key={c.id}
                    className={cn(
                      'group transition-colors',
                      isSelected ? 'bg-brand-50 dark:bg-brand-950/40' : 'hover:bg-[var(--surface-2)]'
                    )}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: i * 0.015 }}
                  >
                    <td className="pl-4 pr-2 py-3.5" onClick={e => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => toggleOne(c.id)}
                        className="h-4 w-4 rounded border-[var(--border)] text-brand-600 focus:ring-brand-500 cursor-pointer"
                      />
                    </td>
                    <td className="px-4 py-3.5 cursor-pointer" onClick={() => setDrawer(c.id)}>
                      <div className="flex items-center gap-3">
                        <Avatar name={c.name} size="sm" />
                        <span className="text-sm font-medium text-[var(--text-primary)]">{c.name}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)] cursor-pointer" onClick={() => setDrawer(c.id)}>
                      <div className="flex items-center gap-1.5">
                        {(() => { const ct = getPhoneCountry(c.phone); return ct ? <span title={ct.name} className="text-base leading-none">{ct.flag}</span> : null })()}
                        <span>{c.phone}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)] cursor-pointer" onClick={() => setDrawer(c.id)}>
                      {c.email ?? '—'}
                    </td>
                    <td className="px-4 py-3.5 cursor-pointer" onClick={() => setDrawer(c.id)}>
                      <div className="flex flex-wrap gap-1">
                        {c.tags?.slice(0, 2).map(t => <Badge key={t.id} variant="brand" size="sm">{t.name}</Badge>)}
                        {c.tags?.length > 2 && <Badge variant="default" size="sm">+{c.tags.length - 2}</Badge>}
                      </div>
                    </td>
                    <td className="px-4 py-3.5 cursor-pointer" onClick={() => setDrawer(c.id)}>
                      <StatusBadge status={c.opted_in ? 'opted_in' : 'opted_out'} />
                    </td>
                    <td className="px-4 py-3.5 text-xs text-[var(--text-tertiary)] cursor-pointer" onClick={() => setDrawer(c.id)}>
                      {new Date(c.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-3 py-3.5 w-10" onClick={e => e.stopPropagation()}>
                      <button
                        onClick={e => handleEditClick(e, c)}
                        className="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg text-[var(--text-tertiary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface)] transition-all"
                        title="Edit contact"
                      >
                        <Pencil size={13} />
                      </button>
                    </td>
                  </motion.tr>
                )
              })}
            </tbody>
          </table>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </Card>
      )}

      {/* ── Floating bulk-action bar ──────────────────────────────────────────── */}
      <AnimatePresence>
        {selected.size > 0 && (
          <motion.div
            className={cn(
              'fixed bottom-6 left-1/2 z-50 -translate-x-1/2',
              'flex items-center gap-2 px-4 py-2.5',
              'bg-[var(--surface)] rounded-2xl border border-[var(--border)]',
              'shadow-[var(--shadow-xl)]'
            )}
            initial={{ opacity: 0, y: 20, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 20, scale: 0.95 }}
            transition={{ type: 'spring', damping: 28, stiffness: 320 }}
          >
            <span className="text-sm font-semibold text-[var(--text-primary)] tabular-nums min-w-[5rem]">
              {selected.size} selected
            </span>
            <div className="w-px h-5 bg-[var(--border)]" />
            <Button variant="secondary" size="sm" leftIcon={<Settings2 size={13} />} onClick={() => setBulkOpen(true)}>Mass Update</Button>
            <Button variant="secondary" size="sm" leftIcon={<Download size={13} />} onClick={bulkExport}>Export</Button>
            <Button variant="danger"    size="sm" leftIcon={<Trash2   size={13} />} loading={deleting} onClick={bulkDelete}>Delete</Button>
            <button
              onClick={clearSelection}
              className="p-1.5 rounded-lg text-[var(--text-tertiary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-2)] transition-colors"
            >
              <X size={14} />
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      <AddContactModal    open={addOpen}      onClose={() => setAddOpen(false)}      onCreated={() => doLoad(1)}       tags={tags} />
      <EditContactModal   contact={editContact} open={!!editContact} onClose={() => setEditContact(null)} onUpdated={() => doLoad(page)} tags={tags} />
      <ImportContactsModal open={importOpen}  onClose={() => setImportOpen(false)}   onImported={() => doLoad(1)} />
      <BulkUpdateModal    open={bulkOpen}     onClose={() => setBulkOpen(false)}     selectedIds={[...selected]}        onUpdated={() => { doLoad(page); clearSelection() }} tags={tags} />
      <ContactDrawer      contactId={drawerContact} open={!!drawerContact} onClose={() => setDrawer(null)} onUpdate={() => doLoad(page)} />
    </div>
  )
}
