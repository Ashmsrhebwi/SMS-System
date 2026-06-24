import React, { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell, LogOut, Moon, Sun, Menu, ChevronDown, Settings, Search } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import { useTheme } from '../../context/ThemeContext'
import { useAuth } from '../../context/AuthContext'
import { useToast } from '../../context/ToastContext'
import { CommandPalette } from '../CommandPalette'
import { Avatar } from '../ui/Avatar'
import { Tooltip } from '../ui/Tooltip'
import { cn } from '../../lib/cn'

// Detect Mac vs Windows for keyboard hint
const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPod|iPad/.test(navigator.platform)
const MOD   = isMac ? '⌘' : 'Ctrl'

export function TopBar({ onMobileMenuOpen }) {
  const { isDark, toggleTheme } = useTheme()
  const { user, logout }        = useAuth()
  const { toast }               = useToast()
  const navigate                = useNavigate()
  const [menuOpen, setMenuOpen] = useState(false)
  const [paletteOpen, setPaletteOpen] = useState(false)
  const menuRef                 = useRef(null)

  // Global ⌘K / Ctrl+K shortcut
  useEffect(() => {
    const handler = (e) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault()
        setPaletteOpen(v => !v)
      }
    }
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [])

  const handleLogout = async () => {
    setMenuOpen(false)
    await logout()
    toast.success('Signed out successfully')
    navigate('/login')
  }

  return (
    <>
      <CommandPalette open={paletteOpen} onClose={() => setPaletteOpen(false)} />

      <header className="h-[60px] flex items-center justify-between gap-3 px-4 md:px-6 border-b border-[var(--border)] bg-[var(--surface)] shrink-0">
        {/* Mobile menu trigger */}
        <button
          onClick={onMobileMenuOpen}
          className="lg:hidden p-2 -ml-2 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)] transition-colors"
        >
          <Menu size={18} />
        </button>

        {/* Search bar — command palette trigger */}
        <button
          onClick={() => setPaletteOpen(true)}
          className={cn(
            'hidden md:flex items-center gap-2.5 flex-1 max-w-xs',
            'h-9 px-3 rounded-lg',
            'border border-[var(--border)] bg-[var(--surface-2)]',
            'text-sm text-[var(--text-tertiary)]',
            'hover:border-brand-400 hover:bg-[var(--surface-accent)]',
            'transition-all duration-150 group'
          )}
        >
          <Search size={14} className="shrink-0 group-hover:text-brand-500 transition-colors" />
          <span className="flex-1 text-left text-[var(--text-tertiary)]">Search or jump to…</span>
          <div className="flex items-center gap-0.5 shrink-0">
            <kbd className="flex h-5 items-center rounded border border-[var(--border)] bg-[var(--surface)] px-1.5 text-[10px] font-mono text-[var(--text-tertiary)]">
              {MOD}
            </kbd>
            <kbd className="flex h-5 items-center rounded border border-[var(--border)] bg-[var(--surface)] px-1.5 text-[10px] font-mono text-[var(--text-tertiary)]">
              K
            </kbd>
          </div>
        </button>

        {/* Mobile: search icon only */}
        <button
          onClick={() => setPaletteOpen(true)}
          className="md:hidden p-2 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] transition-colors"
        >
          <Search size={18} />
        </button>

        {/* Spacer */}
        <div className="flex-1 md:flex-none" />

        {/* Right controls */}
        <div className="flex items-center gap-1">
          {/* Theme toggle */}
          <Tooltip content={isDark ? 'Light mode' : 'Dark mode'} side="bottom">
            <button
              onClick={toggleTheme}
              className="p-2 rounded-lg text-[var(--text-secondary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)] transition-colors"
            >
              <motion.div
                key={isDark ? 'moon' : 'sun'}
                initial={{ rotate: -20, opacity: 0 }}
                animate={{ rotate: 0, opacity: 1 }}
                transition={{ duration: 0.15 }}
              >
                {isDark ? <Sun size={16} /> : <Moon size={16} />}
              </motion.div>
            </button>
          </Tooltip>

          {/* User menu */}
          <div className="relative" ref={menuRef}>
            <button
              onClick={() => setMenuOpen(v => !v)}
              className={cn(
                'flex items-center gap-2 rounded-lg pl-2 pr-2.5 py-1.5',
                'text-[var(--text-secondary)] hover:bg-[var(--surface-2)] transition-colors',
                menuOpen && 'bg-[var(--surface-2)]'
              )}
            >
              <Avatar name={user?.name} size="xs" />
              <span className="hidden sm:block text-sm font-medium text-[var(--text-primary)] max-w-[120px] truncate">
                {user?.name}
              </span>
              <ChevronDown size={12} className={cn('transition-transform duration-150', menuOpen && 'rotate-180')} />
            </button>

            <AnimatePresence>
              {menuOpen && (
                <>
                  <div className="fixed inset-0 z-10" onClick={() => setMenuOpen(false)} />
                  <motion.div
                    className={cn(
                      'absolute right-0 top-full mt-1.5 z-20 w-56',
                      'bg-[var(--surface)] rounded-xl border border-[var(--border)] shadow-[var(--shadow-xl)]',
                      'overflow-hidden'
                    )}
                    initial={{ opacity: 0, y: -6, scale: 0.96 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: -6, scale: 0.96 }}
                    transition={{ duration: 0.12 }}
                  >
                    {/* User info */}
                    <div className="px-3 py-3 border-b border-[var(--border)]">
                      <div className="flex items-center gap-2.5">
                        <Avatar name={user?.name} size="sm" />
                        <div className="min-w-0">
                          <p className="text-sm font-semibold text-[var(--text-primary)] truncate">{user?.name}</p>
                          <p className="text-xs text-[var(--text-tertiary)] truncate">{user?.email}</p>
                        </div>
                      </div>
                    </div>

                    <div className="p-1">
                      <button
                        onClick={() => { setMenuOpen(false); navigate('/settings') }}
                        className="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-[var(--text-primary)] hover:bg-[var(--surface-2)] rounded-lg transition-colors"
                      >
                        <Settings size={14} />
                        Settings
                        <span className="ml-auto text-xs text-[var(--text-tertiary)] font-mono capitalize">
                          {user?.role}
                        </span>
                      </button>

                      <div className="my-1 border-t border-[var(--border)]" />

                      <button
                        onClick={handleLogout}
                        className="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors"
                      >
                        <LogOut size={14} />
                        Sign out
                      </button>
                    </div>
                  </motion.div>
                </>
              )}
            </AnimatePresence>
          </div>
        </div>
      </header>
    </>
  )
}
