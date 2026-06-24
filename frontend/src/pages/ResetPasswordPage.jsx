import React, { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Lock, CheckCircle2 } from 'lucide-react'
import api from '../services/api'
import { Input } from '../components/ui/Input'
import { Button } from '../components/ui/Button'
import { fadeUp } from '../lib/animations'

export default function ResetPasswordPage() {
  const [params]    = useSearchParams()
  const navigate    = useNavigate()
  const [form, setForm]     = useState({ password: '', password_confirmation: '' })
  const [loading, setLoading] = useState(false)
  const [error, setError]   = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await api.post('/auth/reset-password', {
        token: params.get('token'),
        email: params.get('email'),
        ...form,
      })
      navigate('/login', { state: { message: 'Password reset successfully. Please sign in.' } })
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Reset failed. The link may have expired.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--bg)] p-6">
      <motion.div className="w-full max-w-sm" {...fadeUp}>
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 mb-4">
            <Lock size={32} strokeWidth={1.5} />
          </div>
          <h1 className="text-2xl font-bold text-[var(--text-primary)]">Set new password</h1>
          <p className="text-sm text-[var(--text-secondary)] mt-1.5">Choose a strong password for your account</p>
        </div>

        <div className="rounded-2xl bg-[var(--surface)] border border-[var(--border)] p-6 shadow-[var(--shadow-md)]">
          <div className="mb-4 rounded-lg bg-[var(--info-bg)] border border-[var(--info-border)] px-3 py-2.5 text-xs text-blue-700 dark:text-blue-400 space-y-0.5">
            <p className="font-medium mb-1">Password requirements:</p>
            {['12+ characters', 'Upper and lower case', 'At least one number', 'At least one symbol'].map(r => (
              <p key={r} className="flex items-center gap-1.5">
                <CheckCircle2 size={10} /> {r}
              </p>
            ))}
          </div>

          {error && (
            <div className="mb-4 rounded-lg bg-[var(--danger-bg)] border border-[var(--danger-border)] px-3 py-2.5 text-sm text-red-700">
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="New password"
              type="password"
              leftIcon={<Lock size={15} />}
              value={form.password}
              onChange={e => setForm(p => ({ ...p, password: e.target.value }))}
              required
              minLength={12}
            />
            <Input
              label="Confirm new password"
              type="password"
              leftIcon={<Lock size={15} />}
              value={form.password_confirmation}
              onChange={e => setForm(p => ({ ...p, password_confirmation: e.target.value }))}
              required
            />
            <Button type="submit" loading={loading} className="w-full" size="lg">
              Reset password
            </Button>
          </form>
        </div>
      </motion.div>
    </div>
  )
}
