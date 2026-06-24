import React, { useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { KeyRound, Mail, ArrowLeft } from 'lucide-react'
import api from '../services/api'
import { Input } from '../components/ui/Input'
import { Button } from '../components/ui/Button'
import { fadeUp } from '../lib/animations'

export default function ForgotPasswordPage() {
  const [email, setEmail]     = useState('')
  const [loading, setLoading] = useState(false)
  const [sent, setSent]       = useState(false)
  const [error, setError]     = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await api.post('/auth/forgot-password', { email })
      setSent(true)
    } catch {
      setError('Unable to process your request. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--bg)] p-6">
      <motion.div className="w-full max-w-sm" {...fadeUp}>
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 mb-4">
            <KeyRound size={32} strokeWidth={1.5} />
          </div>
          <h1 className="text-2xl font-bold text-[var(--text-primary)]">
            {sent ? 'Check your email' : 'Reset password'}
          </h1>
          <p className="text-sm text-[var(--text-secondary)] mt-1.5">
            {sent
              ? `If an account exists for ${email}, a reset link has been sent.`
              : "We'll send a reset link to your inbox"
            }
          </p>
        </div>

        <div className="rounded-2xl bg-[var(--surface)] border border-[var(--border)] p-6 shadow-[var(--shadow-md)]">
          {sent ? (
            <div className="text-center py-2">
              <div className="text-5xl mb-4">📬</div>
              <p className="text-sm text-[var(--text-secondary)] mb-4">Didn't get it? Check your spam folder or try again.</p>
              <Button variant="secondary" className="w-full" onClick={() => setSent(false)}>
                Try again
              </Button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              {error && (
                <div className="rounded-lg bg-[var(--danger-bg)] border border-[var(--danger-border)] px-3 py-2.5 text-sm text-red-700">
                  {error}
                </div>
              )}
              <Input
                label="Email address"
                type="email"
                placeholder="you@feraclinic.com"
                leftIcon={<Mail size={15} />}
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                autoFocus
              />
              <Button type="submit" loading={loading} className="w-full" size="lg">
                Send reset link
              </Button>
            </form>
          )}
        </div>

        <div className="mt-5 text-center">
          <Link
            to="/login"
            className="inline-flex items-center gap-1.5 text-sm text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors"
          >
            <ArrowLeft size={14} />
            Back to sign in
          </Link>
        </div>
      </motion.div>
    </div>
  )
}
