Test

Create quote

Expected

Success


Test

Delete customer

Expected

Cannot delete if quotes exist


Test

Margin

Excel = Plugin


# TESTING.md

## Core Navigation Tests
- Portal loads at `/fruth/portal/`
- Quote Builder loads at `/fruth/quotes/`
- Editor loads at `/fruth/editor/`
- Portal button routes to `/fruth/portal/`
- Quote Builder button routes to `/fruth/quotes/`
- Editor button routes to `/fruth/editor/`
- No full domains are hardcoded

## User / Access Tests
- Add new user
- Edit existing user
- Delete user
- Reset user login
- Invalid login is rejected
- Correct login redirects to correct page
- Logged-out users cannot access protected editor
- Sales users cannot edit pricing tables unless allowed
- Admin users can access editor/settings

## Pricing Editor Tests
- Add pricing row
- Edit pricing row
- Delete pricing row
- Save table changes
- Refresh page and confirm changes persist
- Invalid values are rejected
- Blank required fields are rejected
- Decimal values save correctly
- Special characters do not break the editor

## Quote Calculation Tests
- Validate plugin output against Excel workbook
- Test small quantity
- Test large quantity
- Test minimum order
- Test quantity breaks
- Test rounding behavior
- Test margin-driven output
- Test target-price output
- Test roll calculations
- Test waste calculations
- Test setup charges
- Test freight logic
- Test edge cases with zero/blank values

## Quote Builder UI Tests
- Required fields validate correctly
- Product selections update calculations
- Margin fields update output
- Target price fields update output
- Reset / clear form works
- Browser refresh does not corrupt data
- Mobile/tablet layout remains usable
- Button spacing and layout remain consistent

## Print Module Tests
- Print button opens correct print layout
- Customer information appears correctly
- Quote totals match Quote Builder
- Margins/internal values do not appear on customer printout unless intended
- Logo and company information display correctly
- Terms and conditions display correctly
- Print layout works in Chrome, Edge, and Firefox
- Print layout works on letter-size paper
- Page breaks do not cut off important sections

## PDF Tests
- Generate PDF from quote
- PDF totals match Quote Builder
- PDF customer info is correct
- PDF quote number is correct
- PDF version/revision is correct
- PDF branding is correct
- PDF page breaks are clean
- PDF downloads successfully
- PDF can be emailed or attached later

## Saved Quote Tests
- Save new quote
- Reopen saved quote
- Edit saved quote
- Duplicate saved quote
- Delete saved quote
- Saved quote retains original pricing
- Saved quote does not change when pricing tables are updated later
- Saved quote stores customer info
- Saved quote stores line items
- Saved quote stores calculated totals

## Quote Number Tests
- Generate unique quote number
- Quote number never duplicates
- Quote number format is consistent
- Quote number persists after save
- Deleted quote numbers are not reused
- Quote number appears on print/PDF/email

## Quote Version Tests
- Create first quote version
- Create revision/version
- Previous version remains unchanged
- Latest version is clearly marked
- Print/PDF uses selected version
- Version history shows date/user/changes
- Quote can be duplicated into a new quote number

## Customer Tests
- Add customer
- Edit customer
- Delete customer only if no quotes are attached
- Search customer
- Select customer during quote creation
- Customer info auto-fills quote
- Customer quote history is visible

## Email Quote Tests
- Email quote to customer
- Email includes correct subject
- Email includes quote number
- Email attaches PDF
- Email body uses approved template
- Email send failure is reported
- Sent email is logged to quote history

## Security Tests
- Nonces validate on all save/delete actions
- Users cannot access editor without permission
- AJAX endpoints reject unauthenticated requests
- Inputs are sanitized
- Outputs are escaped
- SQL queries use prepared statements
- File/PDF generation cannot expose protected data

## Database Tests
- Plugin activation creates required tables
- Plugin update preserves existing data
- Plugin deactivation does not delete data
- Database migrations run only once
- Schema version is tracked
- Backup/rollback plan exists before destructive changes

## Browser / Device Tests
- Chrome desktop
- Edge desktop
- Firefox desktop
- iPad/tablet
- Large monitor
- Print preview
- Mobile usability where practical

## Regression Tests
- Known Excel examples always match plugin output
- Saved quotes remain unchanged after plugin updates
- Navigation paths remain unchanged
- Print totals match quote totals
- PDF totals match quote totals
- Version history remains intact