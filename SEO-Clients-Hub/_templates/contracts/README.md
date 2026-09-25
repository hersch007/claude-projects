# Parts of Practice — Services Agreement Templates

One contract body, three pricing tiers (from the SEO pricing sheet):

| File | Plan | Monthly | 12-mo total / liability cap |
|---|---|---|---|
| `output/TEMPLATE-Spark-SEO-55-Agreement` | Spark SEO – The Foundation | $55 | $660 |
| `output/TEMPLATE-Website-SEO-Support-99-Agreement` | Website + SEO Support | $99 | $1,188 |
| `output/TEMPLATE-Growth-SEO-149-Agreement` | Growth SEO | $149 | $1,788 |

Yellow `[brackets]` in the templates are fill-ins. Signature blocks (Signature / Printed Name / Title / Date) are left blank for the e-signature program. The Effective Date is the date of the later signature, so no date needs to be typed in.

Exhibit A in every contract lists add-on rates (extra page $75, extra blog post $50); change them in `RATES` in `tiers.js`.

`build-welcome.js` makes the Welcome & Getting Started Checklist (access steps for Squarespace / WordPress / Wix, Google Business Profile, Analytics, Search Console, and practice info). Set `welcomeTier`, `contactFirstName`, `website` and `platform` on the client in `clients.js`.

## New client contract

1. Add the client to `clients.js` (legal name, address, phone, email, service area, specialties, optional platform note, and which tiers to generate).
2. `npm install` (first time only), then `node build-contracts.js && node build-welcome.js`. The .docx files are written to `../../clients/<folder>/`.
3. PDF: `soffice --headless --convert-to pdf --outdir <dir> <file>.docx`

Plan scope lives in `tiers.js`; the legal terms live in `build-contracts.js`.
