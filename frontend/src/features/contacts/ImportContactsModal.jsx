import React, { useCallback, useRef, useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import {
  Upload, FileSpreadsheet, CheckCircle2, AlertCircle,
  Copy, AlertTriangle, Download, RotateCcw, X, Loader2,
} from 'lucide-react'
import { createPortal } from 'react-dom'
import { cn } from '../../lib/cn'
import api from '../../services/api'
import { useToast } from '../../context/ToastContext'

// ─── helpers ─────────────────────────────────────────────────────────────────

function downloadCsv(rows, filename) {
  if (!rows?.length) return
  const headers = Object.keys(rows[0])
  const csv = [
    headers.join(','),
    ...rows.map(r =>
      headers.map(h => {
        const val = r[h] == null ? '' : String(r[h])
        return val.includes(',') || val.includes('"') || val.includes('\n')
          ? `"${val.replace(/"/g, '""')}"`
          : val
      }).join(',')
    ),
  ].join('\r\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}

// ─── stat card ───────────────────────────────────────────────────────────────

function StatCard({ icon: Icon, label, count, color, onDownload, downloadable }) {
  const colors = {
    green:  'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800',
    blue:   'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-800',
    amber:  'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800',
    red:    'bg-red-50 dark:bg-red-950/40 border-red-200 dark:border-red-800',
  }
  const iconColors = {
    green: 'text-emerald-600 dark:text-emerald-400',
    blue:  'text-blue-600 dark:text-blue-400',
    amber: 'text-amber-600 dark:text-amber-400',
    red:   'text-red-600 dark:text-red-400',
  }
  const countColors = {
    green: 'text-emerald-700 dark:text-emerald-300',
    blue:  'text-blue-700 dark:text-blue-300',
    amber: 'text-amber-700 dark:text-amber-300',
    red:   'text-red-700 dark:text-red-300',
  }

  return (
    <div className={cn('rounded-xl border p-4 flex flex-col gap-2', colors[color])}>
      <div className="flex items-center justify-between">
        <Icon size={18} className={iconColors[color]} />
        {downloadable && count > 0 && (
          <button
            onClick={onDownload}
            className={cn(
              'flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-md transition-colors',
              'hover:bg-black/10 dark:hover:bg-white/10',
              iconColors[color]
            )}
            title="Download list as CSV"
          >
            <Download size={11} />
            CSV
          </button>
        )}
      </div>
      <div className={cn('text-2xl font-bold tabular-nums', countColors[color])}>{count}</div>
      <div className="text-xs font-medium text-[var(--text-tertiary)] uppercase tracking-wide">{label}</div>
    </div>
  )
}

// ─── records table ────────────────────────────────────────────────────────────

function RecordsTable({ rows, color }) {
  if (!rows?.length) return null
  const borderColor = color === 'amber'
    ? 'border-amber-200 dark:border-amber-800'
    : 'border-red-200 dark:border-red-800'
  const headerBg = color === 'amber'
    ? 'bg-amber-50 dark:bg-amber-950/40'
    : 'bg-red-50 dark:bg-red-950/40'

  return (
    <div className={cn('rounded-xl border overflow-hidden', borderColor)}>
      <table className="w-full text-xs">
        <thead>
          <tr className={cn('border-b', borderColor, headerBg)}>
            <th className="px-3 py-2 text-left font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">Row</th>
            <th className="px-3 py-2 text-left font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">Name</th>
            <th className="px-3 py-2 text-left font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">Phone</th>
            <th className="px-3 py-2 text-left font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">Reason</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-[var(--border)]">
          {rows.slice(0, 50).map((r, i) => (
            <tr key={i} className="hover:bg-[var(--surface-2)]">
              <td className="px-3 py-2 text-[var(--text-tertiary)] tabular-nums">{r.row}</td>
              <td className="px-3 py-2 text-[var(--text-primary)]">{r.name || '—'}</td>
              <td className="px-3 py-2 text-[var(--text-secondary)] font-mono">{r.phone || '—'}</td>
              <td className="px-3 py-2 text-[var(--text-secondary)]">{r.reason}</td>
            </tr>
          ))}
        </tbody>
      </table>
      {rows.length > 50 && (
        <div className="px-3 py-2 text-xs text-[var(--text-tertiary)] bg-[var(--surface-2)] border-t border-[var(--border)]">
          Showing first 50 of {rows.length} records. Download CSV for the full list.
        </div>
      )}
    </div>
  )
}

// ─── main modal ──────────────────────────────────────────────────────────────

export function ImportContactsModal({ open, onClose, onImported }) {
  const { toast } = useToast()
  const inputRef  = useRef(null)

  const [step, setStep]         = useState('upload')    // 'upload' | 'loading' | 'results'
  const [dragOver, setDragOver] = useState(false)
  const [results, setResults]   = useState(null)
  const [activeTab, setTab]     = useState('duplicates')

  const reset = () => {
    setStep('upload')
    setResults(null)
    setTab('duplicates')
    setDragOver(false)
    if (inputRef.current) inputRef.current.value = ''
  }

  const handleClose = () => {
    if (step === 'loading') return
    reset()
    onClose()
  }

  const processFile = useCallback(async (file) => {
    if (!file) return
    const allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     'application/vnd.ms-excel', 'text/csv', 'application/csv']
    const ext = file.name.split('.').pop().toLowerCase()
    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
      toast.error('Please upload an Excel (.xlsx, .xls) or CSV file.')
      return
    }

    setStep('loading')
    const fd = new FormData()
    fd.append('file', file)
    fd.append('duplicate_action', 'skip')

    try {
      const res = await api.post('/contacts/import', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      setResults(res.data)
      setStep('results')
      if (res.data.imported > 0 || res.data.updated > 0) {
        onImported?.()
      }
    } catch (err) {
      const msg = err?.response?.data?.message ?? 'Import failed. Please try again.'
      toast.error(msg)
      setStep('upload')
    }
  }, [onImported, toast])

  const handleFileInput = (e) => {
    processFile(e.target.files?.[0])
    if (inputRef.current) inputRef.current.value = ''
  }

  const handleDrop = (e) => {
    e.preventDefault()
    setDragOver(false)
    processFile(e.dataTransfer.files?.[0])
  }

  const handleDragOver = (e) => { e.preventDefault(); setDragOver(true) }
  const handleDragLeave = () => setDragOver(false)

  if (!open) return null

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop */}
      <motion.div
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        transition={{ duration: 0.15 }}
        onClick={handleClose}
      />

      {/* Panel */}
      <motion.div
        className={cn(
          'relative w-full bg-[var(--surface)] rounded-2xl shadow-[var(--shadow-xl)]',
          'border border-[var(--border)] flex flex-col',
          step === 'results' ? 'max-w-2xl max-h-[90vh]' : 'max-w-lg'
        )}
        initial={{ opacity: 0, scale: 0.95, y: 8 }}
        animate={{ opacity: 1, scale: 1, y: 0 }}
        exit={{ opacity: 0, scale: 0.95, y: 8 }}
        transition={{ duration: 0.18, ease: [0.25, 0.46, 0.45, 0.94] }}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-[var(--border)] shrink-0">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-lg bg-brand-100 dark:bg-brand-900/40 flex items-center justify-center">
              <FileSpreadsheet size={16} className="text-brand-600 dark:text-brand-400" />
            </div>
            <div>
              <h2 className="text-base font-semibold text-[var(--text-primary)]">Import Contacts</h2>
              <p className="text-xs text-[var(--text-tertiary)]">
                {step === 'results' ? 'Import complete — review the results below' : 'Upload an Excel or CSV file'}
              </p>
            </div>
          </div>
          <button
            onClick={handleClose}
            disabled={step === 'loading'}
            className="rounded-lg p-1.5 text-[var(--text-tertiary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)] transition-colors disabled:opacity-40"
          >
            <X size={16} />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-6 py-5">
          <AnimatePresence mode="wait">

            {/* ── UPLOAD STEP ── */}
            {step === 'upload' && (
              <motion.div
                key="upload"
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                className="space-y-5"
              >
                {/* Drop zone */}
                <div
                  onDrop={handleDrop}
                  onDragOver={handleDragOver}
                  onDragLeave={handleDragLeave}
                  className={cn(
                    'relative flex flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed py-12 px-6 transition-all duration-200',
                    dragOver
                      ? 'border-brand-400 bg-brand-50 dark:bg-brand-950/30 scale-[1.01]'
                      : 'border-[var(--border)] hover:border-brand-300 hover:bg-[var(--surface-2)]'
                  )}
                >
                  <div className={cn(
                    'w-14 h-14 rounded-2xl flex items-center justify-center transition-colors',
                    dragOver ? 'bg-brand-100 dark:bg-brand-900/40' : 'bg-[var(--surface-2)]'
                  )}>
                    <Upload size={24} className={dragOver ? 'text-brand-500' : 'text-[var(--text-tertiary)]'} />
                  </div>
                  <div className="text-center">
                    <p className="text-sm font-medium text-[var(--text-primary)]">
                      {dragOver ? 'Drop your file here' : 'Drag & drop your file here'}
                    </p>
                    <p className="mt-1 text-xs text-[var(--text-tertiary)]">Supports .xlsx, .xls, .csv files</p>
                  </div>
                  <label className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium cursor-pointer transition-colors shadow-sm">
                    <FileSpreadsheet size={14} />
                    Choose File
                    <input
                      ref={inputRef}
                      type="file"
                      accept=".xlsx,.xls,.csv"
                      onChange={handleFileInput}
                      className="hidden"
                    />
                  </label>
                </div>

                {/* Column spec */}
                <div className="rounded-xl border border-[var(--border)] overflow-hidden">
                  <div className="px-4 py-3 bg-[var(--surface-2)] border-b border-[var(--border)]">
                    <p className="text-xs font-semibold text-[var(--text-secondary)] uppercase tracking-wide">
                      Expected Columns
                    </p>
                  </div>
                  <div className="divide-y divide-[var(--border)]">
                    {[
                      { col: 'Full Name',    note: 'Required', req: true },
                      { col: 'Phone Number', note: 'Required — international format preferred', req: true },
                      { col: 'Email',        note: 'Optional', req: false },
                      { col: 'Notes',        note: 'Optional', req: false },
                    ].map(({ col, note, req }) => (
                      <div key={col} className="flex items-center justify-between px-4 py-2.5">
                        <div className="flex items-center gap-2">
                          <code className="text-xs font-mono bg-[var(--surface-2)] px-2 py-0.5 rounded border border-[var(--border)]">
                            {col}
                          </code>
                          <span className="text-xs text-[var(--text-tertiary)]">{note}</span>
                        </div>
                        {req ? (
                          <span className="text-xs font-medium text-red-500">Required</span>
                        ) : (
                          <span className="text-xs text-[var(--text-tertiary)]">Optional</span>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              </motion.div>
            )}

            {/* ── LOADING STEP ── */}
            {step === 'loading' && (
              <motion.div
                key="loading"
                initial={{ opacity: 0, scale: 0.97 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0 }}
                className="flex flex-col items-center justify-center gap-5 py-16"
              >
                <div className="relative">
                  <div className="w-16 h-16 rounded-2xl bg-brand-100 dark:bg-brand-900/40 flex items-center justify-center">
                    <FileSpreadsheet size={28} className="text-brand-600 dark:text-brand-400" />
                  </div>
                  <div className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-[var(--surface)] border-2 border-[var(--border)] flex items-center justify-center">
                    <Loader2 size={12} className="animate-spin text-brand-500" />
                  </div>
                </div>
                <div className="text-center">
                  <p className="text-sm font-semibold text-[var(--text-primary)]">Processing your file…</p>
                  <p className="mt-1 text-xs text-[var(--text-tertiary)]">Validating and importing contacts</p>
                </div>
              </motion.div>
            )}

            {/* ── RESULTS STEP ── */}
            {step === 'results' && results && (
              <motion.div
                key="results"
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                className="space-y-5"
              >
                {/* Stat cards */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                  <StatCard
                    icon={CheckCircle2}
                    label="Imported"
                    count={results.imported ?? 0}
                    color="green"
                  />
                  <StatCard
                    icon={Copy}
                    label="Updated"
                    count={results.updated ?? 0}
                    color="blue"
                  />
                  <StatCard
                    icon={AlertTriangle}
                    label="Duplicates"
                    count={results.duplicates?.length ?? 0}
                    color="amber"
                    downloadable
                    onDownload={() => downloadCsv(
                      results.duplicates.map(r => ({
                        Row: r.row, 'Full Name': r.name, Phone: r.phone, Email: r.email ?? '', Reason: r.reason,
                      })),
                      'duplicates.csv'
                    )}
                  />
                  <StatCard
                    icon={AlertCircle}
                    label="Errors"
                    count={results.errors?.length ?? 0}
                    color="red"
                    downloadable
                    onDownload={() => downloadCsv(
                      results.errors.map(r => ({
                        Row: r.row, 'Full Name': r.name, Phone: r.phone, Email: r.email ?? '', Reason: r.reason,
                      })),
                      'errors.csv'
                    )}
                  />
                </div>

                {/* Detail tabs */}
                {((results.duplicates?.length ?? 0) > 0 || (results.errors?.length ?? 0) > 0) && (
                  <div className="space-y-3">
                    {/* Tab switcher */}
                    <div className="flex gap-1 p-1 rounded-lg bg-[var(--surface-2)] border border-[var(--border)] w-fit">
                      {[
                        { key: 'duplicates', label: `Duplicates (${results.duplicates?.length ?? 0})`, color: 'amber' },
                        { key: 'errors',     label: `Errors (${results.errors?.length ?? 0})`,         color: 'red'   },
                      ].map(({ key, label }) => (
                        <button
                          key={key}
                          onClick={() => setTab(key)}
                          className={cn(
                            'px-3 py-1.5 rounded-md text-xs font-medium transition-colors',
                            activeTab === key
                              ? 'bg-[var(--surface)] text-[var(--text-primary)] shadow-sm border border-[var(--border)]'
                              : 'text-[var(--text-tertiary)] hover:text-[var(--text-secondary)]'
                          )}
                        >
                          {label}
                        </button>
                      ))}
                    </div>

                    {/* Tab content */}
                    {activeTab === 'duplicates' && (
                      <RecordsTable rows={results.duplicates} color="amber" />
                    )}
                    {activeTab === 'errors' && (
                      <RecordsTable rows={results.errors} color="red" />
                    )}
                  </div>
                )}
              </motion.div>
            )}
          </AnimatePresence>
        </div>

        {/* Footer */}
        {step === 'results' && (
          <div className="px-6 py-4 border-t border-[var(--border)] flex items-center justify-between gap-3 shrink-0 bg-[var(--surface-2)] rounded-b-2xl">
            <button
              onClick={reset}
              className="flex items-center gap-2 text-sm text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors"
            >
              <RotateCcw size={14} />
              Import another file
            </button>
            <button
              onClick={handleClose}
              className="px-5 py-2 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium transition-colors shadow-sm"
            >
              Done
            </button>
          </div>
        )}
      </motion.div>
    </div>,
    document.body
  )
}
