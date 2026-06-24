import React, { useState, useRef, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { motion } from 'framer-motion'
import { ShieldCheck, AlertCircle, RefreshCw } from 'lucide-react'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { Button } from '../components/ui/Button'
import { fadeUp } from '../lib/animations'

export default function OtpPage() {
  const { verifyOtp, resendOtp } = useAuth()
  const navigate  = useNavigate()
  const { toast } = useToast()

  const maskedEmail = sessionStorage.getItem('otp_masked_email') ?? 'your email'
  const [digits, setDigits]       = useState(Array(6).fill(''))
  const [loading, setLoading]     = useState(false)
  const [resending, setResending] = useState(false)
  const [error, setError]         = useState('')
  const inputs = useRef([])

  useEffect(() => { inputs.current[0]?.focus() }, [])

  const handleChange = (i, val) => {
    if (!/^\d?$/.test(val)) return
    const next = [...digits]
    next[i] = val
    setDigits(next)
    if (val && i < 5) inputs.current[i + 1]?.focus()

    // Auto-submit when all filled
    const full = next.join('')
    if (full.length === 6) submit(full)
  }

  const handleKeyDown = (i, e) => {
    if (e.key === 'Backspace' && !digits[i] && i > 0) {
      inputs.current[i - 1]?.focus()
    }
  }

  const handlePaste = (e) => {
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
    if (pasted.length === 6) {
      setDigits(pasted.split(''))
      inputs.current[5]?.focus()
      setTimeout(() => submit(pasted), 50)
    }
  }

  const submit = async (otp) => {
    setError('')
    setLoading(true)
    try {
      await verifyOtp(otp)
      toast.success('Signed in successfully')
      navigate('/', { replace: true })
    } catch (err) {
      setError(err?.response?.data?.message ?? 'Invalid or expired code.')
      setDigits(Array(6).fill(''))
      setTimeout(() => inputs.current[0]?.focus(), 50)
    } finally {
      setLoading(false)
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    submit(digits.join(''))
  }

  const handleResend = async () => {
    setResending(true)
    setError('')
    try {
      await resendOtp()
      toast.success('A new code has been sent.')
      setDigits(Array(6).fill(''))
      inputs.current[0]?.focus()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Too many requests.')
    } finally {
      setResending(false)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--bg)] p-6">
      <motion.div className="w-full max-w-sm" {...fadeUp}>
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center h-16 w-16 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 mb-4">
            <ShieldCheck size={32} strokeWidth={1.5} />
          </div>
          <h1 className="text-2xl font-bold text-[var(--text-primary)]">Two-factor authentication</h1>
          <p className="text-sm text-[var(--text-secondary)] mt-1.5">
            Enter the 6-digit code sent to{' '}
            <span className="font-medium text-[var(--text-primary)]">{maskedEmail}</span>
          </p>
        </div>

        <div className="rounded-2xl bg-[var(--surface)] border border-[var(--border)] p-6 shadow-[var(--shadow-md)]">
          <div className="rounded-lg bg-[var(--warning-bg)] border border-[var(--warning-border)] px-3 py-2.5 text-xs text-amber-700 dark:text-amber-400 mb-5">
            Code expires in <strong>5 minutes</strong> · Maximum <strong>3 attempts</strong>
          </div>

          {error && (
            <div className="mb-4 flex items-center gap-2 rounded-lg bg-[var(--danger-bg)] border border-[var(--danger-border)] px-3 py-2.5 text-sm text-red-700">
              <AlertCircle size={14} className="shrink-0" />
              {error}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div className="flex justify-center gap-2 mb-6" onPaste={handlePaste}>
              {digits.map((d, i) => (
                <input
                  key={i}
                  ref={el => inputs.current[i] = el}
                  type="text"
                  inputMode="numeric"
                  maxLength={1}
                  value={d}
                  onChange={e => handleChange(i, e.target.value)}
                  onKeyDown={e => handleKeyDown(i, e)}
                  className="w-11 h-13 text-center text-xl font-bold rounded-xl border-2 border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-primary)] transition-all duration-150 focus:border-brand-500 focus:bg-[var(--surface)] focus:outline-none focus:ring-0 caret-brand-600"
                  disabled={loading}
                />
              ))}
            </div>

            <Button
              type="submit"
              className="w-full"
              loading={loading}
              disabled={digits.join('').length < 6}
              size="lg"
            >
              Verify code
            </Button>
          </form>

          <div className="mt-4 text-center">
            <p className="text-sm text-[var(--text-secondary)]">
              Didn't receive a code?{' '}
              <button
                onClick={handleResend}
                disabled={resending}
                className="inline-flex items-center gap-1 font-medium text-brand-600 hover:text-brand-700 disabled:opacity-50 transition-colors"
              >
                {resending && <RefreshCw size={12} className="animate-spin" />}
                Resend code
              </button>
            </p>
          </div>
        </div>
      </motion.div>
    </div>
  )
}
