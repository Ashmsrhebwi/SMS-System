import React from 'react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import { Home, ArrowLeft } from 'lucide-react'
import { Button } from '../components/ui/Button'

export default function NotFoundPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-[var(--bg)] px-4">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
        className="text-center max-w-md"
      >
        {/* Big 404 */}
        <div className="relative mb-8">
          <p className="text-[10rem] font-black text-[var(--surface-2)] leading-none select-none">404</p>
          <div className="absolute inset-0 flex items-center justify-center">
            <div className="h-24 w-24 rounded-2xl bg-brand-50 dark:bg-brand-950 flex items-center justify-center shadow-[var(--shadow-lg)]">
              <span className="text-4xl">🔍</span>
            </div>
          </div>
        </div>

        <h1 className="text-2xl font-bold text-[var(--text-primary)] mb-2">Page not found</h1>
        <p className="text-sm text-[var(--text-secondary)] mb-8 leading-relaxed">
          The page you're looking for doesn't exist or has been moved.
        </p>

        <div className="flex items-center justify-center gap-3">
          <Button
            variant="secondary"
            leftIcon={<ArrowLeft size={14} />}
            onClick={() => window.history.back()}
          >
            Go back
          </Button>
          <Link to="/">
            <Button leftIcon={<Home size={14} />}>
              Dashboard
            </Button>
          </Link>
        </div>
      </motion.div>
    </div>
  )
}
