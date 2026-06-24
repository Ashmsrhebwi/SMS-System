import React, { useEffect, useState } from 'react'
import {
  AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend
} from 'recharts'
import { DollarSign, TrendingUp, MessageSquare, Globe2 } from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useTheme } from '../context/ThemeContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { MetricCard } from '../components/charts/MetricCard'
import { Pagination } from '../components/ui/Pagination'
import { StatusBadge } from '../components/ui/Badge'
import { EmptyState } from '../components/ui/EmptyState'

const COLORS = ['#6366f1', '#22c55e', '#f59e0b', '#3b82f6', '#ec4899', '#14b8a6']

const CustomTooltip = ({ active, payload, label }) => {
  if (!active || !payload?.length) return null
  return (
    <div className="bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3 py-2.5 shadow-[var(--shadow-lg)] text-xs">
      <p className="text-[var(--text-tertiary)] mb-1.5 font-medium">{label}</p>
      {payload.map(p => (
        <p key={p.name} className="flex items-center gap-2">
          <span className="w-2 h-2 rounded-full" style={{ background: p.color }} />
          <span className="text-[var(--text-secondary)]">{p.name}:</span>
          <span className="text-[var(--text-primary)] font-semibold">
            {p.name.includes('Cost') ? `$${Number(p.value).toFixed(2)}` : p.value?.toLocaleString()}
          </span>
        </p>
      ))}
    </div>
  )
}

export default function ReportsPage() {
  const { user }              = useAuth()
  const { isDark }            = useTheme()
  const [costs, setCosts]     = useState(null)
  const [delivery, setDel]    = useState([])
  const [countries, setCoun]  = useState([])
  const [loading, setLoading] = useState(true)
  const [costPage, setCostPage] = useState(1)

  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)'
  const axisColor = isDark ? '#475569' : '#94a3b8'

  useEffect(() => {
    if (user?.role !== 'admin') return
    Promise.all([
      api.get('/reports/costs'),
      api.get('/reports/delivery', { params: { days: 30 } }),
      api.get('/reports/countries'),
    ]).then(([c, d, k]) => {
      setCosts(c.data)
      setDel(d.data.data ?? [])
      setCoun(k.data.data?.slice(0, 8) ?? [])
    }).finally(() => setLoading(false))
  }, [])

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Reports" />
        <Card><EmptyState icon={Globe2} title="Admin access required" description="Reports are available to administrators only." /></Card>
      </div>
    )
  }

  const sym  = costs?.summary?.currency_symbol ?? '$'
  const summ = costs?.summary ?? {}

  return (
    <div className="space-y-6">
      <PageHeader title="Reports" subtitle="Analytics and cost breakdown" />

      {/* Summary metrics */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {[
          { label: 'Total Messages',  value: summ.total_messages?.toLocaleString() ?? '—', icon: MessageSquare, iconColor: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400' },
          { label: 'Total Segments',  value: summ.total_segments?.toLocaleString() ?? '—', icon: TrendingUp,    iconColor: 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400' },
          { label: 'This Month',      value: `${sym}${(summ.cost_this_month ?? 0).toFixed(2)}`,  icon: DollarSign,   iconColor: 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400' },
          { label: 'Total Spend',     value: `${sym}${(summ.total_cost ?? 0).toFixed(2)}`,        icon: DollarSign,   iconColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' },
        ].map(m => <MetricCard key={m.label} {...m} loading={loading} />)}
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Delivery trend */}
        <Card className="lg:col-span-2">
          <CardHeader title="Delivery Trend" subtitle="Last 30 days" />
          {loading ? (
            <div className="h-56 skeleton rounded-lg" />
          ) : (
            <ResponsiveContainer width="100%" height={220}>
              <AreaChart data={delivery} margin={{ top: 4, right: 4, bottom: 0, left: -10 }}>
                <defs>
                  <linearGradient id="rDelivered" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#22c55e" stopOpacity={0.25} />
                    <stop offset="95%" stopColor="#22c55e" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="rFailed" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#ef4444" stopOpacity={0.2} />
                    <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke={gridColor} vertical={false} />
                <XAxis dataKey="date" tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                <Tooltip content={<CustomTooltip />} />
                <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '12px', paddingTop: '12px' }} />
                <Area type="monotone" dataKey="delivered" name="Delivered" stroke="#22c55e" strokeWidth={2} fill="url(#rDelivered)" dot={false} />
                <Area type="monotone" dataKey="failed" name="Failed" stroke="#ef4444" strokeWidth={2} fill="url(#rFailed)" dot={false} />
              </AreaChart>
            </ResponsiveContainer>
          )}
        </Card>

        {/* Country pie */}
        <Card>
          <CardHeader title="Top Countries" subtitle="By contact volume" />
          {loading ? (
            <div className="h-56 skeleton rounded-lg" />
          ) : (
            <div>
              <ResponsiveContainer width="100%" height={160}>
                <PieChart>
                  <Pie data={countries} dataKey="contacts" cx="50%" cy="50%" outerRadius={65} innerRadius={38} paddingAngle={2}>
                    {countries.map((_, i) => (
                      <Cell key={i} fill={COLORS[i % COLORS.length]} />
                    ))}
                  </Pie>
                  <Tooltip content={<CustomTooltip />} />
                </PieChart>
              </ResponsiveContainer>
              <div className="space-y-2 mt-3">
                {countries.slice(0, 5).map(({ country, contacts }, i) => (
                  <div key={country} className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ background: COLORS[i % COLORS.length] }} />
                    <span className="text-xs text-[var(--text-secondary)] flex-1 truncate">{country}</span>
                    <span className="text-xs tabular font-medium text-[var(--text-primary)]">{contacts}</span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </Card>
      </div>

      {/* Country bar chart */}
      {countries.length > 0 && (
        <Card>
          <CardHeader title="Delivery by Country" />
          <ResponsiveContainer width="100%" height={260}>
            <BarChart data={countries} layout="vertical" margin={{ left: 16, right: 16 }}>
              <CartesianGrid strokeDasharray="3 3" stroke={gridColor} horizontal={false} />
              <XAxis type="number" tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
              <YAxis dataKey="country" type="category" width={90} tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
              <Tooltip content={<CustomTooltip />} />
              <Bar dataKey="delivered" name="Delivered" fill="#22c55e" radius={[0, 4, 4, 0]} />
              <Bar dataKey="failed"    name="Failed"    fill="#ef4444" radius={[0, 4, 4, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </Card>
      )}

      {/* Campaign cost table */}
      {costs?.data?.data?.length > 0 && (
        <Card padding={false}>
          <div className="px-5 py-4 border-b border-[var(--border)]">
            <h3 className="text-sm font-semibold text-[var(--text-primary)]">Campaign Cost Breakdown</h3>
          </div>
          <table className="w-full">
            <thead>
              <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                {['Campaign', 'Status', 'Messages', 'Segments', 'Cost'].map(h => (
                  <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--border)]">
              {costs.data.data.map(c => (
                <tr key={c.id} className="hover:bg-[var(--surface-2)] transition-colors">
                  <td className="px-4 py-3 text-sm font-medium text-[var(--text-primary)]">{c.name}</td>
                  <td className="px-4 py-3"><StatusBadge status={c.status} /></td>
                  <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{Number(c.message_count).toLocaleString()}</td>
                  <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{Number(c.total_segments ?? 0).toLocaleString()}</td>
                  <td className="px-4 py-3 text-sm tabular font-medium text-[var(--text-primary)]">{sym}{parseFloat(c.total_cost ?? 0).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <Pagination meta={costs.data.meta} onPageChange={setCostPage} />
        </Card>
      )}
    </div>
  )
}
