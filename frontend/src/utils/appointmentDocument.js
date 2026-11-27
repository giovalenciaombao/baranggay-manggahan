function rtfText(value) {
  return String(value).replace(/\r\n?/g, '\n').replace(/[\\{}\t\n\u0080-\uFFFF]/g, character => {
    if (character === '\n') return '\\par\n'
    if (character === '\t') return '\\tab '
    if ('\\{}'.includes(character)) return '\\' + character
    const code = character.charCodeAt(0)
    return '\\u' + (code > 32767 ? code - 65536 : code) + '?'
  })
}

export function buildAppointmentDocument({ id, title, resident, schedule, notes }) {
  return [
    '{\\rtf1\\ansi\\uc1\\deff0{\\fonttbl{\\f0 Calibri;}}',
    '\\f0\\fs24',
    '\\b\\fs32 ' + rtfText(title) + '\\b0\\fs24\\par',
    'Applicant draft for barangay staff review\\par\\par',
    'Appointment number: ' + rtfText(id) + '\\par',
    'Requested by: ' + rtfText(resident) + '\\par',
    'Scheduled visit: ' + rtfText(schedule) + '\\par\\par',
    '\\b Submitted information\\b0\\par',
    rtfText(notes) + '\\par',
    '}',
  ].join('\n')
}
