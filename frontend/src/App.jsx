import React from 'react'
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider, useAuth } from './context/AuthContext'
import { ThemeProvider } from './context/ThemeContext'
import { ToastProvider } from './context/ToastContext'
import { ToastContainer } from './components/ui/Toast'
import AppLayout from './components/AppLayout'

import LoginPage          from './pages/LoginPage'
import OtpPage            from './pages/OtpPage'
import ForgotPasswordPage from './pages/ForgotPasswordPage'
import ResetPasswordPage  from './pages/ResetPasswordPage'
import DashboardPage      from './pages/DashboardPage'
import CampaignsPage      from './pages/CampaignsPage'
import CampaignWizard     from './features/campaigns/CampaignWizard'
import CampaignDetailPage from './pages/CampaignDetailPage'
import ContactsPage       from './pages/ContactsPage'
import TemplatesPage      from './pages/TemplatesPage'
import TagsPage           from './pages/TagsPage'
import SegmentsPage       from './pages/SegmentsPage'
import ReportsPage        from './pages/ReportsPage'
import UsersPage          from './pages/UsersPage'
import ContactDetailPage  from './pages/ContactDetailPage'
import SettingsPage       from './pages/SettingsPage'
import AuditLogsPage      from './pages/AuditLogsPage'
import BlacklistPage      from './pages/BlacklistPage'
import SuppressionPage    from './pages/SuppressionPage'
import SmartSegmentPage   from './pages/SmartSegmentPage'
import NotFoundPage       from './pages/NotFoundPage'

function RequireAuth({ children }) {
  const { user } = useAuth()
  if (!user) return <Navigate to="/login" replace />
  return children
}

function RequireGuest({ children }) {
  const { user } = useAuth()
  if (user) return <Navigate to="/" replace />
  return children
}

export default function App() {
  return (
    <AuthProvider>
      <ThemeProvider>
        <ToastProvider>
          <BrowserRouter basename={import.meta.env.PROD ? '/spa' : '/'}>
            <ToastContainer />
            <Routes>
              {/* Guest */}
              <Route path="/login"           element={<RequireGuest><LoginPage /></RequireGuest>} />
              <Route path="/otp"             element={<OtpPage />} />
              <Route path="/forgot-password" element={<RequireGuest><ForgotPasswordPage /></RequireGuest>} />
              <Route path="/reset-password"  element={<RequireGuest><ResetPasswordPage /></RequireGuest>} />

              {/* Authenticated */}
              <Route element={<RequireAuth><AppLayout /></RequireAuth>}>
                <Route index element={<DashboardPage />} />
                <Route path="campaigns"      element={<CampaignsPage />} />
                <Route path="campaigns/new"  element={<CampaignWizard />} />
                <Route path="campaigns/:id"  element={<CampaignDetailPage />} />
                <Route path="contacts"       element={<ContactsPage />} />
                <Route path="contacts/:id"  element={<ContactDetailPage />} />
                <Route path="templates"      element={<TemplatesPage />} />
                <Route path="tags"           element={<TagsPage />} />
                <Route path="segments"       element={<SegmentsPage />} />
                <Route path="reports"        element={<ReportsPage />} />
                <Route path="users"          element={<UsersPage />} />
                <Route path="settings"       element={<SettingsPage />} />
                <Route path="audit-logs"     element={<AuditLogsPage />} />
                <Route path="blacklist"      element={<BlacklistPage />} />
                <Route path="suppression"      element={<SuppressionPage />} />
                <Route path="smart-segments"  element={<SmartSegmentPage />} />
              </Route>

              <Route path="*" element={<NotFoundPage />} />
            </Routes>
          </BrowserRouter>
        </ToastProvider>
      </ThemeProvider>
    </AuthProvider>
  )
}
