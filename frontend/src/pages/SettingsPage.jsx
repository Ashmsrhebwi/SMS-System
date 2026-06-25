import React, { useEffect, useState } from 'react'
import {
  User, Lock, Save, Eye, EyeOff, Settings2, Shield,
  Bell, Zap, Phone, BarChart2, Info,
} from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { cn } from '../lib/cn'

// ─── Tabs ────────────────────────────────────────────────────────────────────

const TABS = [
  { id: 'profile',   label: 'Profile',   icon: User    },
  { id: 'password',  label: 'Password',  icon: Lock    },
]

const ADMIN_TABS = [
  { id: 'system',    label: 'System',    icon: Settings2 },
  { id: 'twilio',    label: 'Twilio',    icon: Phone   },
  { id: 'campaigns', label: 'Campaigns', icon: BarChart2 },
  { id: 'security',  label: 'Security',  icon: Shield  },
  { id: 'notifications', label: 'Notifications', icon: Bell },
]

// ─── Profile Tab ─────────────────────────────────────────────────────────────

function ProfileTab() {
  const { refreshUser }              = useAuth()
  const { toast }                    = useToast()
  const [profile, setProfile]        = useState({ name: '', email: '' })
  const [saving, setSaving]          = useState(false)

  useEffect(() => {
    api.get('/profile').then(r => {
      const u = r.data.user
      setProfile({ name: u.name, email: u.email })
    })
  }, [])

  const save = async (e) => {
    e.preventDefault()
    setSaving(true)
    try {
      await api.put('/profile', profile)
      toast.success('Profile updated.')
      await refreshUser()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Failed to update profile.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Card>
      <CardHeader title="Profile" subtitle="Update your display name and email" icon={<User size={16} className="text-brand-600" />} />
      <form onSubmit={save} className="space-y-4 mt-4">
        <Input label="Full name" required value={profile.name} onChange={e => setProfile(p => ({ ...p, name: e.target.value }))} />
        <Input label="Email address" type="email" required value={profile.email} onChange={e => setProfile(p => ({ ...p, email: e.target.value }))} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={saving} leftIcon={<Save size={14} />}>Save Profile</Button>
        </div>
      </form>
    </Card>
  )
}

// ─── Password Tab ─────────────────────────────────────────────────────────────

function PasswordTab() {
  const { toast }                    = useToast()
  const [pw, setPw]                  = useState({ current_password: '', password: '', password_confirmation: '' })
  const [saving, setSaving]          = useState(false)
  const [showCurrent, setShowC]      = useState(false)
  const [showNew, setShowN]          = useState(false)

  const save = async (e) => {
    e.preventDefault()
    if (pw.password !== pw.password_confirmation) { toast.error('Passwords do not match.'); return }
    setSaving(true)
    try {
      await api.put('/profile/password', pw)
      toast.success('Password changed.')
      setPw({ current_password: '', password: '', password_confirmation: '' })
    } catch (err) {
      const errors = err?.response?.data?.errors
      const first  = errors ? Object.values(errors)[0]?.[0] : null
      toast.error(first ?? err?.response?.data?.message ?? 'Failed to change password.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Card>
      <CardHeader title="Change Password" subtitle="Minimum 12 characters with mixed case, numbers & symbols" icon={<Lock size={16} className="text-brand-600" />} />
      <form onSubmit={save} className="space-y-4 mt-4">
        <div className="relative">
          <Input label="Current password" type={showCurrent ? 'text' : 'password'} required value={pw.current_password}
            onChange={e => setPw(p => ({ ...p, current_password: e.target.value }))} />
          <button type="button" onClick={() => setShowC(v => !v)}
            className="absolute right-3 top-[34px] text-[var(--text-tertiary)] hover:text-[var(--text-primary)]">
            {showCurrent ? <EyeOff size={15} /> : <Eye size={15} />}
          </button>
        </div>
        <div className="relative">
          <Input label="New password" type={showNew ? 'text' : 'password'} required value={pw.password}
            onChange={e => setPw(p => ({ ...p, password: e.target.value }))} hint="Min 12 chars, uppercase, number, symbol" />
          <button type="button" onClick={() => setShowN(v => !v)}
            className="absolute right-3 top-[34px] text-[var(--text-tertiary)] hover:text-[var(--text-primary)]">
            {showNew ? <EyeOff size={15} /> : <Eye size={15} />}
          </button>
        </div>
        <Input label="Confirm new password" type="password" required value={pw.password_confirmation}
          onChange={e => setPw(p => ({ ...p, password_confirmation: e.target.value }))} />
        <div className="flex justify-end pt-2">
          <Button type="submit" loading={saving} leftIcon={<Save size={14} />}>Change Password</Button>
        </div>
      </form>
    </Card>
  )
}

// ─── System Settings Tab ──────────────────────────────────────────────────────

function SettingsGroup({ group, settings, onChange }) {
  if (!settings || settings.length === 0) return null

  const typeInput = (s) => {
    if (s.type === 'boolean') {
      return (
        <label className="flex items-center gap-2 cursor-pointer">
          <input
            type="checkbox"
            checked={s.value === 'true' || s.value === true}
            onChange={e => onChange(s.key, e.target.checked ? 'true' : 'false')}
            className="w-4 h-4 rounded border-[var(--border)] text-brand-600 focus:ring-brand-500"
          />
          <span className="text-sm text-[var(--text-secondary)]">{s.value === 'true' ? 'Enabled' : 'Disabled'}</span>
        </label>
      )
    }
    return (
      <input
        type={s.type === 'integer' || s.type === 'float' ? 'number' : 'text'}
        value={s.value ?? ''}
        onChange={e => onChange(s.key, e.target.value)}
        className="h-9 w-full rounded-lg border border-[var(--border)] bg-[var(--surface)] px-3 text-sm text-[var(--text-primary)] focus:outline-none focus:ring-2 focus:ring-brand-500"
      />
    )
  }

  return (
    <div className="space-y-4">
      {settings.map(s => (
        <div key={s.key} className="grid grid-cols-[1fr,auto] gap-4 items-start">
          <div>
            <p className="text-sm font-medium text-[var(--text-primary)]">{s.label ?? s.key}</p>
            {s.description && <p className="text-xs text-[var(--text-tertiary)] mt-0.5">{s.description}</p>}
          </div>
          <div className="w-60">{typeInput(s)}</div>
        </div>
      ))}
    </div>
  )
}

function AdminSettingsTab({ group, icon: Icon, title, subtitle }) {
  const { toast }            = useToast()
  const [settings, setSettings] = useState([])
  const [local, setLocal]    = useState({})
  const [saving, setSaving]  = useState(false)
  const [loading, setLoad]   = useState(true)

  useEffect(() => {
    setLoad(true)
    api.get('/system/settings')
      .then(r => {
        const grp = r.data.settings[group] ?? {}
        const list = Object.values(grp)
        setSettings(list)
        const init = {}
        list.forEach(s => { init[s.key] = s.value })
        setLocal(init)
      })
      .finally(() => setLoad(false))
  }, [group])

  const handleChange = (key, value) => {
    setLocal(p => ({ ...p, [key]: value }))
  }

  const save = async () => {
    setSaving(true)
    try {
      await api.post('/system/settings/bulk', {
        settings: Object.entries(local).map(([key, value]) => ({ key, value })),
      })
      toast.success('Settings saved.')
    } catch {
      toast.error('Failed to save settings.')
    } finally {
      setSaving(false)
    }
  }

  const displaySettings = settings.map(s => ({ ...s, value: local[s.key] ?? s.value }))

  return (
    <Card>
      <CardHeader title={title} subtitle={subtitle} icon={<Icon size={16} className="text-brand-600" />} />
      <div className="mt-4">
        {loading ? (
          <div className="space-y-3">
            {[1,2,3].map(i => <div key={i} className="h-12 rounded-lg bg-[var(--surface-2)] animate-pulse" />)}
          </div>
        ) : (
          <SettingsGroup group={group} settings={displaySettings} onChange={handleChange} />
        )}
        <div className="flex justify-end pt-4 mt-4 border-t border-[var(--border)]">
          <Button onClick={save} loading={saving} leftIcon={<Save size={14} />}>Save {title}</Button>
        </div>
      </div>
    </Card>
  )
}

// ─── Main Page ────────────────────────────────────────────────────────────────

export default function SettingsPage() {
  const { user }      = useAuth()
  const isAdmin       = user?.role === 'admin'
  const tabs          = isAdmin ? [...TABS, ...ADMIN_TABS] : TABS
  const [tab, setTab] = useState('profile')

  return (
    <div className="max-w-3xl space-y-5">
      <PageHeader title="Settings" subtitle="Manage your account and platform configuration" />

      {/* Tab bar */}
      <div className="flex gap-1 border-b border-[var(--border)] overflow-x-auto">
        {tabs.map(t => {
          const Icon = t.icon
          return (
            <button
              key={t.id}
              onClick={() => setTab(t.id)}
              className={cn(
                'flex items-center gap-1.5 px-3 py-2 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px',
                tab === t.id
                  ? 'border-brand-600 text-brand-600'
                  : 'border-transparent text-[var(--text-tertiary)] hover:text-[var(--text-primary)]'
              )}
            >
              <Icon size={13} />
              {t.label}
            </button>
          )
        })}
      </div>

      {/* Tab content */}
      {tab === 'profile'  && <ProfileTab />}
      {tab === 'password' && <PasswordTab />}

      {isAdmin && tab === 'system' && (
        <AdminSettingsTab group="general" icon={Settings2}
          title="General Settings" subtitle="Platform name, timezone and display preferences" />
      )}
      {isAdmin && tab === 'twilio' && (
        <>
          <Card>
            <CardHeader title="Twilio Configuration" subtitle="Read-only values from environment configuration" icon={<Info size={16} className="text-blue-500" />} />
            <div className="mt-4 rounded-xl border border-[var(--border)] divide-y divide-[var(--border)]">
              {[
                ['Account SID',         'Configured via TWILIO_ACCOUNT_SID in .env'],
                ['Auth Token',          'Configured via TWILIO_AUTH_TOKEN in .env (hidden)'],
                ['Messaging Service',   'Configured via TWILIO_MESSAGING_SERVICE_SID in .env'],
              ].map(([label, value]) => (
                <div key={label} className="flex justify-between px-4 py-3">
                  <span className="text-sm text-[var(--text-tertiary)]">{label}</span>
                  <span className="text-sm text-[var(--text-secondary)] italic">{value}</span>
                </div>
              ))}
            </div>
          </Card>
          <AdminSettingsTab group="twilio" icon={Phone}
            title="Twilio Behaviour" subtitle="SMS sending rate, opt-out text and tracking settings" />
        </>
      )}
      {isAdmin && tab === 'campaigns' && (
        <AdminSettingsTab group="campaigns" icon={BarChart2}
          title="Campaign Defaults" subtitle="Batch size, cost per segment and currency settings" />
      )}
      {isAdmin && tab === 'security' && (
        <AdminSettingsTab group="security" icon={Shield}
          title="Security Settings" subtitle="Session timeout, login attempts and OTP configuration" />
      )}
      {isAdmin && tab === 'notifications' && (
        <AdminSettingsTab group="notifications" icon={Bell}
          title="Admin Notifications" subtitle="Configure automatic email alerts for system events" />
      )}

      {/* Account info strip */}
      {tab === 'profile' && (
        <Card>
          <CardHeader title="Account Information" subtitle="Your current account details" />
          <div className="mt-4 divide-y divide-[var(--border)] rounded-xl border border-[var(--border)]">
            {[
              { label: 'Role',   value: user?.role === 'admin' ? 'Administrator' : 'Staff' },
              { label: 'Status', value: user?.is_active !== false ? 'Active' : 'Inactive' },
            ].map(({ label, value }) => (
              <div key={label} className="flex items-center justify-between px-4 py-3">
                <span className="text-sm text-[var(--text-tertiary)]">{label}</span>
                <span className="text-sm font-medium text-[var(--text-primary)]">{value}</span>
              </div>
            ))}
          </div>
        </Card>
      )}
    </div>
  )
}
