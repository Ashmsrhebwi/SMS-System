import React, { useCallback, useEffect, useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import {
  Search, Upload, Download, Plus, Users, Trash2, X,
  Pencil, Settings2, SlidersHorizontal, ShieldOff, RefreshCw
} from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { getPhoneCountry } from '../lib/phoneCountry'
import { ContactDrawer } from '../features/contacts/ContactDrawer'
import { AddContactModal } from '../features/contacts/AddContactModal'
import { EditContactModal } from '../features/contacts/EditContactModal'
import { ImportContactsModal } from '../features/contacts/ImportContactsModal'
import { BulkUpdateModal } from '../features/contacts/BulkUpdateModal'
import { ContactFiltersPanel, blankCondition, conditionLabel, needsNoValue, FIELD_MAP, CONTACT_STATUSES } from '../features/contacts/ContactFiltersPanel'
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

const PER_PAGE_OPTIONS = [10, 20, 30, 50, 100, 200, 300, 500]

const STATUS_COLORS = {
  active:         'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
  inactive:       'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
  interested:     'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-400',
  follow_up:      'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
  not_interested: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
}

function ContactStatusBadge({ status }) {
  const label = CONTACT_STATUSES.find(s => s.value === status)?.label ?? status ?? 'Active'
  return (
    <span className={cn('inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide', STATUS_COLORS[status] ?? STATUS_COLORS.active)}>
      {label}
    </span>
  )
}

export default function ContactsPage() {
  const { toast } = useToast()
  const [searchParams, setSearchParams] = useSearchParams()

  // ── Restore state from URL ─────────────────────────────────────────────────
  const [data,    setData]    = useState(null)
  const [tags,    setTags]    = useState([])
  const [loading, setLoading] = useState(true)

  const [page,      setPage]    = useState(() => parseInt(searchParams.get('page') || '1', 10))
  const [perPage,   setPerPage] = useState(() => parseInt(searchParams.get('per_page') || '50', 10))
  const [search,    setSearch]  = useState(() => searchParams.get('search') || '')
  const [tagFilter, setTag]     = useState(() => searchParams.get('tag') || '')
  const [optIn,     setOptIn]   = useState(() => searchParams.get('opt_in') || '')

  const [filterOpen,      setFilterOpen]   = useState(false)
  const [draftConditions, setDraftConds]   = useState([])
  const [draftLogic,      setDraftLogic]   = useState('and')
  const [appliedConds,    setAppliedConds] = useState(() => {
    try { return JSON.parse(searchParams.get('filters') || '[]') } catch { return [] }
  })
  const [appliedLogic,    setAppliedLogic] = useState(() => searchParams.get('filter_logic') || 'and')

  const [drawerContact, setDrawer]      = useState(null)
  const [addOpen,       setAddOpen]     = useState(false)
  const [importOpen,    setImportOpen]  = useState(false)
  const [editContact,   setEditContact] = useState(null)
  const [bulkOpen,      setBulkOpen]    = useState(false)
  const [selected,      setSelected]    = useState(new Set())
  const [deleting,      setDeleting]    = useState(false)
  const [suppressing,   setSuppressing] = useState(false)

  const activeCount = appliedConds.length

  // ── Sync state → URL (preserve filters across page refreshes) ─────────────
  const syncUrl = useCallback((overrides = {}) => {
    const p = {
      page:         String(overrides.page         ?? page),
      per_page:     String(overrides.perPage       ?? perPage),
      search:       overrides.search               ?? search,
      tag:          overrides.tagFilter            ?? tagFilter,
      opt_in:       overrides.optIn                ?? optIn,
      filters:      JSON.stringify(overrides.conds ?? appliedConds),
      filter_logic: overrides.logic                ?? appliedLogic,
    }
    // Clean empty params
    Object.keys(p).forEach(k => { if (!p[k] || p[k] === '[]') delete p[k] })
    setSearchParams(p, { replace: true })
  }, [page, perPage, search, tagFilter, optIn, appliedConds, appliedLogic, setSearchParams])

  // ── Core load ─────────────────────────────────────────────────────────────
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

  // Reset page on filter change
  useEffect(() => { setPage(1) }, [search, tagFilter, optIn, perPage, appliedConds, appliedLogic])

  useEffect(() => {
    doLoad(page)
    syncUrl({ page })
  }, [page, doLoad]) // eslint-disable-line react-hooks/exhaustive-deps

  useEffect(() => {
    api.get('/tags').then(r => setTags(r.data.data ?? []))
  }, [])

  // ── Filter actions ────────────────────────────────────────────────────────
  const applyFilters = () => {
    const valid = draftConditions.filter(c => {
      const vt = FIELD_MAP[c.field]?.valueType ?? 'text'
      if (needsNoValue(c.field, c.operator)) return true
      if (vt === 'opted_in' || vt === 'status' || vt === 'source') return true
      return c.value?.trim()
    })
    setAppliedConds(valid)
    setAppliedLogic(draftLogic)
    setFilterOpen(false)
    syncUrl({ conds: valid, logic: draftLogic })
  }

  const resetFilters = () => {
    setDraftConds([])
    setDraftLogic('and')
    setAppliedConds([])
    setAppliedLogic('and')
    setFilterOpen(false)
    syncUrl({ conds: [], logic: 'and' })
  }

  const removeChip = (i) => {
    const next = appliedConds.filter((_, idx) => idx !== i)
    setAppliedConds(next)
    setDraftConds([...next])
    syncUrl({ conds: next })
  }

  const openFilterPanel = () => {
    setDraftConds(appliedConds.length ? [...appliedConds] : [])
    setDraftLogic(appliedLogic)
    setFilterOpen(v => !v)
  }

  // ── Table helpers ─────────────────────────────────────────────────────────
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
      clearSelection()
      doLoad(page)
    } catch {
      toast.error('Bulk delete failed')
    } finally {
      setDeleting(false)
    }
  }

  const bulkSuppress = async () => {
    if (!confirm(`Add ${selected.size} contact${selected.size === 1 ? '' : 's'} to Suppression List? They will be opted out.`)) return
    setSuppressing(true)
    try {
      const res = await api.post('/contacts/bulk-suppress', { ids: [...selected] })
      toast.success(res.data.message)
      clearSelection()
      doLoad(page)
    } catch {
      toast.error('Bulk suppress failed')
    } finally {
      setSuppressing(false)
    }
  }

  const handleExport = () => {
    const params = new URLSearchParams()
    if (search)    params.set('search', search)
    if (tagFilter) params.set('tag_id', tagFilter)
    if (optIn)     params.set('opt_in', optIn)
    if (appliedConds.length) {
      params.set('filters', JSON.stringify(appliedConds))
      params.set('filter_logic', appliedLogic)
    }
    if (selected.size > 0) params.set('ids', [...selected].join(','))
    window.open(`/api/v1/contacts/export?${params}`, '_blank')
  }

  const handleEditClick = (e, contact) => { e.stopPropagation(); setEditContact(contact) }

  const handlePageChange = (p) => {
    setPage(p)
    syncUrl({ page: p })
  }

  const handlePerPageChange = (n) => {
    setPerPage(n)
    syncUrl({ perPage: n, page: 1 })
  }

  const handleSearchChange = (v) => {
    setSearch(v)
    syncUrl({ search: v, page: 1 })
  }

  const handleTagChange = (v) => {
    setTag(v)
    syncUrl({ tagFilter: v, page: 1 })
  }

  const handleOptInChange = (v) => {
    setOptIn(v)
    syncUrl({ optIn: v, page: 1 })
  }

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
            <Button variant="secondary" leftIcon={<Download size={14} />} onClick={handleExport}>
              {selected.size > 0 ? `Export ${selected.size}` : 'Export'}
            </Button>
          </div>
        }
      />

      {/* ── Toolbar ──────────────────────────────────────────────────────────── */}
      <div className="flex flex-col sm:flex-row gap-3 flex-wrap">
        <Input
          placeholder="Search name, phone, email…"
          leftIcon={<Search size={14} />}
          value={search}
          onChange={e => handleSearchChange(e.target.value)}
          className="sm:w-72"
        />
        <Select value={tagFilter} onChange={e => handleTagChange(e.target.value)} className="sm:w-40">
          <option value="">All tags</option>
          {tags.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
        </Select>
        <Select value={optIn} onChange={e => handleOptInChange(e.target.value)} className="sm:w-36">
          <option value="">All statuses</option>
          <option value="1">Opted in</option>
          <option value="0">Opted out</option>
        </Select>

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
          <span className="text-xs text-[var(--text-tertiary)] whitespace-nowrap">Rows</span>
          <Select value={perPage} onChange={e => handlePerPageChange(Number(e.target.value))} className="w-20">
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
              search={search}
              tagFilter={tagFilter}
              optIn={optIn}
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
              <button onClick={() => removeChip(i)} className="ml-0.5 hover:text-brand-900 dark:hover:text-white transition-colors">
                <X size={10} />
              </button>
            </span>
          ))}
          {appliedConds.length > 1 && (
            <span className="text-xs text-[var(--text-tertiary)] font-medium px-1.5 py-0.5 rounded bg-[var(--surface-2)]">
              {appliedLogic === 'or' ? 'matching ANY' : 'matching ALL'}
            </span>
          )}
          <button onClick={resetFilters} className="text-xs text-[var(--text-tertiary)] hover:text-red-500 transition-colors ml-1">
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
                <Button variant="secondary" onClick={() => { handleSearchChange(''); handleTagChange(''); handleOptInChange(''); resetFilters() }}>
                  Clear all filters
                </Button>
              ) : null
            }
          />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[800px]">
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
                  {['Contact', 'Phone', 'Country', 'Language', 'Status', 'Tags', 'Opt-in', 'Added', ''].map((h, i) => (
                    <th key={i} className="px-3 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide whitespace-nowrap">
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]">
                {rows.map((c, i) => {
                  const isSelected = selected.has(c.id)
                  const ct = getPhoneCountry(c.phone)
                  return (
                    <motion.tr
                      key={c.id}
                      className={cn(
                        'group transition-colors',
                        isSelected ? 'bg-brand-50 dark:bg-brand-950/40' : 'hover:bg-[var(--surface-2)]'
                      )}
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      transition={{ delay: i * 0.01 }}
                    >
                      <td className="pl-4 pr-2 py-3" onClick={e => e.stopPropagation()}>
                        <input
                          type="checkbox"
                          checked={isSelected}
                          onChange={() => toggleOne(c.id)}
                          className="h-4 w-4 rounded border-[var(--border)] text-brand-600 focus:ring-brand-500 cursor-pointer"
                        />
                      </td>
                      <td className="px-3 py-3 cursor-pointer" onClick={() => setDrawer(c.id)}>
                        <div className="flex items-center gap-2">
                          <Avatar name={c.name} size="sm" />
                          <span className="text-sm font-medium text-[var(--text-primary)] whitespace-nowrap">{c.name}</span>
                        </div>
                      </td>
                      <td className="px-3 py-3 text-sm text-[var(--text-secondary)] cursor-pointer whitespace-nowrap" onClick={() => setDrawer(c.id)}>
                        {c.phone}
                      </td>
                      <td className="px-3 py-3 cursor-pointer" onClick={() => setDrawer(c.id)}>
                        <div className="flex items-center gap-1.5 whitespace-nowrap">
                          {ct && <span className="text-base leading-none">{ct.flag}</span>}
                          <span className="text-xs text-[var(--text-secondary)]">{c.country || ct?.name || '—'}</span>
                        </div>
                      </td>
                      <td className="px-3 py-3 text-xs text-[var(--text-secondary)] cursor-pointer whitespace-nowrap" onClick={() => setDrawer(c.id)}>
                        {c.language || '—'}
                      </td>
                      <td className="px-3 py-3 cursor-pointer" onClick={() => setDrawer(c.id)}>
                        <ContactStatusBadge status={c.status} />
                      </td>
                      <td className="px-3 py-3 cursor-pointer" onClick={() => setDrawer(c.id)}>
                        <div className="flex flex-wrap gap-1">
                          {c.tags?.slice(0, 2).map(t => <Badge key={t.id} variant="brand" size="sm">{t.name}</Badge>)}
                          {c.tags?.length > 2 && <Badge variant="default" size="sm">+{c.tags.length - 2}</Badge>}
                        </div>
                      </td>
                      <td className="px-3 py-3 cursor-pointer" onClick={() => setDrawer(c.id)}>
                        <StatusBadge status={c.opted_in ? 'opted_in' : 'opted_out'} />
                      </td>
                      <td className="px-3 py-3 text-xs text-[var(--text-tertiary)] cursor-pointer whitespace-nowrap" onClick={() => setDrawer(c.id)}>
                        {new Date(c.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-2 py-3 w-10" onClick={e => e.stopPropagation()}>
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
          </div>
          <Pagination meta={data?.meta} onPageChange={handlePageChange} />
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
            <Button variant="secondary" size="sm" leftIcon={<Settings2 size={13} />} onClick={() => setBulkOpen(true)}>
              Mass Update
            </Button>
            <Button variant="secondary" size="sm" leftIcon={<Download size={13} />} onClick={handleExport}>
              Export
            </Button>
            <Button
              variant="secondary"
              size="sm"
              leftIcon={<ShieldOff size={13} />}
              loading={suppressing}
              onClick={bulkSuppress}
              className="text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/30"
            >
              Suppress
            </Button>
            <Button variant="danger" size="sm" leftIcon={<Trash2 size={13} />} loading={deleting} onClick={bulkDelete}>
              Delete
            </Button>
            <button
              onClick={clearSelection}
              className="p-1.5 rounded-lg text-[var(--text-tertiary)] hover:text-[var(--text-primary)] hover:bg-[var(--surface-2)] transition-colors"
            >
              <X size={14} />
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      <AddContactModal    open={addOpen}      onClose={() => setAddOpen(false)}       onCreated={() => doLoad(page)}   tags={tags} />
      <EditContactModal   contact={editContact} open={!!editContact} onClose={() => setEditContact(null)} onUpdated={() => doLoad(page)} tags={tags} />
      <ImportContactsModal open={importOpen}  onClose={() => setImportOpen(false)}    onImported={() => doLoad(1)} />
      <BulkUpdateModal    open={bulkOpen}     onClose={() => setBulkOpen(false)}      selectedIds={[...selected]} onUpdated={() => { doLoad(page); clearSelection() }} tags={tags} />
      <ContactDrawer      contactId={drawerContact} open={!!drawerContact} onClose={() => setDrawer(null)} onUpdate={() => doLoad(page)} />
    </div>
  )
}
