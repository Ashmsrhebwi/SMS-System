import React, { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { motion, AnimatePresence } from 'framer-motion'
import { Check, ChevronRight, Users, FileText, Eye, Send } from 'lucide-react'
import api from '../../services/api'
import { useToast } from '../../context/ToastContext'
import { PageHeader } from '../../components/layout/PageHeader'
import { Card } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input, Textarea, Select } from '../../components/ui/Input'
import { Badge, StatusBadge } from '../../components/ui/Badge'
import { cn } from '../../lib/cn'

// ─── Step definitions ─────────────────────────────────────────────────────────
const STEPS = [
  { id: 1, label: 'Audience',  icon: Users },
  { id: 2, label: 'Template',  icon: FileText },
  { id: 3, label: 'Preview',   icon: Eye },
  { id: 4, label: 'Send',      icon: Send },
]

// ─── Step 1: Audience ─────────────────────────────────────────────────────────
function Step1({ form, setForm, segments }) {
  const selectedSeg = segments.find(s => String(s.id) === String(form.segment_id))

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-[var(--text-primary)] mb-1">Name your campaign</h2>
        <p className="text-sm text-[var(--text-secondary)]">Give this campaign a clear, descriptive name.</p>
      </div>
      <Input
        label="Campaign name"
        required
        placeholder="e.g. June Appointment Reminders"
        value={form.name}
        onChange={e => setForm(p => ({ ...p, name: e.target.value }))}
        size="lg"
      />

      <div>
        <h2 className="text-lg font-semibold text-[var(--text-primary)] mb-1 mt-6">Select audience</h2>
        <p className="text-sm text-[var(--text-secondary)]">Choose who will receive this campaign.</p>
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        {/* All contacts option */}
        <motion.button
          type="button"
          whileTap={{ scale: 0.99 }}
          onClick={() => setForm(p => ({ ...p, segment_id: '' }))}
          className={cn(
            'text-left rounded-xl border-2 p-4 transition-all duration-150',
            !form.segment_id
              ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50'
              : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--text-tertiary)]'
          )}
        >
          <div className="flex items-start justify-between mb-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-100 dark:bg-brand-900 text-brand-600">
              <Users size={16} />
            </div>
            {!form.segment_id && (
              <span className="flex h-5 w-5 items-center justify-center rounded-full bg-brand-600">
                <Check size={12} className="text-white" />
              </span>
            )}
          </div>
          <p className="font-semibold text-sm text-[var(--text-primary)]">All opted-in contacts</p>
          <p className="text-xs text-[var(--text-secondary)] mt-0.5">Send to your entire contact list</p>
        </motion.button>

        {/* Segment options */}
        {segments.map(seg => (
          <motion.button
            key={seg.id}
            type="button"
            whileTap={{ scale: 0.99 }}
            onClick={() => setForm(p => ({ ...p, segment_id: String(seg.id) }))}
            className={cn(
              'text-left rounded-xl border-2 p-4 transition-all duration-150',
              String(form.segment_id) === String(seg.id)
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50'
                : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--text-tertiary)]'
            )}
          >
            <div className="flex items-start justify-between mb-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900 text-purple-600">
                <Users size={16} />
              </div>
              {String(form.segment_id) === String(seg.id) && (
                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-brand-600">
                  <Check size={12} className="text-white" />
                </span>
              )}
            </div>
            <p className="font-semibold text-sm text-[var(--text-primary)]">{seg.name}</p>
            <p className="text-xs text-[var(--text-secondary)] mt-0.5">
              {seg.eligible_count?.toLocaleString() ?? '?'} eligible contacts
            </p>
          </motion.button>
        ))}
      </div>

      {selectedSeg && (
        <div className="rounded-lg bg-[var(--info-bg)] border border-[var(--info-border)] px-4 py-3 text-sm text-blue-700 dark:text-blue-400">
          This campaign will reach approximately{' '}
          <strong>{selectedSeg.eligible_count?.toLocaleString()}</strong> contacts.
        </div>
      )}
    </div>
  )
}

// ─── Step 2: Template ─────────────────────────────────────────────────────────
function Step2({ form, setForm, templates }) {
  const [freeText, setFreeText] = useState(!form.template_id)

  const applyTemplate = (t) => {
    setForm(p => ({ ...p, message_body: t.content, template_id: t.id }))
    setFreeText(false)
  }

  const VARS = ['{first_name}', '{last_name}', '{clinic_name}', '{tracking_url}']
  const insertVar = (v) => setForm(p => ({ ...p, message_body: p.message_body + v }))

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-[var(--text-primary)] mb-1">Compose message</h2>
        <p className="text-sm text-[var(--text-secondary)]">Select a template or write your own message.</p>
      </div>

      {/* Tab toggle */}
      <div className="flex rounded-lg border border-[var(--border)] p-1 bg-[var(--surface-2)] w-fit">
        {[
          { id: 'template', label: 'Use template' },
          { id: 'custom',   label: 'Write custom' },
        ].map(tab => (
          <button
            key={tab.id}
            type="button"
            onClick={() => setFreeText(tab.id === 'custom')}
            className={cn(
              'px-4 py-1.5 rounded-md text-sm font-medium transition-all',
              (freeText ? tab.id === 'custom' : tab.id === 'template')
                ? 'bg-[var(--surface)] text-[var(--text-primary)] shadow-[var(--shadow-xs)]'
                : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'
            )}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {freeText ? (
        <div className="space-y-3">
          <div className="flex flex-wrap gap-1.5">
            {VARS.map(v => (
              <button
                key={v}
                type="button"
                onClick={() => insertVar(v)}
                className="rounded-full bg-[var(--surface-2)] border border-[var(--border)] px-2.5 py-1 text-xs font-mono text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-950 transition-colors"
              >
                {v}
              </button>
            ))}
          </div>
          <Textarea
            label="Message body"
            required
            placeholder="Type your message… Use variables like {first_name} for personalization."
            value={form.message_body}
            onChange={e => setForm(p => ({ ...p, message_body: e.target.value, template_id: null }))}
            className="min-h-[160px] font-mono text-sm"
            maxLength={1600}
          />
          <div className="flex items-center justify-between text-xs text-[var(--text-tertiary)]">
            <span>Opt-out text is appended automatically.</span>
            <span>{form.message_body.length}/1600 · {Math.ceil((form.message_body.length || 1) / 160)} SMS segment(s)</span>
          </div>
        </div>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2">
          {templates.map(t => (
            <motion.button
              key={t.id}
              type="button"
              whileTap={{ scale: 0.99 }}
              onClick={() => applyTemplate(t)}
              className={cn(
                'text-left rounded-xl border-2 p-4 transition-all duration-150',
                form.template_id === t.id
                  ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50'
                  : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--text-tertiary)]'
              )}
            >
              <div className="flex items-start justify-between mb-2">
                <p className="font-semibold text-sm text-[var(--text-primary)]">{t.name}</p>
                {form.template_id === t.id && (
                  <span className="flex h-5 w-5 items-center justify-center rounded-full bg-brand-600 shrink-0">
                    <Check size={12} className="text-white" />
                  </span>
                )}
              </div>
              <p className="text-xs text-[var(--text-secondary)] line-clamp-3 font-mono leading-relaxed">
                {t.content}
              </p>
              <p className="text-xs text-[var(--text-tertiary)] mt-2">
                {t.content.length} chars · {Math.ceil(t.content.length / 160)} segment(s)
              </p>
            </motion.button>
          ))}
          {templates.length === 0 && (
            <div className="col-span-2 py-8 text-center text-sm text-[var(--text-tertiary)]">
              No templates yet. Switch to "Write custom" to compose.
            </div>
          )}
        </div>
      )}
    </div>
  )
}

// ─── Step 3: Preview ──────────────────────────────────────────────────────────
function Step3({ form, segments }) {
  const seg         = segments.find(s => String(s.id) === String(form.segment_id))
  const charCount   = form.message_body.length
  const smsSegments = Math.ceil((charCount || 1) / 160)

  const preview = form.message_body
    .replace(/{first_name}/g, 'Sarah')
    .replace(/{last_name}/g, 'Johnson')
    .replace(/{clinic_name}/g, 'FeRa Clinic')
    .replace(/{tracking_url}/g, 'https://fera.cl/t/abc123')

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-[var(--text-primary)] mb-1">Preview message</h2>
        <p className="text-sm text-[var(--text-secondary)]">
          Review how your message will look. Variables are shown with sample data.
        </p>
      </div>

      <div className="flex gap-8 items-start">
        {/* Phone mockup */}
        <div className="shrink-0">
          <div className="w-64 rounded-3xl bg-gray-900 dark:bg-gray-800 p-3 shadow-2xl">
            <div className="rounded-2xl bg-gray-100 dark:bg-gray-700 overflow-hidden">
              {/* Status bar */}
              <div className="flex items-center justify-between px-4 py-2 bg-white dark:bg-gray-800">
                <span className="text-xs font-semibold">9:41</span>
                <div className="flex gap-1">
                  <div className="w-4 h-2 bg-gray-900 dark:bg-gray-200 rounded-sm" />
                  <div className="w-1 h-2 bg-gray-400 rounded-sm" />
                </div>
              </div>
              {/* Messages header */}
              <div className="bg-gray-50 dark:bg-gray-700 px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                <p className="text-xs font-semibold text-gray-600 dark:text-gray-300">FeRa Clinic</p>
              </div>
              {/* Message bubble */}
              <div className="p-3 space-y-2 min-h-[180px] bg-white dark:bg-gray-800">
                <div className="max-w-[90%] rounded-2xl rounded-tl-sm bg-gray-200 dark:bg-gray-600 px-3 py-2">
                  <p className="text-xs text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap">
                    {preview}
                  </p>
                  <p className="text-[10px] text-gray-400 text-right mt-1">Delivered</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Details */}
        <div className="flex-1 space-y-4">
          <div className="rounded-xl bg-[var(--surface-2)] border border-[var(--border)] divide-y divide-[var(--border)]">
            {[
              { label: 'Campaign',    value: form.name },
              { label: 'Audience',    value: seg ? `${seg.name} (${seg.eligible_count?.toLocaleString()} contacts)` : 'All opted-in contacts' },
              { label: 'Characters',  value: `${charCount} / 1,600` },
              { label: 'SMS segments', value: smsSegments },
            ].map(({ label, value }) => (
              <div key={label} className="flex items-center justify-between px-4 py-3">
                <span className="text-xs text-[var(--text-tertiary)]">{label}</span>
                <span className="text-sm font-medium text-[var(--text-primary)]">{value}</span>
              </div>
            ))}
          </div>

          <div className="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 px-4 py-3 text-xs text-amber-700 dark:text-amber-400">
            <strong>Note:</strong> An opt-out message will be automatically appended to your SMS.
          </div>
        </div>
      </div>
    </div>
  )
}

// ─── Step 4: Send ─────────────────────────────────────────────────────────────
function Step4({ form, setForm }) {
  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-[var(--text-primary)] mb-1">Schedule & send</h2>
        <p className="text-sm text-[var(--text-secondary)]">Choose when to deliver your campaign.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        {[
          {
            id: 'now',
            label: 'Send immediately',
            desc: 'Dispatch as soon as you submit.',
            icon: '⚡',
          },
          {
            id: 'schedule',
            label: 'Schedule for later',
            desc: 'Pick a future date and time.',
            icon: '🗓️',
          },
          {
            id: 'draft',
            label: 'Save as draft',
            desc: 'Come back and send when ready.',
            icon: '💾',
          },
        ].map(opt => (
          <motion.button
            key={opt.id}
            type="button"
            whileTap={{ scale: 0.98 }}
            onClick={() => setForm(p => ({ ...p, send_mode: opt.id }))}
            className={cn(
              'text-left rounded-xl border-2 p-4 transition-all duration-150',
              form.send_mode === opt.id
                ? 'border-brand-500 bg-brand-50 dark:bg-brand-950/50'
                : 'border-[var(--border)] bg-[var(--surface)] hover:border-[var(--text-tertiary)]'
            )}
          >
            <div className="flex items-start justify-between mb-2">
              <span className="text-2xl">{opt.icon}</span>
              {form.send_mode === opt.id && (
                <span className="flex h-5 w-5 items-center justify-center rounded-full bg-brand-600">
                  <Check size={12} className="text-white" />
                </span>
              )}
            </div>
            <p className="font-semibold text-sm text-[var(--text-primary)]">{opt.label}</p>
            <p className="text-xs text-[var(--text-secondary)] mt-0.5">{opt.desc}</p>
          </motion.button>
        ))}
      </div>

      {form.send_mode === 'schedule' && (
        <motion.div
          initial={{ opacity: 0, y: -8 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.15 }}
        >
          <Input
            label="Scheduled date & time"
            type="datetime-local"
            value={form.scheduled_at}
            onChange={e => setForm(p => ({ ...p, scheduled_at: e.target.value }))}
            required
          />
        </motion.div>
      )}
    </div>
  )
}

// ─── Wizard orchestrator ──────────────────────────────────────────────────────
export default function CampaignWizard() {
  const navigate = useNavigate()
  const { toast } = useToast()

  const [step, setStep]     = useState(1)
  const [direction, setDir] = useState(1)
  const [segments, setSegments]   = useState([])
  const [templates, setTemplates] = useState([])
  const [loading, setLoading]     = useState(false)

  const [form, setForm] = useState({
    name: '',
    segment_id: '',
    message_body: '',
    template_id: null,
    scheduled_at: '',
    send_mode: 'now',
  })

  useEffect(() => {
    api.get('/segments').then(r => setSegments(r.data.data ?? []))
    api.get('/templates').then(r => setTemplates(r.data.data?.data ?? r.data.data ?? []))
  }, [])

  const goNext = () => { setDir(1); setStep(s => s + 1) }
  const goBack = () => { setDir(-1); setStep(s => s - 1) }

  const canNext = () => {
    if (step === 1) return form.name.trim().length > 0
    if (step === 2) return form.message_body.trim().length > 0
    if (step === 3) return true
    return false
  }

  const handleSubmit = async () => {
    setLoading(true)
    try {
      const payload = {
        name:         form.name,
        message_body: form.message_body,
        segment_id:   form.segment_id || null,
        scheduled_at: form.send_mode === 'schedule' ? form.scheduled_at : null,
        send_now:     form.send_mode === 'now',
      }
      const res = await api.post('/campaigns', payload)
      const id = res.data.data?.id ?? res.data.id
      toast.success('Campaign created successfully!')
      navigate(`/campaigns/${id}`)
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Failed to create campaign.')
    } finally {
      setLoading(false)
    }
  }

  const variants = {
    initial: { opacity: 0, x: direction > 0 ? 28 : -28 },
    animate: { opacity: 1, x: 0 },
    exit:    { opacity: 0, x: direction > 0 ? -20 : 20 },
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <PageHeader
        title="New Campaign"
        breadcrumbs={[
          { label: 'Campaigns', href: '/campaigns' },
          { label: 'New Campaign' },
        ]}
      />

      {/* Progress stepper */}
      <div className="flex items-center gap-0">
        {STEPS.map((s, i) => {
          const Icon       = s.icon
          const done       = step > s.id
          const active     = step === s.id
          const last       = i === STEPS.length - 1

          return (
            <React.Fragment key={s.id}>
              <div className="flex flex-col items-center gap-1.5">
                <div className={cn(
                  'flex h-9 w-9 items-center justify-center rounded-full border-2 transition-all duration-200',
                  done   ? 'border-brand-600 bg-brand-600 text-white'                        :
                  active ? 'border-brand-600 bg-white dark:bg-brand-950 text-brand-600'      :
                           'border-[var(--border)] bg-[var(--surface)] text-[var(--text-tertiary)]'
                )}>
                  {done ? <Check size={14} /> : <Icon size={14} />}
                </div>
                <span className={cn(
                  'text-xs font-medium hidden sm:block',
                  active ? 'text-brand-600' : done ? 'text-[var(--text-secondary)]' : 'text-[var(--text-tertiary)]'
                )}>{s.label}</span>
              </div>
              {!last && (
                <div className="flex-1 h-0.5 mx-1 mb-5 sm:mb-0 sm:mt-[-18px]">
                  <motion.div
                    className="h-full bg-brand-600 rounded-full"
                    initial={false}
                    animate={{ scaleX: done ? 1 : 0 }}
                    style={{ originX: 0 }}
                    transition={{ duration: 0.3 }}
                  />
                  <div className="h-full bg-[var(--border)] rounded-full -mt-0.5" />
                </div>
              )}
            </React.Fragment>
          )
        })}
      </div>

      {/* Step content */}
      <Card>
        <AnimatePresence mode="wait" initial={false}>
          <motion.div
            key={step}
            variants={variants}
            initial="initial"
            animate="animate"
            exit="exit"
            transition={{ duration: 0.2, ease: [0.25, 0.46, 0.45, 0.94] }}
          >
            {step === 1 && <Step1 form={form} setForm={setForm} segments={segments} />}
            {step === 2 && <Step2 form={form} setForm={setForm} templates={templates} />}
            {step === 3 && <Step3 form={form} segments={segments} />}
            {step === 4 && <Step4 form={form} setForm={setForm} />}
          </motion.div>
        </AnimatePresence>
      </Card>

      {/* Footer navigation */}
      <div className="flex items-center justify-between">
        <Button
          variant="secondary"
          onClick={step === 1 ? () => navigate('/campaigns') : goBack}
        >
          {step === 1 ? 'Cancel' : '← Back'}
        </Button>

        <div className="flex items-center gap-3">
          <span className="text-xs text-[var(--text-tertiary)]">
            Step {step} of {STEPS.length}
          </span>
          {step < STEPS.length ? (
            <Button
              onClick={goNext}
              disabled={!canNext()}
              rightIcon={<ChevronRight size={14} />}
            >
              Continue
            </Button>
          ) : (
            <Button
              onClick={handleSubmit}
              loading={loading}
              variant={form.send_mode === 'draft' ? 'secondary' : 'primary'}
              leftIcon={form.send_mode === 'now' ? <Send size={14} /> : undefined}
            >
              {form.send_mode === 'now'      ? 'Send Campaign'    :
               form.send_mode === 'schedule' ? 'Schedule Campaign' :
               'Save Draft'}
            </Button>
          )}
        </div>
      </div>
    </div>
  )
}
