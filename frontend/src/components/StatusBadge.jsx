import React from 'react'

const STATUS_CLASS = {
  draft:       'badge-gray',
  scheduled:   'badge-blue',
  queued:      'badge-yellow',
  sending:     'badge-yellow',
  sent:        'badge-blue',
  delivered:   'badge-green',
  failed:      'badge-red',
  undelivered: 'badge-red',
  completed:   'badge-green',
  active:      'badge-green',
  inactive:    'badge-gray',
}

export default function StatusBadge({ status }) {
  return (
    <span className={STATUS_CLASS[status] ?? 'badge-gray'}>
      {status}
    </span>
  )
}
