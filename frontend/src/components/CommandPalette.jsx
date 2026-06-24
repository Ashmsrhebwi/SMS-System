import React, { useCallback, useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { useNavigate } from 'react-router-dom'
import { AnimatePresence, motion } from 'framer-motion'
import {
  Search, Send, Users, FileText, Tag, Target, BarChart3,
  UserCog, Settings, ClipboardList, Ban, ArrowRight, Hash,
  Loader2, X,
} from 'lucide-react'
import { cn } from '../lib/cn'
import api from '../services/api'

// ── Static quick-nav items ────────────────────────────────────────────────────
const NAV_ITEMS = [
  { label: 'Dashboard',    href: '/',           icon: Send,          group: 'Navigate' },
  { label: 'Campaigns',    href: '/campaigns',  icon: Send,          group: 'Navigate' },
  { label: 'Contacts',     href: '/contacts',   icon: Users,         group: 'Navigate' },
  { label: 'Templates',    href: '/templates',  icon: FileText,      group: 'Navigate' },
  { label: 'Tags',         href: '/tags',       icon: Tag,           group: 'Navigate' },
  { label: 'Segments',     href: '/segments',   icon: Target,        group: 'Navigate' },
  { label: 'Reports',      href: '/reports',    icon: BarChart3,     group: 'Navigate' },
  { label: 'Users',        href: '/users',      icon: UserCog,       group: 'Navigate' },
  { label: 'Settings',     href: '/settings',   icon: Settings,      group: 'Navigate' },
  { label: 'Audit Logs',   href: '/audit-logs', icon: ClipboardList, group: 'Navigate' },
  { label: 'Blacklist',    href: '/blacklist',  icon: Ban,           group: 'Navigate' },
  { label: 'New Campaign', href: '/campaigns/new', icon: Send,       group: 'Actions' },
]

const TYPE_ICON = { contact: Users, campaign: Send, template: FileText }

export function CommandPalette({ open, onClose }) {
  const navigate          = useNavigate()
  const inputRef          = useRef(null)
  const listRef           = useRef(null)
  const [query, setQuery] = useState('')
  const [results, setResults] = useState([])
  const [searching, setSearching] = useState(false)
  const [cursor, setCursor]       = useState(0)
  const debounceRef               = useRef(null)

  // Focus input when opened
  useEffect(() => {
    if (open) {
      setQuery('')
      setResults([])
      setCursor(0)
      setTimeout(() => inputRef.current?.focus(), 50)
    }
  }, [open])

  // Search API
  const search = useCallback((q) => {
    clearTimeout(debounceRef.current)
    if (q.length < 2) { setResults([]); setSearching(false); return }
    setSearching(true)
    debounceRef.current = setTimeout(async () => {
      try {
        const res = await api.get('/search', { params: { q } })
        setResults(res.data.results ?? [])
      } catch {
        setResults([])
      } finally {
        setSearching(false)
      }
    }, 220)
  }, [])

  useEffect(() => { search(query) }, [query])

  // Filtered nav items
  const navItems = query.length >= 1
    ? NAV_ITEMS.filter(i => i.label.toLowerCase().includes(query.toLowerCase()))
    : NAV_ITEMS.slice(0, 6)

  // Merged items for keyboard nav
  const allItems = [
    ...navItems.map(i => ({ ...i, _type: 'nav' })),
    ...results.map(r => ({ ...r, _type: 'result' })),
  ]

  const go = (item) => {
    onClose()
    if (item._type === 'nav') navigate(item.href)
    else navigate(item.href)
  }

  // Keyboard navigation
  const handleKey = (e) => {
    if (e.key === 'ArrowDown')  { e.preventDefault(); setCursor(c => Math.min(c + 1, allItems.length - 1)) }
    if (e.key === 'ArrowUp')    { e.preventDefault(); setCursor(c => Math.max(c - 1, 0)) }
    if (e.key === 'Enter' && allItems[cursor]) go(allItems[cursor])
    if (e.key === 'Escape') onClose()
  }

  // Scroll cursor into view
  useEffect(() => {
    const el = listRef.current?.children[cursor]
    el?.scrollIntoView({ block: 'nearest' })
  }, [cursor])

  // Group results
  const apiGroups = {}
  for (const r of results) {
    const g = r.type === 'contact' ? 'Contacts' : r.type === 'campaign' ? 'Campaigns' : 'Templates'
    if (!apiGroups[g]) apiGroups[g] = []
    apiGroups[g].push(r)
  }

  let itemIndex = 0

  return createPortal(
    <AnimatePresence>
      {open && (
        <div className="fixed inset-0 z-[9950] flex items-start justify-center pt-[15vh] px-4">
          {/* Backdrop */}
          <motion.div
            className="absolute inset-0 bg-black/60 backdrop-blur-sm"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.15 }}
            onClick={onClose}
          />

          {/* Panel */}
          <motion.div
            className="relative w-full max-w-xl bg-[var(--surface)] rounded-2xl shadow-[var(--shadow-xl)] border border-[var(--border)] overflow-hidden"
            initial={{ opacity: 0, scale: 0.96, y: -16 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.96, y: -16 }}
            transition={{ duration: 0.18, ease: [0.25, 0.46, 0.45, 0.94] }}
          >
            {/* Input */}
            <div className="flex items-center gap-3 px-4 py-3.5 border-b border-[var(--border)]">
              {searching
                ? <Loader2 size={16} className="shrink-0 text-[var(--text-tertiary)] animate-spin" />
                : <Search size={16} className="shrink-0 text-[var(--text-tertiary)]" />
              }
              <input
                ref={inputRef}
                className="flex-1 bg-transparent text-[var(--text-primary)] text-sm placeholder:text-[var(--text-tertiary)] focus:outline-none"
                placeholder="Search or jump to…"
                value={query}
                onChange={e => { setQuery(e.target.value); setCursor(0) }}
                onKeyDown={handleKey}
              />
              {query && (
                <button onClick={() => setQuery('')} className="text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors">
                  <X size={14} />
                </button>
              )}
              <kbd className="hidden sm:flex items-center gap-1 rounded border border-[var(--border)] bg-[var(--surface-2)] px-1.5 py-0.5 text-[10px] text-[var(--text-tertiary)] font-mono shrink-0">
                ESC
              </kbd>
            </div>

            {/* Results */}
            <div className="max-h-[400px] overflow-y-auto" ref={listRef}>
              {/* Nav items */}
              {navItems.length > 0 && (
                <div>
                  <p className="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-[var(--text-tertiary)]">
                    {query ? 'Pages' : 'Quick navigation'}
                  </p>
                  {navItems.map((item) => {
                    const Icon = item.icon
                    const isActive = itemIndex === cursor
                    const myIndex = itemIndex++
                    return (
                      <button
                        key={item.href}
                        onClick={() => go({ ...item, _type: 'nav' })}
                        onMouseEnter={() => setCursor(myIndex)}
                        className={cn(
                          'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors',
                          isActive
                            ? 'bg-brand-600 text-white'
                            : 'text-[var(--text-primary)] hover:bg-[var(--surface-2)]'
                        )}
                      >
                        <Icon size={15} className="shrink-0" />
                        <span className="flex-1 text-left">{item.label}</span>
                        <ArrowRight size={13} className={isActive ? 'text-white/60' : 'text-[var(--text-tertiary)]'} />
                      </button>
                    )
                  })}
                </div>
              )}

              {/* API results */}
              {Object.entries(apiGroups).map(([group, items]) => (
                <div key={group}>
                  <p className="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-[var(--text-tertiary)]">{group}</p>
                  {items.map((item) => {
                    const Icon = TYPE_ICON[item.type] ?? Hash
                    const isActive = itemIndex === cursor
                    const myIndex = itemIndex++
                    return (
                      <button
                        key={`${item.type}-${item.id}`}
                        onClick={() => go({ ...item, _type: 'result' })}
                        onMouseEnter={() => setCursor(myIndex)}
                        className={cn(
                          'w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors',
                          isActive
                            ? 'bg-brand-600 text-white'
                            : 'text-[var(--text-primary)] hover:bg-[var(--surface-2)]'
                        )}
                      >
                        <Icon size={15} className="shrink-0" />
                        <span className="flex-1 text-left truncate">{item.label}</span>
                        {item.sub && (
                          <span className={cn('text-xs shrink-0', isActive ? 'text-white/60' : 'text-[var(--text-tertiary)]')}>
                            {item.sub}
                          </span>
                        )}
                      </button>
                    )
                  })}
                </div>
              ))}

              {query.length >= 2 && !searching && results.length === 0 && navItems.length === 0 && (
                <div className="py-12 text-center">
                  <p className="text-sm text-[var(--text-tertiary)]">No results for "<span className="text-[var(--text-primary)]">{query}</span>"</p>
                </div>
              )}

              <div className="h-2" />
            </div>

            {/* Footer */}
            <div className="flex items-center gap-4 px-4 py-2.5 border-t border-[var(--border)] bg-[var(--surface-2)]">
              {[
                { keys: ['↑', '↓'], label: 'navigate' },
                { keys: ['↵'], label: 'open' },
                { keys: ['ESC'], label: 'close' },
              ].map(({ keys, label }) => (
                <div key={label} className="flex items-center gap-1.5">
                  <div className="flex gap-0.5">
                    {keys.map(k => (
                      <kbd key={k} className="rounded border border-[var(--border)] bg-[var(--surface)] px-1.5 py-0.5 text-[10px] text-[var(--text-tertiary)] font-mono">
                        {k}
                      </kbd>
                    ))}
                  </div>
                  <span className="text-[10px] text-[var(--text-tertiary)]">{label}</span>
                </div>
              ))}
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>,
    document.body
  )
}
