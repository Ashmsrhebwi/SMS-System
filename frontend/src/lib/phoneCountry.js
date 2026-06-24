// Phone country detection from E.164 normalized numbers
// Ordered longest-prefix first to avoid false matches

const PREFIXES = [
  // UK
  ['+44',  { code: 'GB', name: 'United Kingdom', flag: '🇬🇧' }],
  // US / Canada
  ['+1',   { code: 'US', name: 'United States',  flag: '🇺🇸' }],
  // Gulf
  ['+966', { code: 'SA', name: 'Saudi Arabia',   flag: '🇸🇦' }],
  ['+971', { code: 'AE', name: 'UAE',            flag: '🇦🇪' }],
  ['+974', { code: 'QA', name: 'Qatar',          flag: '🇶🇦' }],
  ['+965', { code: 'KW', name: 'Kuwait',         flag: '🇰🇼' }],
  ['+973', { code: 'BH', name: 'Bahrain',        flag: '🇧🇭' }],
  ['+968', { code: 'OM', name: 'Oman',           flag: '🇴🇲' }],
  // Levant
  ['+963', { code: 'SY', name: 'Syria',          flag: '🇸🇾' }],
  ['+962', { code: 'JO', name: 'Jordan',         flag: '🇯🇴' }],
  ['+961', { code: 'LB', name: 'Lebanon',        flag: '🇱🇧' }],
  ['+970', { code: 'PS', name: 'Palestine',      flag: '🇵🇸' }],
  ['+972', { code: 'IL', name: 'Israel',         flag: '🇮🇱' }],
  ['+964', { code: 'IQ', name: 'Iraq',           flag: '🇮🇶' }],
  // North Africa
  ['+20',  { code: 'EG', name: 'Egypt',          flag: '🇪🇬' }],
  ['+212', { code: 'MA', name: 'Morocco',        flag: '🇲🇦' }],
  ['+213', { code: 'DZ', name: 'Algeria',        flag: '🇩🇿' }],
  ['+216', { code: 'TN', name: 'Tunisia',        flag: '🇹🇳' }],
  ['+218', { code: 'LY', name: 'Libya',          flag: '🇱🇾' }],
  // Europe
  ['+33',  { code: 'FR', name: 'France',         flag: '🇫🇷' }],
  ['+49',  { code: 'DE', name: 'Germany',        flag: '🇩🇪' }],
  ['+34',  { code: 'ES', name: 'Spain',          flag: '🇪🇸' }],
  ['+39',  { code: 'IT', name: 'Italy',          flag: '🇮🇹' }],
  ['+31',  { code: 'NL', name: 'Netherlands',    flag: '🇳🇱' }],
  ['+32',  { code: 'BE', name: 'Belgium',        flag: '🇧🇪' }],
  ['+41',  { code: 'CH', name: 'Switzerland',    flag: '🇨🇭' }],
  ['+43',  { code: 'AT', name: 'Austria',        flag: '🇦🇹' }],
  ['+46',  { code: 'SE', name: 'Sweden',         flag: '🇸🇪' }],
  ['+47',  { code: 'NO', name: 'Norway',         flag: '🇳🇴' }],
  ['+45',  { code: 'DK', name: 'Denmark',        flag: '🇩🇰' }],
  ['+358', { code: 'FI', name: 'Finland',        flag: '🇫🇮' }],
  ['+353', { code: 'IE', name: 'Ireland',        flag: '🇮🇪' }],
  ['+48',  { code: 'PL', name: 'Poland',         flag: '🇵🇱' }],
  ['+351', { code: 'PT', name: 'Portugal',       flag: '🇵🇹' }],
  ['+30',  { code: 'GR', name: 'Greece',         flag: '🇬🇷' }],
  ['+90',  { code: 'TR', name: 'Turkey',         flag: '🇹🇷' }],
  ['+7',   { code: 'RU', name: 'Russia',         flag: '🇷🇺' }],
  // Asia
  ['+92',  { code: 'PK', name: 'Pakistan',       flag: '🇵🇰' }],
  ['+91',  { code: 'IN', name: 'India',          flag: '🇮🇳' }],
  ['+880', { code: 'BD', name: 'Bangladesh',     flag: '🇧🇩' }],
  ['+86',  { code: 'CN', name: 'China',          flag: '🇨🇳' }],
  ['+81',  { code: 'JP', name: 'Japan',          flag: '🇯🇵' }],
  ['+82',  { code: 'KR', name: 'South Korea',    flag: '🇰🇷' }],
  // Africa
  ['+234', { code: 'NG', name: 'Nigeria',        flag: '🇳🇬' }],
  ['+27',  { code: 'ZA', name: 'South Africa',   flag: '🇿🇦' }],
  ['+254', { code: 'KE', name: 'Kenya',          flag: '🇰🇪' }],
  ['+233', { code: 'GH', name: 'Ghana',          flag: '🇬🇭' }],
  // Americas
  ['+55',  { code: 'BR', name: 'Brazil',         flag: '🇧🇷' }],
  ['+52',  { code: 'MX', name: 'Mexico',         flag: '🇲🇽' }],
  ['+54',  { code: 'AR', name: 'Argentina',      flag: '🇦🇷' }],
  ['+57',  { code: 'CO', name: 'Colombia',       flag: '🇨🇴' }],
  ['+61',  { code: 'AU', name: 'Australia',      flag: '🇦🇺' }],
  ['+64',  { code: 'NZ', name: 'New Zealand',    flag: '🇳🇿' }],
]

// Sort by prefix length descending so longer prefixes match first
const SORTED = [...PREFIXES].sort((a, b) => b[0].length - a[0].length)

export function getPhoneCountry(phone) {
  if (!phone) return null
  const normalized = phone.startsWith('+') ? phone : '+' + phone
  for (const [prefix, country] of SORTED) {
    if (normalized.startsWith(prefix)) return country
  }
  return null
}

export function getCountryFlag(phone) {
  return getPhoneCountry(phone)?.flag ?? null
}

export function getCountryName(phone) {
  return getPhoneCountry(phone)?.name ?? null
}

// Group an array of contacts by country
export function groupByCountry(contacts) {
  const groups = {}
  for (const contact of contacts) {
    const country = getPhoneCountry(contact.phone)
    const key = country ? country.code : 'UNKNOWN'
    if (!groups[key]) {
      groups[key] = { country: country ?? { code: 'UNKNOWN', name: 'Unknown', flag: '🌐' }, contacts: [] }
    }
    groups[key].contacts.push(contact)
  }
  return Object.values(groups).sort((a, b) => b.contacts.length - a.contacts.length)
}
