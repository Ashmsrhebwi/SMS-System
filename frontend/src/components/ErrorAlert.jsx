import React from 'react'

export default function ErrorAlert({ error }) {
  if (!error) return null

  const message = typeof error === 'string'
    ? error
    : error?.response?.data?.message ?? error?.message ?? 'An unexpected error occurred.'

  const errors = error?.response?.data?.errors

  return (
    <div className="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
      <p className="font-medium">{message}</p>
      {errors && (
        <ul className="mt-1 list-disc list-inside space-y-0.5">
          {Object.values(errors).flat().map((msg, i) => (
            <li key={i}>{msg}</li>
          ))}
        </ul>
      )}
    </div>
  )
}
