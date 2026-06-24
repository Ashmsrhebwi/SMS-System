import React, { useEffect, useState } from 'react'
import { motion } from 'framer-motion'
import { Plus, Edit2, Trash2, Users, ShieldCheck, UserCheck, UserX, ToggleLeft, ToggleRight } from 'lucide-react'
import api from '../services/api'
import { useToast } from '../context/ToastContext'
import { useAuth } from '../context/AuthContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input, Select } from '../components/ui/Input'
import { Badge } from '../components/ui/Badge'
import { Modal } from '../components/ui/Modal'
import { EmptyState } from '../components/ui/EmptyState'
import { SkeletonTable } from '../components/ui/Skeleton'
import { Avatar } from '../components/ui/Avatar'

export default function UsersPage() {
  const { user: me }          = useAuth()
  const { toast }             = useToast()
  const [users, setUsers]     = useState([])
  const [loading, setLoading] = useState(true)
  const [modal, setModal]     = useState(null) // null | 'create' | user object
  const [saving, setSaving]   = useState(false)
  const [form, setForm]       = useState({ name: '', email: '', password: '', role: 'staff', is_active: true })

  const load = () => {
    setLoading(true)
    api.get('/users').then(r => setUsers(r.data.data ?? [])).finally(() => setLoading(false))
  }

  useEffect(() => { load() }, [])

  if (me?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Users" />
        <Card>
          <EmptyState icon={ShieldCheck} title="Admin access required" description="User management is available to administrators only." />
        </Card>
      </div>
    )
  }

  const openCreate = () => {
    setForm({ name: '', email: '', password: '', role: 'staff', is_active: true })
    setModal('create')
  }

  const openEdit = (u) => {
    setForm({ name: u.name, email: u.email, password: '', role: u.role, is_active: u.is_active })
    setModal(u)
  }

  const save = async () => {
    setSaving(true)
    try {
      const payload = { ...form }
      if (!payload.password) delete payload.password
      if (modal === 'create') {
        await api.post('/users', payload)
        toast.success('User created')
      } else {
        await api.put(`/users/${modal.id}`, payload)
        toast.success('User updated')
      }
      setModal(null)
      load()
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Save failed')
    } finally {
      setSaving(false)
    }
  }

  const toggleActive = async (u) => {
    try {
      await api.post(`/users/${u.id}/toggle-active`)
      toast.success(`${u.name} ${u.is_active ? 'deactivated' : 'activated'}`)
      load()
    } catch {
      toast.error('Action failed')
    }
  }

  const remove = async (u) => {
    if (!confirm(`Delete user "${u.name}"? This cannot be undone.`)) return
    try {
      await api.delete(`/users/${u.id}`)
      toast.success('User deleted')
      load()
    } catch {
      toast.error('Delete failed')
    }
  }

  const admins = users.filter(u => u.role === 'admin').length
  const active = users.filter(u => u.is_active).length

  return (
    <div className="space-y-5">
      <PageHeader
        title="Users"
        subtitle={`${users.length} users · ${admins} admin${admins !== 1 ? 's' : ''} · ${active} active`}
        action={<Button leftIcon={<Plus size={14} />} onClick={openCreate}>New User</Button>}
      />

      {/* Stats row */}
      <div className="grid grid-cols-3 gap-4">
        {[
          { label: 'Total Users',   value: users.length,         icon: Users,     color: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400' },
          { label: 'Active',        value: active,               icon: UserCheck, color: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' },
          { label: 'Administrators',value: admins,               icon: ShieldCheck,color: 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400' },
        ].map(s => (
          <div key={s.label} className="rounded-xl bg-[var(--surface)] border border-[var(--border)] p-4 shadow-[var(--shadow-sm)]">
            <div className="flex items-center gap-2 mb-2">
              <div className={`flex h-7 w-7 items-center justify-center rounded-lg ${s.color}`}>
                <s.icon size={14} />
              </div>
              <span className="text-xs font-medium text-[var(--text-tertiary)] uppercase tracking-wide">{s.label}</span>
            </div>
            <p className="text-2xl font-bold text-[var(--text-primary)] tabular">{s.value}</p>
          </div>
        ))}
      </div>

      {loading ? (
        <SkeletonTable rows={5} />
      ) : !users.length ? (
        <Card>
          <EmptyState icon={Users} title="No users found" description="Add team members to give them access." />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['User', 'Email', 'Role', 'Status', 'Actions'].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {users.map((u, i) => (
                <motion.tr
                  key={u.id}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: i * 0.03 }}
                  className="hover:bg-[var(--surface-2)] transition-colors"
                >
                  <td className="px-4 py-3.5">
                    <div className="flex items-center gap-3">
                      <Avatar name={u.name} size="sm" />
                      <div>
                        <p className="text-sm font-medium text-[var(--text-primary)]">
                          {u.name}
                          {u.id === me?.id && (
                            <span className="ml-2 text-xs text-brand-500 font-normal">(you)</span>
                          )}
                        </p>
                      </div>
                    </div>
                  </td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)]">{u.email}</td>
                  <td className="px-4 py-3.5">
                    <Badge variant={u.role === 'admin' ? 'purple' : 'default'} size="sm">
                      {u.role === 'admin' ? 'Admin' : 'Staff'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3.5">
                    <Badge variant={u.is_active ? 'success' : 'danger'} size="sm">
                      {u.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </td>
                  <td className="px-4 py-3.5">
                    <div className="flex items-center gap-1">
                      <button
                        onClick={() => openEdit(u)}
                        className="p-1.5 rounded-lg hover:bg-[var(--surface-2)] text-[var(--text-tertiary)] hover:text-[var(--text-primary)] transition-colors"
                        title="Edit"
                      >
                        <Edit2 size={13} />
                      </button>
                      <button
                        onClick={() => toggleActive(u)}
                        className={`p-1.5 rounded-lg transition-colors ${
                          u.is_active
                            ? 'hover:bg-amber-50 dark:hover:bg-amber-950 text-[var(--text-tertiary)] hover:text-amber-600'
                            : 'hover:bg-emerald-50 dark:hover:bg-emerald-950 text-[var(--text-tertiary)] hover:text-emerald-600'
                        }`}
                        title={u.is_active ? 'Deactivate' : 'Activate'}
                      >
                        {u.is_active ? <ToggleRight size={13} /> : <ToggleLeft size={13} />}
                      </button>
                      {u.id !== me?.id && (
                        <button
                          onClick={() => remove(u)}
                          className="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-[var(--text-tertiary)] hover:text-red-600 transition-colors"
                          title="Delete"
                        >
                          <Trash2 size={13} />
                        </button>
                      )}
                    </div>
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}

      <Modal
        open={!!modal}
        onClose={() => setModal(null)}
        title={modal === 'create' ? 'New User' : 'Edit User'}
        size="md"
        footer={
          <>
            <Button variant="secondary" onClick={() => setModal(null)}>Cancel</Button>
            <Button loading={saving} onClick={save}>{modal === 'create' ? 'Create User' : 'Save Changes'}</Button>
          </>
        }
      >
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Full name"
              required
              value={form.name}
              onChange={e => setForm(p => ({ ...p, name: e.target.value }))}
            />
            <Input
              label="Email address"
              type="email"
              required
              value={form.email}
              onChange={e => setForm(p => ({ ...p, email: e.target.value }))}
            />
          </div>
          <Input
            label={modal !== 'create' ? 'New password (leave blank to keep)' : 'Password'}
            type="password"
            required={modal === 'create'}
            value={form.password}
            onChange={e => setForm(p => ({ ...p, password: e.target.value }))}
            hint="Minimum 12 characters"
          />
          <Select
            label="Role"
            value={form.role}
            onChange={e => setForm(p => ({ ...p, role: e.target.value }))}
          >
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </Select>
          {modal !== 'create' && (
            <label className="flex items-center gap-3 cursor-pointer">
              <div
                onClick={() => setForm(p => ({ ...p, is_active: !p.is_active }))}
                className={`relative h-5 w-9 rounded-full transition-colors ${form.is_active ? 'bg-brand-600' : 'bg-[var(--surface-3)]'}`}
              >
                <div className={`absolute top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform ${form.is_active ? 'translate-x-4' : 'translate-x-0.5'}`} />
              </div>
              <span className="text-sm text-[var(--text-secondary)]">Active account</span>
            </label>
          )}
        </div>
      </Modal>
    </div>
  )
}
