import React from 'react'
import { NavLink } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import {
  LayoutDashboard, Send, Users, FileText, Tag, Target,
  BarChart3, UserCog, ChevronLeft, ChevronRight,
  Zap, Settings, ClipboardList, Ban,
} from 'lucide-react'
import { cn } from '../../lib/cn'
import { useAuth } from '../../context/AuthContext'
import { Avatar } from '../ui/Avatar'

const NAV = [
  { to: '/',          label: 'Dashboard',  icon: LayoutDashboard, end: true },
  { to: '/campaigns', label: 'Campaigns',  icon: Send },
  { to: '/contacts',  label: 'Contacts',   icon: Users },
  { to: '/templates', label: 'Templates',  icon: FileText },
  { to: '/tags',      label: 'Tags',       icon: Tag },
  { to: '/segments',  label: 'Segments',   icon: Target },
  { to: '/reports',   label: 'Reports',    icon: BarChart3 },
]

const ADMIN_NAV = [
  { to: '/users',      label: 'Users',      icon: UserCog },
  { to: '/audit-logs', label: 'Audit Logs', icon: ClipboardList },
  { to: '/blacklist',  label: 'Blacklist',  icon: Ban },
]

const BOTTOM_NAV = [
  { to: '/settings', label: 'Settings', icon: Settings },
]

function NavItem({ item, collapsed }) {
  const Icon = item.icon
  return (
    <NavLink
      to={item.to}
      end={item.end}
      className={({ isActive }) => cn(
        'group flex items-center gap-3 rounded-lg px-3 py-2 transition-all duration-150',
        'text-sm font-medium relative',
        isActive
          ? 'bg-brand-600 text-white shadow-[var(--shadow-xs)]'
          : 'text-[var(--text-secondary)] hover:bg-[var(--surface-2)] hover:text-[var(--text-primary)]',
        collapsed && 'justify-center px-0 w-10 mx-auto'
      )}
      title={collapsed ? item.label : undefined}
    >
      <Icon size={16} className="shrink-0" />
      <AnimatePresence initial={false}>
        {!collapsed && (
          <motion.span
            initial={{ opacity: 0, width: 0 }}
            animate={{ opacity: 1, width: 'auto' }}
            exit={{ opacity: 0, width: 0 }}
            transition={{ duration: 0.15 }}
            className="overflow-hidden whitespace-nowrap"
          >
            {item.label}
          </motion.span>
        )}
      </AnimatePresence>
    </NavLink>
  )
}

export function Sidebar({ collapsed, onToggle, onClose, mobile = false }) {
  const { user } = useAuth()

  const sidebarContent = (
    <div className={cn(
      'flex h-full flex-col bg-[var(--surface)] border-r border-[var(--border)]',
      'transition-all duration-200'
    )}>
      {/* Logo */}
      <div className={cn(
        'flex items-center h-[60px] px-4 border-b border-[var(--border)] shrink-0',
        collapsed ? 'justify-center px-0' : 'gap-2.5'
      )}>
        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white shrink-0">
          <Zap size={16} />
        </div>
        <AnimatePresence initial={false}>
          {!collapsed && (
            <motion.div
              initial={{ opacity: 0, width: 0 }}
              animate={{ opacity: 1, width: 'auto' }}
              exit={{ opacity: 0, width: 0 }}
              transition={{ duration: 0.15 }}
              className="overflow-hidden"
            >
              <span className="text-sm font-bold text-[var(--text-primary)] whitespace-nowrap">FeRa Clinic</span>
              <span className="block text-[10px] text-[var(--text-tertiary)] font-medium -mt-0.5 whitespace-nowrap">SMS Platform</span>
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* Navigation */}
      <nav className={cn('flex-1 py-3 overflow-y-auto', collapsed ? 'px-1' : 'px-3')}>
        <div className="space-y-0.5">
          {NAV.map(item => <NavItem key={item.to} item={item} collapsed={collapsed} />)}
        </div>

        {user?.role === 'admin' && (
          <>
            <div className={cn('my-3 border-t border-[var(--border)]', collapsed && 'mx-1')} />
            {!collapsed && (
              <p className="px-3 mb-1.5 text-[10px] font-semibold text-[var(--text-tertiary)] uppercase tracking-widest">
                Admin
              </p>
            )}
            <div className="space-y-0.5">
              {ADMIN_NAV.map(item => <NavItem key={item.to} item={item} collapsed={collapsed} />)}
            </div>
          </>
        )}
      </nav>

      {/* Settings + User section */}
      <div className={cn('border-t border-[var(--border)] shrink-0', collapsed ? 'px-1 py-2' : 'px-3 py-2')}>
        <div className="space-y-0.5 mb-2">
          {BOTTOM_NAV.map(item => <NavItem key={item.to} item={item} collapsed={collapsed} />)}
        </div>
        <div className={cn(
          'pt-2 border-t border-[var(--border)]',
          collapsed ? 'flex justify-center' : ''
        )}>
          {collapsed ? (
            <Avatar name={user?.name} size="sm" />
          ) : (
            <div className="flex items-center gap-2.5 min-w-0 px-1">
              <Avatar name={user?.name} size="sm" />
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-[var(--text-primary)] truncate">{user?.name}</p>
                <p className="text-xs text-[var(--text-tertiary)] capitalize">{user?.role}</p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Toggle button (desktop only) */}
      {!mobile && (
        <button
          onClick={onToggle}
          className={cn(
            'absolute -right-3 top-[72px] z-10',
            'flex h-6 w-6 items-center justify-center rounded-full',
            'bg-[var(--surface)] border border-[var(--border)] shadow-[var(--shadow-sm)]',
            'text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors'
          )}
        >
          {collapsed ? <ChevronRight size={12} /> : <ChevronLeft size={12} />}
        </button>
      )}
    </div>
  )

  if (mobile) {
    return (
      <div className="relative h-full">
        {sidebarContent}
        <button
          onClick={onClose}
          className="absolute top-4 right-3 p-1.5 rounded-lg text-[var(--text-tertiary)] hover:bg-[var(--surface-2)]"
        >
          ✕
        </button>
      </div>
    )
  }

  return (
    <motion.div
      className="relative h-full"
      animate={{ width: collapsed ? 64 : 240 }}
      transition={{ duration: 0.2, ease: [0.25, 0.46, 0.45, 0.94] }}
    >
      {sidebarContent}
    </motion.div>
  )
}
