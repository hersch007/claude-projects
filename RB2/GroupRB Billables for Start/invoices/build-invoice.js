const fs = require('fs');
const logoB64 = fs.readFileSync('invoices/logo-b64.txt', 'utf8').trim();

const items = [
  { client: 'Garlock',                      hours: 41.00, rate: 40 },
  { client: 'Fruth',                        hours: 52.75, rate: 40 },
  { client: 'Wireless TS',                  hours: 20.00, rate: 40 },
  { client: 'Sport Medical',                hours: 12.50, rate: 40 },
  { client: 'TriCoGo',                      hours: 12.00, rate: 40 },
  { client: 'Automotive Service Products',  hours: 6.00,  rate: 40 },
  { client: 'Advernology',                  hours: 4.75,  rate: 40 },
  { client: 'ANS',                          hours: 3.50,  rate: 40 },
];

const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const totalHours = items.reduce((s, i) => s + i.hours, 0);
const totalAmount = items.reduce((s, i) => s + i.hours * i.rate, 0);

const rows = items.map(i => `
      <tr>
        <td>${i.client} — App Development, Marketing, SEO &amp; Web Services — July 2026</td>
        <td class="num">${fmt(i.hours)}</td>
        <td class="num">$${fmt(i.rate)}</td>
        <td class="num">$${fmt(i.hours * i.rate)}</td>
      </tr>`).join('');

const html = `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice GRB-2026-07</title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #2b2b33;
    margin: 0;
    padding: 32px 48px;
    font-size: 12.5px;
    line-height: 1.4;
  }
  .top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 3px solid #4a3f6b;
    padding-bottom: 14px;
    margin-bottom: 18px;
  }
  .logo { height: 48px; }
  .invoice-title {
    text-align: right;
  }
  .invoice-title h1 {
    margin: 0 0 6px 0;
    font-size: 28px;
    letter-spacing: 2px;
    color: #4a3f6b;
    font-weight: 700;
  }
  .invoice-title .meta { font-size: 13px; color: #555; }
  .invoice-title .meta strong { color: #2b2b33; }
  .addresses {
    display: flex;
    justify-content: space-between;
    gap: 40px;
    margin-bottom: 18px;
  }
  .addr-block h3 {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #a33;
    margin: 0 0 8px 0;
  }
  .addr-block.from h3 { color: #4a3f6b; }
  .addr-block p { margin: 0; }
  .addr-block .name { font-weight: 600; }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8px;
  }
  thead th {
    text-align: left;
    background: #4a3f6b;
    color: #fff;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px;
  }
  thead th.num, td.num { text-align: right; }
  tbody td {
    padding: 6px 12px;
    border-bottom: 1px solid #e3e0ea;
  }
  tbody tr:nth-child(even) { background: #f8f7fb; }
  tfoot td {
    padding: 7px 12px;
    font-weight: 700;
  }
  tfoot tr.grand td {
    border-top: 2px solid #4a3f6b;
    font-size: 15px;
    color: #4a3f6b;
  }
  .footer {
    margin-top: 18px;
    padding-top: 10px;
    border-top: 1px solid #e3e0ea;
    font-size: 11px;
    color: #666;
  }
</style>
</head>
<body>
  <div class="top">
    <img class="logo" src="data:image/png;base64,${logoB64}" alt="GroupRB Marketing">
    <div class="invoice-title">
      <h1>INVOICE</h1>
      <div class="meta"><strong>Invoice #:</strong> GRB-2026-07</div>
      <div class="meta"><strong>Invoice Date:</strong> 8/4/2026</div>
      <div class="meta"><strong>Period:</strong> July 1&ndash;31, 2026</div>
    </div>
  </div>

  <div class="addresses">
    <div class="addr-block from">
      <h3>From</h3>
      <p class="name">Richard Brashear &mdash; GroupRB Marketing</p>
      <p>1043 Woodland Dr</p>
      <p>Rock Hill, SC 29732</p>
      <p>803-415-5062</p>
      <p>Richard@GroupRB.com</p>
      <p>www.grouprb.com</p>
    </div>
    <div class="addr-block to">
      <h3>Bill To</h3>
      <p class="name">Start Advertising</p>
      <p>Attn: Suzann Schrader</p>
      <p>115 Dave Blvd.</p>
      <p>Rock Hill, SC 29730</p>
      <p>Tel: 803.328.2180 &nbsp;|&nbsp; Fax: 803.328.0113</p>
      <p>info@startadvertising.com</p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description</th>
        <th class="num">Hours</th>
        <th class="num">Rate</th>
        <th class="num">Amount</th>
      </tr>
    </thead>
    <tbody>${rows}
    </tbody>
    <tfoot>
      <tr>
        <td colspan="1"></td>
        <td class="num">${fmt(totalHours)}</td>
        <td class="num"></td>
        <td class="num"></td>
      </tr>
      <tr class="grand">
        <td colspan="3">Total Due</td>
        <td class="num">$${fmt(totalAmount)}</td>
      </tr>
    </tfoot>
  </table>

  <div class="footer">
    <p>Payment Terms: Due upon receipt.</p>
    <p>Thank you for your business.</p>
  </div>
</body>
</html>
`;

fs.writeFileSync('invoices/GroupRB-Invoice-2026-07.html', html);
console.log('Total hours:', totalHours, '| Total amount: $' + fmt(totalAmount));
