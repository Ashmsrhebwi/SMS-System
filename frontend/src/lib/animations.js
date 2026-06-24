// Framer Motion variants — import and spread as needed

export const fadeUp = {
  initial: { opacity: 0, y: 12 },
  animate: { opacity: 1, y: 0 },
  exit:    { opacity: 0, y: -6 },
  transition: { duration: 0.18, ease: [0.25, 0.46, 0.45, 0.94] },
}

export const fadeIn = {
  initial:  { opacity: 0 },
  animate:  { opacity: 1 },
  exit:     { opacity: 0 },
  transition: { duration: 0.15 },
}

export const slideRight = {
  initial: { x: '100%', opacity: 0 },
  animate: { x: 0, opacity: 1 },
  exit:    { x: '100%', opacity: 0 },
  transition: { type: 'spring', damping: 32, stiffness: 300, mass: 0.8 },
}

export const scaleIn = {
  initial: { opacity: 0, scale: 0.96 },
  animate: { opacity: 1, scale: 1 },
  exit:    { opacity: 0, scale: 0.96 },
  transition: { duration: 0.15, ease: [0.25, 0.46, 0.45, 0.94] },
}

export const slideUp = {
  initial: { opacity: 0, y: 16, scale: 0.96 },
  animate: { opacity: 1, y: 0, scale: 1 },
  exit:    { opacity: 0, y: 8, scale: 0.97 },
  transition: { duration: 0.2, ease: [0.25, 0.46, 0.45, 0.94] },
}

export const stepForward = {
  initial: { opacity: 0, x: 24 },
  animate: { opacity: 1, x: 0 },
  exit:    { opacity: 0, x: -16 },
  transition: { duration: 0.22, ease: [0.25, 0.46, 0.45, 0.94] },
}

export const stepBackward = {
  initial: { opacity: 0, x: -24 },
  animate: { opacity: 1, x: 0 },
  exit:    { opacity: 0, x: 16 },
  transition: { duration: 0.22, ease: [0.25, 0.46, 0.45, 0.94] },
}

export const staggerContainer = {
  animate: { transition: { staggerChildren: 0.05 } },
}

export const staggerItem = {
  initial: { opacity: 0, y: 10 },
  animate: { opacity: 1, y: 0 },
  transition: { duration: 0.2 },
}
