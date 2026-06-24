import React, { useEffect, useState } from 'react'
import {
  AreaChart, Area, BarChart, Bar, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend
} from 'recharts'
import {
  DollarSign, TrendingUp, MessageSquare, Globe2, Languages,
  CheckCircle2, XCircle, MousePointerClick, Users, Calendar, BarChart3
} from 'lucide-react'
import api from '../services/api'
import { useAuth } from '../context/AuthContext'
import { useTheme } from '../context/ThemeContext'
import { PageHeader } from '../components/layout/PageHeader'
import { Card, CardHeader } from '../components/ui/Card'
import { MetricCard } from '../components/charts/MetricCard'
import { Pagination } from '../components/ui/Pagination'
import { StatusBadge } from '../components/ui/Badge'
import { EmptyState } from '../components/ui/EmptyState'
import { Select } from '../components/ui/Input'
import { cn } from '../lib/cn'

const COLORS = ['#6366f1', '#22c55e', '#f59e0b', '#3b82f6', '#ec4899', '#14b8a6', '#f97316', '#8b5cf6']

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
            {p.name.includes('Cost') ? `$${Number(p.value).toFixed(2)}` : Number(p.value).toLocaleString()}
          </span>
        </p>
      ))}
    </div>
  )
}

function SummaryPeriodCard({ label, data, sym, icon: Icon, color }) {
  return (
    <Card className="flex-1">
      <div className="flex items-center gap-2 mb-4">
        <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${color}`}>
          <Icon size={14} />
        </div>
        <span className="text-sm font-semibold text-[var(--text-primary)]">{label}</span>
      </div>
      <div className="grid grid-cols-2 gap-3">
        {[
          { label: 'Sent',           value: data?.sent?.toLocaleString() ?? '—' },
          { label: 'Delivered',      value: `${data?.delivered?.toLocaleString() ?? '—'} (${data?.delivery_rate ?? 0}%)` },
          { label: 'Failed',         value: data?.failed?.toLocaleString() ?? '—' },
          { label: 'Clicks',         value: `${data?.clicks?.toLocaleString() ?? '—'} (${data?.click_rate ?? 0}%)` },
          { label: 'Cost',           value: `${sym}${(data?.cost ?? 0).toFixed(2)}` },
          { label: 'Contacts Added', value: data?.contacts_added?.toLocaleString() ?? '—' },
        ].map(({ label: l, value }) => (
          <div key={l}>
            <p className="text-[10px] uppercase tracking-wide font-semibold text-[var(--text-tertiary)] mb-0.5">{l}</p>
            <p className="text-sm font-medium text-[var(--text-primary)]">{value}</p>
          </div>
        ))}
      </div>
    </Card>
  )
}

const PERIOD_OPTS = [
  { value: 'daily',   label: 'Daily (30 days)' },
  { value: 'weekly',  label: 'Weekly (12 weeks)' },
  { value: 'monthly', label: 'Monthly (12 months)' },
]

export default function ReportsPage() {
  const { user }               = useAuth()
  const { isDark }             = useTheme()
  const [costs, setCosts]      = useState(null)
  const [delivery, setDel]     = useState([])
  const [countries, setCoun]   = useState([])
  const [languages, setLang]   = useState([])
  const [summary, setSummary]  = useState(null)
  const [loading, setLoading]  = useState(true)
  const [period, setPeriod]    = useState('daily')
  const [activeTab, setTab]    = useState('overview')

  const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)'
  const axisColor = isDark ? '#475569' : '#94a3b8'
  const sym = costs?.summary?.currency_symbol ?? '$'

  useEffect(() => {
    if (user?.role !== 'admin') return
    setLoading(true)
    Promise.all([
      api.get('/reports/costs'),
      api.get('/reports/countries'),
      api.get('/reports/languages'),
      api.get('/reports/summary'),
    ]).then(([c, k, l, s]) => {
      setCosts(c.data)
      setCoun(k.data.data?.slice(0, 10) ?? [])
      setLang(l.data.data?.slice(0, 10) ?? [])
      setSummary(s.data)
    }).finally(() => setLoading(false))
  }, [user])

  useEffect(() => {
    if (user?.role !== 'admin') return
    const params = { period }
    if (period === 'daily') params.days = 30
    api.get('/reports/delivery', { params }).then(r => setDel(r.data.data ?? []))
  }, [period, user])

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-5">
        <PageHeader title="Reports" />
        <Card><EmptyState icon={Globe2} title="Admin access required" description="Reports are available to administrators only." /></Card>
      </div>
    )
  }

  const summ = costs?.summary ?? {}

  const tabs = [
    { id: 'overview',   label: 'Overview' },
    { id: 'delivery',   label: 'Delivery' },
    { id: 'geography',  label: 'Countries' },
    { id: 'languages',  label: 'Languages' },
    { id: 'costs',      label: 'Costs' },
  ]

  return (
    <div className="space-y-6">
      <PageHeader title="Reports" subtitle="Analytics and cost breakdown" />

      {/* Summary period cards */}
      {!loading && summary && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <SummaryPeriodCard label="Today"        data={summary.daily}   sym={sym} icon={Calendar}   color="bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400" />
          <SummaryPeriodCard label="This Week"    data={summary.weekly}  sym={sym} icon={TrendingUp}  color="bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400" />
          <SummaryPeriodCard label="This Month"   data={summary.monthly} sym={sym} icon={BarChart3}   color="bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400" />
        </div>
      )}
      {loading && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
          {[...Array(3)].map((_, i) => <div key={i} className="h-44 skeleton rounded-xl" />)}
        </div>
      )}

      {/* Top KPIs */}
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {[
          { label: 'Total Messages', value: summ.total_messages?.toLocaleString() ?? '—', icon: MessageSquare, iconColor: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400' },
          { label: 'SMS Segments',   value: summ.total_segments?.toLocaleString() ?? '—', icon: TrendingUp,    iconColor: 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400' },
          { label: 'This Month',     value: `${sym}${(summ.cost_this_month ?? 0).toFixed(2)}`, icon: DollarSign, iconColor: 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400' },
          { label: 'Total Spend',    value: `${sym}${(summ.total_cost ?? 0).toFixed(2)}`,      icon: DollarSign, iconColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' },
        ].map(m => <MetricCard key={m.label} {...m} loading={loading} />)}
      </div>

      {/* Tab navigation */}
      <div className="flex items-center gap-1 border-b border-[var(--border)]">
        {tabs.map(t => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            className={cn(
              'px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px',
              activeTab === t.id
                ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                : 'border-transparent text-[var(--text-secondary)] hover:text-[var(--text-primary)]'
            )}
          >
            {t.label}
          </button>
        ))}
      </div>

      {/* Overview tab */}
      {activeTab === 'overview' && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <div className="flex items-center justify-between mb-4">
                <CardHeader title="Delivery Trend" subtitle={`${period === 'daily' ? 'Last 30 days' : period === 'weekly' ? 'Last 12 weeks' : 'Last 12 months'}`} />
                <Select value={period} onChange={e => setPeriod(e.target.value)} className="w-44 text-xs">
                  {PERIOD_OPTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
                </Select>
              </div>
              {loading ? <div className="h-56 skeleton rounded-lg" /> : (
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
                    <Area type="monotone" dataKey="failed"    name="Failed"    stroke="#ef4444" strokeWidth={2} fill="url(#rFailed)"    dot={false} />
                  </AreaChart>
                </ResponsiveContainer>
              )}
            </Card>

            <Card>
              <CardHeader title="Top Countries" subtitle="By contact volume" />
              {loading ? <div className="h-56 skeleton rounded-lg" /> : (
                <div>
                  <ResponsiveContainer width="100%" height={160}>
                    <PieChart>
                      <Pie data={countries} dataKey="contacts" cx="50%" cy="50%" outerRadius={65} innerRadius={38} paddingAngle={2}>
                        {countries.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                      </Pie>
                      <Tooltip content={<CustomTooltip />} />
                    </PieChart>
                  </ResponsiveContainer>
                  <div className="space-y-2 mt-3">
                    {countries.slice(0, 5).map(({ country, contacts, percentage }, i) => (
                      <div key={country} className="flex items-center gap-2">
                        <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ background: COLORS[i % COLORS.length] }} />
                        <span className="text-xs text-[var(--text-secondary)] flex-1 truncate">{country}</span>
                        <span className="text-xs tabular font-medium text-[var(--text-primary)]">{contacts?.toLocaleString()}</span>
                        <span className="text-xs text-[var(--text-tertiary)]">{percentage}%</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </Card>
          </div>
        </div>
      )}

      {/* Delivery tab */}
      {activeTab === 'delivery' && (
        <div className="space-y-6">
          <Card>
            <div className="flex items-center justify-between mb-4">
              <CardHeader title="Delivery Trend" />
              <Select value={period} onChange={e => setPeriod(e.target.value)} className="w-44 text-xs">
                {PERIOD_OPTS.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
              </Select>
            </div>
            {loading ? <div className="h-72 skeleton rounded-lg" /> : (
              <ResponsiveContainer width="100%" height={280}>
                <AreaChart data={delivery} margin={{ top: 4, right: 4, bottom: 0, left: -10 }}>
                  <defs>
                    <linearGradient id="dDelivered" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#22c55e" stopOpacity={0.25} />
                      <stop offset="95%" stopColor="#22c55e" stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="dTotal" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#6366f1" stopOpacity={0.15} />
                      <stop offset="95%" stopColor="#6366f1" stopOpacity={0} />
                    </linearGradient>
                    <linearGradient id="dFailed" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="#ef4444" stopOpacity={0.2} />
                      <stop offset="95%" stopColor="#ef4444" stopOpacity={0} />
                    </linearGradient>
                  </defs>
                  <CartesianGrid strokeDasharray="3 3" stroke={gridColor} vertical={false} />
                  <XAxis dataKey="date" tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                  <YAxis tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                  <Tooltip content={<CustomTooltip />} />
                  <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '12px', paddingTop: '12px' }} />
                  <Area type="monotone" dataKey="total"     name="Total"     stroke="#6366f1" strokeWidth={2} fill="url(#dTotal)"     dot={false} />
                  <Area type="monotone" dataKey="delivered" name="Delivered" stroke="#22c55e" strokeWidth={2} fill="url(#dDelivered)" dot={false} />
                  <Area type="monotone" dataKey="failed"    name="Failed"    stroke="#ef4444" strokeWidth={2} fill="url(#dFailed)"    dot={false} />
                </AreaChart>
              </ResponsiveContainer>
            )}
          </Card>

          {delivery.length > 0 && (
            <Card padding={false}>
              <div className="px-5 py-4 border-b border-[var(--border)]">
                <h3 className="text-sm font-semibold text-[var(--text-primary)]">Delivery Data</h3>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                      {['Date', 'Total', 'Delivered', 'Failed', 'Cost'].map(h => (
                        <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[var(--border)]">
                    {delivery.map((row, i) => (
                      <tr key={i} className="hover:bg-[var(--surface-2)] transition-colors">
                        <td className="px-4 py-2.5 text-sm text-[var(--text-primary)]">{row.date}</td>
                        <td className="px-4 py-2.5 text-sm tabular text-[var(--text-secondary)]">{Number(row.total).toLocaleString()}</td>
                        <td className="px-4 py-2.5 text-sm tabular text-emerald-600">{Number(row.delivered).toLocaleString()}</td>
                        <td className="px-4 py-2.5 text-sm tabular text-red-500">{Number(row.failed).toLocaleString()}</td>
                        <td className="px-4 py-2.5 text-sm tabular text-[var(--text-secondary)]">${parseFloat(row.cost ?? 0).toFixed(2)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Card>
          )}
        </div>
      )}

      {/* Countries tab */}
      {activeTab === 'geography' && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card>
              <CardHeader title="Contacts by Country" subtitle="Distribution overview" />
              {loading ? <div className="h-72 skeleton rounded-lg" /> : (
                <ResponsiveContainer width="100%" height={280}>
                  <PieChart>
                    <Pie data={countries} dataKey="contacts" nameKey="country" cx="50%" cy="50%" outerRadius={100} innerRadius={50} paddingAngle={2} label={({ country, percentage }) => `${country} ${percentage}%`} labelLine={false}>
                      {countries.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                  </PieChart>
                </ResponsiveContainer>
              )}
            </Card>

            <Card>
              <CardHeader title="Delivery by Country" subtitle="Messages sent vs delivered" />
              {loading ? <div className="h-72 skeleton rounded-lg" /> : (
                <ResponsiveContainer width="100%" height={280}>
                  <BarChart data={countries} layout="vertical" margin={{ left: 4, right: 16 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke={gridColor} horizontal={false} />
                    <XAxis type="number" tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                    <YAxis dataKey="country" type="category" width={110} tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                    <Tooltip content={<CustomTooltip />} />
                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '12px', paddingTop: '8px' }} />
                    <Bar dataKey="delivered" name="Delivered" fill="#22c55e" radius={[0, 4, 4, 0]} />
                    <Bar dataKey="failed"    name="Failed"    fill="#ef4444" radius={[0, 4, 4, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              )}
            </Card>
          </div>

          <Card padding={false}>
            <div className="px-5 py-4 border-b border-[var(--border)]">
              <h3 className="text-sm font-semibold text-[var(--text-primary)]">Country Breakdown</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                    {['Country', 'Contacts', 'Opted In', 'Share', 'Messages Sent', 'Delivered', 'Failed', 'Delivery Rate'].map(h => (
                      <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-[var(--border)]">
                  {countries.map((c, i) => (
                    <tr key={c.country} className="hover:bg-[var(--surface-2)] transition-colors">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ background: COLORS[i % COLORS.length] }} />
                          <span className="text-sm font-medium text-[var(--text-primary)]">{c.country}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{Number(c.contacts).toLocaleString()}</td>
                      <td className="px-4 py-3 text-sm tabular text-emerald-600">{Number(c.opted_in).toLocaleString()}</td>
                      <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{c.percentage}%</td>
                      <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{Number(c.messages_sent).toLocaleString()}</td>
                      <td className="px-4 py-3 text-sm tabular text-emerald-600">{Number(c.delivered).toLocaleString()}</td>
                      <td className="px-4 py-3 text-sm tabular text-red-500">{Number(c.failed).toLocaleString()}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 h-1.5 rounded-full bg-[var(--surface-2)] overflow-hidden max-w-[80px]">
                            <div className="h-full rounded-full bg-emerald-500" style={{ width: `${c.delivery_rate}%` }} />
                          </div>
                          <span className="text-xs tabular text-[var(--text-secondary)]">{c.delivery_rate}%</span>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        </div>
      )}

      {/* Languages tab */}
      {activeTab === 'languages' && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card>
              <CardHeader title="Contacts by Language" subtitle="Distribution overview" />
              {loading ? <div className="h-72 skeleton rounded-lg" /> : languages.length === 0 ? (
                <EmptyState icon={Languages} title="No language data" description="Language data will appear here once contacts have language set." />
              ) : (
                <ResponsiveContainer width="100%" height={280}>
                  <PieChart>
                    <Pie data={languages} dataKey="total" nameKey="language" cx="50%" cy="50%" outerRadius={100} innerRadius={50} paddingAngle={2}>
                      {languages.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                    </Pie>
                    <Tooltip content={<CustomTooltip />} />
                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: '12px', paddingTop: '8px' }} />
                  </PieChart>
                </ResponsiveContainer>
              )}
            </Card>

            <Card>
              <CardHeader title="Language Distribution" subtitle="Top languages by contact count" />
              {loading ? <div className="h-72 skeleton rounded-lg" /> : languages.length === 0 ? (
                <EmptyState icon={Languages} title="No language data" />
              ) : (
                <ResponsiveContainer width="100%" height={280}>
                  <BarChart data={languages} layout="vertical" margin={{ left: 4, right: 16 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke={gridColor} horizontal={false} />
                    <XAxis type="number" tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                    <YAxis dataKey="language" type="category" width={90} tick={{ fontSize: 11, fill: axisColor }} tickLine={false} axisLine={false} />
                    <Tooltip content={<CustomTooltip />} />
                    <Bar dataKey="total" name="Total" fill="#6366f1" radius={[0, 4, 4, 0]} />
                    <Bar dataKey="opted_in" name="Opted In" fill="#22c55e" radius={[0, 4, 4, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              )}
            </Card>
          </div>

          {languages.length > 0 && (
            <Card padding={false}>
              <div className="px-5 py-4 border-b border-[var(--border)]">
                <h3 className="text-sm font-semibold text-[var(--text-primary)]">Language Breakdown</h3>
              </div>
              <table className="w-full">
                <thead>
                  <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                    {['Language', 'Total Contacts', 'Opted In', 'Share'].map(h => (
                      <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-[var(--border)]">
                  {languages.map((l, i) => (
                    <tr key={l.language} className="hover:bg-[var(--surface-2)] transition-colors">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ background: COLORS[i % COLORS.length] }} />
                          <span className="text-sm font-medium text-[var(--text-primary)]">{l.language}</span>
                        </div>
                      </td>
                      <td className="px-4 py-3 text-sm tabular text-[var(--text-secondary)]">{Number(l.total).toLocaleString()}</td>
                      <td className="px-4 py-3 text-sm tabular text-emerald-600">{Number(l.opted_in).toLocaleString()}</td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          <div className="flex-1 h-1.5 rounded-full bg-[var(--surface-2)] overflow-hidden max-w-[80px]">
                            <div className="h-full rounded-full bg-brand-500" style={{ width: `${l.percentage}%` }} />
                          </div>
                          <span className="text-xs tabular text-[var(--text-secondary)]">{l.percentage}%</span>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </Card>
          )}
        </div>
      )}

      {/* Costs tab */}
      {activeTab === 'costs' && (
        <div className="space-y-6">
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {[
              { label: 'Total Messages',  value: summ.total_messages?.toLocaleString() ?? '—', icon: MessageSquare, iconColor: 'bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400' },
              { label: 'Total Segments',  value: summ.total_segments?.toLocaleString() ?? '—', icon: TrendingUp,    iconColor: 'bg-purple-50 text-purple-600 dark:bg-purple-950 dark:text-purple-400' },
              { label: 'Today',           value: `${sym}${(summ.cost_today ?? 0).toFixed(2)}`, icon: DollarSign,   iconColor: 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400' },
              { label: 'Total Spend',     value: `${sym}${(summ.total_cost ?? 0).toFixed(2)}`, icon: DollarSign,   iconColor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' },
            ].map(m => <MetricCard key={m.label} {...m} loading={loading} />)}
          </div>

          {costs?.data?.data?.length > 0 && (
            <Card padding={false}>
              <div className="px-5 py-4 border-b border-[var(--border)]">
                <h3 className="text-sm font-semibold text-[var(--text-primary)]">Campaign Cost Breakdown</h3>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-[var(--border)] bg-[var(--surface-2)]">
                      {['Campaign', 'Status', 'Messages', 'SMS Segments', 'Cost'].map(h => (
                        <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-[var(--text-tertiary)] uppercase tracking-wide whitespace-nowrap">{h}</th>
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
              </div>
              <Pagination meta={costs.data.meta} onPageChange={() => {}} />
            </Card>
          )}
        </div>
      )}
    </div>
  )
}

