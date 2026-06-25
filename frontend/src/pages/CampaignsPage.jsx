import React, { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Search, Plus, Send, Filter, Download } from 'lucide-react'
import api from '../services/api'
import { PageHeader } from '../components/layout/PageHeader'
import { Card } from '../components/ui/Card'
import { Button } from '../components/ui/Button'
import { Input } from '../components/ui/Input'
import { StatusBadge } from '../components/ui/Badge'
import { Pagination } from '../components/ui/Pagination'
import { SkeletonTable } from '../components/ui/Skeleton'
import { EmptyState } from '../components/ui/EmptyState'
import { cn } from '../lib/cn'

const STATUS_TABS = ['all', 'draft', 'scheduled', 'sending', 'completed', 'failed']

export default function CampaignsPage() {
  const [data, setData]       = useState(null)
  const [page, setPage]       = useState(1)
  const [search, setSearch]   = useState('')
  const [status, setStatus]   = useState('all')
  const [loading, setLoading] = useState(true)

  const load = (p = 1) => {
    setLoading(true)
    api.get('/campaigns', { params: { page: p, search, status: status === 'all' ? '' : status } })
      .then(r => setData(r.data))
      .catch(() => {
        // Keep existing data on error; loading indicator will clear
      })
      .finally(() => setLoading(false))
  }

  useEffect(() => { load(1); setPage(1) }, [search, status])
  useEffect(() => { if (page > 1) load(page) }, [page])

  return (
    <div className="space-y-5">
      <PageHeader
        title="Campaigns"
        subtitle={`${data?.meta?.total ?? 0} total campaigns`}
        action={
          <Link to="/campaigns/new">
            <Button leftIcon={<Plus size={14} />}>New Campaign</Button>
          </Link>
        }
      />

      {/* Filter bar */}
      <div className="flex flex-col sm:flex-row gap-3">
        <Input
          placeholder="Search campaigns…"
          leftIcon={<Search size={14} />}
          value={search}
          onChange={e => setSearch(e.target.value)}
          className="sm:w-72"
        />
        <div className="flex gap-1 bg-[var(--surface-2)] border border-[var(--border)] rounded-lg p-1 overflow-x-auto shrink-0">
          {STATUS_TABS.map(s => (
            <button
              key={s}
              onClick={() => setStatus(s)}
              className={cn(
                'px-3 py-1.5 rounded-md text-xs font-medium capitalize whitespace-nowrap transition-all',
                status === s
                  ? 'bg-[var(--surface)] text-[var(--text-primary)] shadow-[var(--shadow-xs)]'
                  : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]'
              )}
            >
              {s}
            </button>
          ))}
        </div>
      </div>

      {/* Table */}
      {loading ? (
        <SkeletonTable rows={6} />
      ) : !data?.data?.length ? (
        <Card>
          <EmptyState
            icon={Send}
            title="No campaigns yet"
            description="Create your first campaign to start reaching your patients."
            action={() => {}}
            actionLabel="New Campaign"
          />
        </Card>
      ) : (
        <Card padding={false} className="overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Campaign', 'Status', 'Segment', 'Recipients', 'Scheduled', 'Created', ''].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">
                    {h}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {data.data.map((c, i) => (
                <motion.tr
                  key={c.id}
                  className="table-row-hover"
                  initial={{ opacity: 0, y: 4 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.15, delay: i * 0.03 }}
                >
                  <td className="px-4 py-3.5">
                    <Link to={`/campaigns/${c.id}`} className="font-medium text-sm text-[var(--text-primary)] hover:text-brand-600 transition-colors">
                      {c.name}
                    </Link>
                  </td>
                  <td className="px-4 py-3.5">
                    <StatusBadge status={c.status} />
                  </td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)]">
                    {c.segment?.name ?? <span className="text-[var(--text-tertiary)]">All contacts</span>}
                  </td>
                  <td className="px-4 py-3.5 text-sm tabular text-[var(--text-secondary)]">
                    {c.total_recipients?.toLocaleString() ?? '—'}
                  </td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-secondary)]">
                    {c.scheduled_at ? new Date(c.scheduled_at).toLocaleDateString() : '—'}
                  </td>
                  <td className="px-4 py-3.5 text-sm text-[var(--text-tertiary)]">
                    {new Date(c.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3.5 text-right">
                    <Link to={`/campaigns/${c.id}`}>
                      <Button variant="ghost" size="xs">View</Button>
                    </Link>
                  </td>
                </motion.tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={data?.meta} onPageChange={setPage} />
        </Card>
      )}
    </div>
  )
}
