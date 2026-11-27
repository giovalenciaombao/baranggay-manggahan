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

const templates = {
  'Certificate of Indigency': `Applicant name: __________________________________________________
Complete residential address: ________________________________________________

I am requesting a Certificate of Indigency for ____________________________________________.`,
  'Certificate of Residency': `This is to certify that ______________________________ is a resident of __________________________________________________________, Barangay Manggahan, Rodriguez (Montalban), Rizal.

This certification is requested as proof of residency for ____________________________________________.`,
  'Barangay Clearance': `First name: ___________________  Middle name: ___________________  Surname: ___________________
Age: ______  Birthday: ________________  Place of birth: __________________________
Address: ________________________________________________________________

Community tax certificate number (if available): ___________________
Issued at: _________________________  Issued on: ________________

This certification is requested for ____________________________________________.

Signature and thumbmark: For completion at the barangay office.`,
  'Certificate of Good Moral Character': `Name: __________________________________________________
Civil status: ______________________
Residential address: ____________________________________________________

This certification is requested by _________________________________ for ____________________________________________.`,
  'Certification for Business Closure': `Business name: __________________________________________________
Owner: _________________________________________________________
Business address: ____________________________________________________
Month and year the business closed: _________________________`,
  'Certificate of Low Income': `This is to certify that ______________________________ is a resident of __________________________________________________________, Barangay Manggahan, Rodriguez (Montalban), Rizal.

This certification is requested by __________________________________ in connection with a low-income application.`,
  'Business Permit': `PANGALAN: ___________________________________________________
HANAPBUHAY: _________________________________________________
LUGAR: ______________________________________________________
URI (CLASS): __________________________________________________

Para sa tanggapan ng barangay:
BILANG NG RESIBO: ____________________
BAYAD SA CLEARANCE: ____________________
NUMERO NG STICKER: ____________________
PETSA NG PAGKUHA: ____________________
PETSA NG PAGKAWALANG BISA: ____________________`,
}

export function appointmentTemplate(purpose) {
  if (!Object.hasOwn(templates, purpose)) return null

  const title = purpose === 'Business Permit'
    ? 'Barangay Clearance para sa Paghahanapbuhay'
    : purpose
  const filename = title.toLowerCase().replaceAll(' ', '-') + '.rtf'
  const contents = [
    '{\\rtf1\\ansi\\uc1\\deff0{\\fonttbl{\\f0 Calibri;}}',
    '\\f0\\fs24\\qc Barangay Manggahan, Rodriguez, Rizal\\par',
    '\\b\\fs32 ' + rtfText(title) + '\\b0\\fs24\\par',
    'Applicant draft for barangay staff review\\par\\par\\ql',
    rtfText(templates[purpose]) + '\\par\\par',
    'Issuance and official signature: For barangay staff.\\par',
    '}',
  ].join('\n')

  return { filename, contents }
}

export function downloadAppointmentFile(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.append(link)
  link.click()
  link.remove()
  window.setTimeout(() => URL.revokeObjectURL(url), 1000)
}
