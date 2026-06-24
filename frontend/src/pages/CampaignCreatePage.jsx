import React, { useEffect, useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import api from '../services/api'
import ErrorAlert from '../components/ErrorAlert'

export default function CampaignCreatePage() {
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    message_body: '',
    segment_id: '',
    scheduled_at: '',
    send_now: false,
  })
  const [segments,  setSegments]  = useState([])
  const [templates, setTemplates] = useState([])
  const [loading,   setLoading]   = useState(false)
  const [error,     setError]     = useState(null)
  const [charCount, setCharCount] = useState(0)

  useEffect(() => {
    api.get('/segments').then(r => setSegments(r.data.data ?? []))
    api.get('/templates').then(r => setTemplates(r.data.data?.data ?? []))
  }, [])

  const handleMessageChange = (val) => {
    setForm(p => ({ ...p, message_body: val }))
    setCharCount(val.length)
  }

  const applyTemplate = (t) => {
    setForm(p => ({ ...p, message_body: t.content }))
    setCharCount(t.content.length)
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const payload = { ...form, segment_id: form.segment_id || null, scheduled_at: form.scheduled_at || null }
      const res = await api.post('/campaigns', payload)
      navigate(`/campaigns/${res.data.data?.id ?? res.data.id}`)
    } catch (err) {
      setError(err)
    } finally {
      setLoading(false)
    }
  }

  const segments_count = Math.ceil(charCount / 160) || 0

  return (
    <div className="max-w-2xl space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900">New Campaign</h1>
        <Link to="/campaigns" className="btn-secondary">Cancel</Link>
      </div>

      <ErrorAlert error={error} />

      <form onSubmit={handleSubmit} className="card space-y-5">
        <div>
          <label className="label">Campaign Name</label>
          <input
            className="input"
            value={form.name}
            onChange={e => setForm(p => ({ ...p, name: e.target.value }))}
            placeholder="e.g. June Appointment Reminders"
            required
          />
        </div>

        <div>
          <div className="flex items-center justify-between mb-1">
            <label className="label mb-0">Message</label>
            <div className="flex gap-2 items-center text-xs text-gray-500">
              {templates.length > 0 && (
                <select
                  className="text-xs border border-gray-300 rounded px-2 py-1"
                  onChange={e => {
                    const t = templates.find(t => String(t.id) === e.target.value)
                    if (t) applyTemplate(t)
                  }}
                  defaultValue=""
                >
                  <option value="">Use template…</option>
                  {templates.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                </select>
              )}
              <span>{charCount}/1600 chars · {segments_count} segment{segments_count !== 1 ? 's' : ''}</span>
            </div>
          </div>
          <textarea
            className="input min-h-[120px] resize-y"
            value={form.message_body}
            onChange={e => handleMessageChange(e.target.value)}
            placeholder="Your message here… Use {first_name}, {last_name}, {tracking_url} as variables."
            required
            maxLength={1600}
          />
          <p className="text-xs text-gray-400 mt-1">Opt-out text is appended automatically.</p>
        </div>

        <div>
          <label className="label">Audience Segment (optional)</label>
          <select className="input" value={form.segment_id} onChange={e => setForm(p => ({ ...p, segment_id: e.target.value }))}>
            <option value="">All opted-in contacts</option>
            {segments.map(s => (
              <option key={s.id} value={s.id}>{s.name} ({s.eligible_count ?? '?'} contacts)</option>
            ))}
          </select>
        </div>

        <div>
          <label className="label">Schedule (optional)</label>
          <input
            type="datetime-local"
            className="input"
            value={form.scheduled_at}
            onChange={e => setForm(p => ({ ...p, scheduled_at: e.target.value, send_now: false }))}
          />
        </div>

        <div className="flex gap-4 pt-2">
          <button type="submit" className="btn-primary" disabled={loading} onClick={() => setForm(p => ({ ...p, send_now: false }))}>
            {loading ? 'Saving…' : 'Save as Draft'}
          </button>
          <button
            type="submit"
            className="btn-primary bg-green-600 hover:bg-green-700 focus:ring-green-500"
            disabled={loading}
            onClick={() => setForm(p => ({ ...p, send_now: true }))}
          >
            {loading ? 'Sending…' : 'Send Now'}
          </button>
        </div>
      </form>
    </div>
  )
}
