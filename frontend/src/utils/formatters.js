import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
dayjs.extend(relativeTime)

export const formatDate    = (d) => dayjs(d).format('MMM D, YYYY')
export const formatTime    = (d) => dayjs(d).format('h:mm A')
export const formatDateTime= (d) => dayjs(d).format('MMM D, YYYY h:mm A')
export const fromNow       = (d) => dayjs(d).fromNow()

export const STATUS_COLOR = {
  pending:   'yellow',
  approved:  'green',
  rejected:  'red',
  resolved:  'green',
  open:      'blue',
  closed:    'gray',
  confirmed: 'green',
  cancelled: 'red',
  active:    'green',
  inactive:  'gray',
}

export const getStatusColor = (status) => STATUS_COLOR[status?.toLowerCase()] || 'gray'

export function downloadBlob(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const a   = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  window.URL.revokeObjectURL(url)
}
