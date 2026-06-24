import React, { useEffect, useState } from 'react'
import { User, Lock, Save, Eye, EyeOff } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'

export default function SettingsPage() {
  const { user, refreshUser } = useAuth()
  const { toast }             = useToast()

  const [profile, setProfile]         = useState({ name: '', email: '' })
  const [profileSaving, setPSaving]   = useState(false)

  const [pw, setPw]                   = useState({ current_password: '', password: '', password_confirmation: '' })
  const [pwSaving, setPwSaving]       = useState(false)
  const [showCurrent, setShowCurrent] = useState(false)
  const [showNew, setShowNew]         = useState(false)

  useEffect(() => {
    api.get('/profile').then(r => {
      const u = r.data.user
      setProfile({ name: u.name, email: u.email })
    })
  }, [])

  const saveProfile = async (e) => {
    e.preventDefault()
    setPSaving(true)
    try {
      const res = await api.put('/profile', profile)
      toast.success('Profile updated successfully.')
      await refreshUser()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Failed to update profile.')
    } finally {
      setPSaving(false)
    }
  }

  const savePassword = async (e) => {
    e.preventDefault()
    if (pw.password !== pw.password_confirmation) {
      toast.error('Passwords do not match.')
      return
    }
    setPwSaving(true)
    try {
      await api.put('/profile/password', pw)
      toast.success('Password changed successfully.')
      setPw({ current_password: '', password: '', password_confirmation: '' })
    } catch (err) {
      const errors = err?.response?.data?.errors
      const first  = errors ? Object.values(errors)[0]?.[0] : null
      toast.error(first ?? err?.response?.data?.message ?? 'Failed to change password.')
    } finally {
      setPwSaving(false)
    }
  }

  return (
    <div className="max-w-2xl space-y-6">
      <PageHeader title="Settings" subtitle="Manage your account profile and security" />

      {/* Profile section */}
      <Card>
        <CardHeader
          title="Profile"
          subtitle="Update your name and email address"
          icon={<User size={16} className="text-brand-600" />}
        />
        <form onSubmit={saveProfile} className="space-y-4 mt-4">
          <Input
            label="Full name"
            required
            value={profile.name}
            onChange={e => setProfile(p => ({ ...p, name: e.target.value }))}
            autoComplete="name"
          />
          <Input
            label="Email address"
            type="email"
            required
            value={profile.email}
            onChange={e => setProfile(p => ({ ...p, email: e.target.value }))}
            autoComplete="email"
          />
          <div className="flex justify-end pt-2">
            <Button type="submit" loading={profileSaving} leftIcon={<Save size={14} />}>
              Save Profile
            </Button>
          </div>
        </form>
      </Card>

      {/* Password section */}
      <Card>
        <CardHeader
          title="Change Password"
          subtitle="Use a strong password of at least 12 characters"
          icon={<Lock size={16} className="text-brand-600" />}
        />
        <form onSubmit={savePassword} className="space-y-4 mt-4">
          <div className="relative">
            <Input
              label="Current password"
              type={showCurrent ? 'text' : 'password'}
              required
              value={pw.current_password}
              onChange={e => setPw(p => ({ ...p, current_password: e.target.value }))}
              autoComplete="current-password"
            />
            <button
              type="button"
              onClick={() => setShowCurrent(v => !v)}
              className="absolute right-3 top-[34px] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
            >
              {showCurrent ? <EyeOff size={15} /> : <Eye size={15} />}
            </button>
          </div>

          <div className="relative">
            <Input
              label="New password"
              type={showNew ? 'text' : 'password'}
              required
              value={pw.password}
              onChange={e => setPw(p => ({ ...p, password: e.target.value }))}
              hint="Minimum 12 characters"
              autoComplete="new-password"
            />
            <button
              type="button"
              onClick={() => setShowNew(v => !v)}
              className="absolute right-3 top-[34px] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
            >
              {showNew ? <EyeOff size={15} /> : <Eye size={15} />}
            </button>
          </div>

          <Input
            label="Confirm new password"
            type="password"
            required
            value={pw.password_confirmation}
            onChange={e => setPw(p => ({ ...p, password_confirmation: e.target.value }))}
            autoComplete="new-password"
          />

          <div className="flex justify-end pt-2">
            <Button type="submit" loading={pwSaving} leftIcon={<Save size={14} />}>
              Change Password
            </Button>
          </div>
        </form>
      </Card>

      {/* Account info */}
      <Card>
        <CardHeader title="Account Information" subtitle="Your current account details" />
        <div className="mt-4 divide-y divide-[var(--border)] rounded-xl border border-[var(--border)]">
          {[
            { label: 'Role',     value: user?.role === 'admin' ? 'Administrator' : 'Staff' },
            { label: 'Status',   value: user?.is_active !== false ? 'Active' : 'Inactive' },
          ].map(({ label, value }) => (
            <div key={label} className="flex items-center justify-between px-4 py-3">
              <span className="text-sm text-[var(--text-tertiary)]">{label}</span>
              <span className="text-sm font-medium text-[var(--text-primary)] capitalize">{value}</span>
            </div>
          ))}
        </div>
      </Card>
    </div>
  )
}
