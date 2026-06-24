import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { Search, Plus, Trash2, Ban, ShieldAlert } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { Modal } from '../components/ui/Modal'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'

export default function BlacklistPage() {
  const { user }             = useAuth()
  const { toast }            = useToast()
  const [data, setData]      = useState(null)
  const [search, setSearch]  = useState('')
  const [page, setPage]      = useState(1)
  const [loading, setLoad]   = useState(true)
  const [modal, setModal]    = useState(false)
  const [saving, setSaving]  = useState(false)
  const [form, setForm]      = useState({ phone: '', reason: '' })

  const load = (p = 1) => {
    setLoad(true)
    api.get('/blacklist', { params: { page: p, search } })
      .then(r => setData(r.data))
      .finally(() => setLoad(false))
  }

  useEffect(() => { load(1); setPage(1) }, [search])
  useEffect(() => { if (page > 1) load(page) }, [page])

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Blacklist" />
        <Card>
          <EmptyState icon={ShieldAlert} title="Admin access required" description="Blacklist management is available to administrators only." />
        </Card>
      </div>
    )
  }

  const openAdd = () => {
    setForm({ phone: '', reason: '' })
    setModal(true)
  }

  const save = async () => {
    setSaving(true)
    try {
      await api.post('/blacklist', form)
      toast.success('Number added to blacklist.')
      setModal(false)
      load(1)
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Failed to add number.')
    } finally {
      setSaving(false)
    }
  }

  const remove = async (entry) => {
    if (!confirm(`Remove ${entry.phone} from the blacklist?`)) return
    try {
      await api.delete(`/blacklist/${entry.id}`)
      toast.success('Number removed from blacklist.')
      load(page)
    } catch {
      toast.error('Failed to remove number.')
    }
  }

  return (
    <div className="space-y-5">
      <PageHeader
        title="Global Blacklist"
        subtitle={`${data?.total ?? 0} blacklisted numbers`}
        action={
          <Button leftIcon={<Plus size={14} />} onClick={openAdd}>
            Add Number
          </Button>
        }
      />

      <Input
        placeholder="Search phone number…"
        leftIcon={<Search size={14} />}
        value={search}
        onChange={e => setSearch(e.target.value)}
        className="sm:w-72"
      />

      {loading ? (
        <SkeletonTable rows={6} />
      ) : !data?.data?.length ? (
        <Card>
          <EmptyState
            icon={Ban}
            title="Blacklist is empty"
            description="Add phone numbers to prevent them from receiving any SMS."
            action={openAdd}
            actionLabel="Add Number"
          />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Phone Number', 'Reason', 'Added', ''].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {data.data.map((entry, i) => (
                <motion.tr
                  key={entry.id}
                  className="hover:bg-[var(--surface-2)] transition-colors"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: i * 0.02 }}
                >
                  <td className="px-4 py-3.5 font-mono text-sm text-[var(--text-primary)]">
                    {entry.phone}
                  </td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)]">
                    {entry.reason ?? <span className="text-[var(--text-tertiary)]">—</span>}
                  </td>
                  <td className="px-4 py-3.5 text-xs text-[var(--text-tertiary)]">
                    {new Date(entry.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3.5 text-right">
                    <button
                      onClick={() => remove(entry)}
                      className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors"
                      title="Remove"
                    >
                      <Trash2 size={14} />
                    </button>
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={{ current_page: data.current_page, last_page: data.last_page, total: data.total }} onPageChange={setPage} />
        </Card>
      )}

      <Modal
        open={modal}
        onClose={() => setModal(false)}
        title="Add to Blacklist"
        size="sm"
        footer={
          <>
            <Button variant="secondary" onClick={() => setModal(false)}>Cancel</Button>
            <Button loading={saving} onClick={save}>Add Number</Button>
          </>
        }
      >
        <div className="space-y-4">
          <Input
            label="Phone number"
            placeholder="+1 555 000 0000"
            required
            value={form.phone}
            onChange={e => setForm(p => ({ ...p, phone: e.target.value }))}
          />
          <Input
            label="Reason (optional)"
            placeholder="e.g. Requested opt-out via phone"
            value={form.reason}
            onChange={e => setForm(p => ({ ...p, reason: e.target.value }))}
          />
        </div>
      </Modal>
    </div>
  )
}
