import React, { useState } from 'react'
import { Link, useNavigate, useLocation } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Mail, Lock, Zap, AlertCircle } from 'lucide-react'
import { useAuth } from '../context/AuthContext'
import { Input } from '../components/ui/Input'
import { Button } from '../components/ui/Button'
import { fadeUp } from '../lib/animations'

export default function LoginPage() {
  const { login }  = useAuth()
  const navigate   = useNavigate()
  const location   = useLocation()

  const [form, setForm]       = useState({ email: '', password: '', remember: false })
  const [loading, setLoading] = useState(false)
  const [error, setError]     = useState('')

  const msg = location.state?.message

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const data = await login(form.email, form.password)
      if (data.requires_otp) {
        sessionStorage.setItem('otp_masked_email', data.masked_email ?? '')
        sessionStorage.setItem('otp_token', data.otp_token ?? '')
        navigate('/otp')
      }
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Invalid email or password.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex bg-[var(--bg)]">
      {/* Left panel */}
      <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-900 via-brand-800 to-brand-700 flex-col justify-between p-12 relative overflow-hidden">
        {/* Background pattern */}
        <div className="absolute inset-0 opacity-10">
          {[...Array(20)].map((_, i) => (
            <div key={i}
              className="absolute rounded-full border border-white"
              style={{
                width: `${(i + 1) * 40}px`, height: `${(i + 1) * 40}px`,
                top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
              }}
            />
          ))}
        </div>
        <div className="relative z-10">
          <div className="flex items-center gap-3 mb-12">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
              <Zap size={20} className="text-white" />
            </div>
            <span className="text-white font-bold text-lg">FeRa Clinic SMS</span>
          </div>
          <h2 className="text-4xl font-bold text-white mb-4 leading-tight">
            Reach your patients<br />at the right time.
          </h2>
          <p className="text-brand-200 text-lg">
            Professional SMS campaigns with real-time delivery tracking.
          </p>
        </div>
        <div className="relative z-10 grid grid-cols-3 gap-4">
          {[
            { n: '99.8%', l: 'Uptime' },
            { n: '2.1s',  l: 'Avg. Delivery' },
            { n: '45+',   l: 'Countries' },
          ].map(({ n, l }) => (
            <div key={l} className="text-center">
              <p className="text-2xl font-bold text-white">{n}</p>
              <p className="text-brand-300 text-xs mt-0.5">{l}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Right panel */}
      <div className="flex-1 flex flex-col items-center justify-center p-6 lg:p-12">
        <motion.div className="w-full max-w-sm" {...fadeUp}>
          {/* Mobile logo */}
          <div className="lg:hidden flex items-center gap-2 mb-8">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600">
              <Zap size={16} className="text-white" />
            </div>
            <span className="font-bold text-[var(--text-primary)]">FeRa Clinic SMS</span>
          </div>

          <h1 className="text-2xl font-bold text-[var(--text-primary)] mb-1">Welcome back</h1>
          <p className="text-sm text-[var(--text-secondary)] mb-6">Sign in to your account to continue</p>

          {msg && (
            <div className="mb-4 rounded-lg bg-[var(--success-bg)] border border-[var(--success-border)] px-4 py-3 text-sm text-emerald-700">
              {msg}
            </div>
          )}

          {error && (
            <div className="mb-4 flex items-start gap-2.5 rounded-lg bg-[var(--danger-bg)] border border-[var(--danger-border)] px-4 py-3 text-sm text-red-700">
              <AlertCircle size={15} className="mt-0.5 shrink-0" />
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <Input
              label="Email address"
              type="email"
              placeholder="you@feraclinic.com"
              leftIcon={<Mail size={15} />}
              value={form.email}
              onChange={e => setForm(p => ({ ...p, email: e.target.value }))}
              required
              autoFocus
              autoComplete="email"
            />
            <Input
              label="Password"
              type="password"
              placeholder="••••••••••••"
              leftIcon={<Lock size={15} />}
              value={form.password}
              onChange={e => setForm(p => ({ ...p, password: e.target.value }))}
              required
              autoComplete="current-password"
            />

            <div className="flex items-center justify-between">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={form.remember}
                  onChange={e => setForm(p => ({ ...p, remember: e.target.checked }))}
                  className="rounded border-[var(--border)] text-brand-600 focus:ring-brand-500"
                />
                <span className="text-sm text-[var(--text-secondary)]">Remember me</span>
              </label>
              <Link to="/forgot-password" className="text-sm text-brand-600 hover:text-brand-700 font-medium">
                Forgot password?
              </Link>
            </div>

            <Button type="submit" loading={loading} className="w-full" size="lg">
              Sign in
            </Button>
          </form>
        </motion.div>
      </div>
    </div>
  )
}
