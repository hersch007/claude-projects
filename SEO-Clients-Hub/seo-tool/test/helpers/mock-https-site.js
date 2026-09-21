// An in-process HTTPS mock server, for the one check that specifically
// only runs on an https:// page (mixed-content resource detection in
// analyzePage() — see lib/audit-engine.js). Uses a static, pre-generated
// self-signed cert/key for 127.0.0.1 (100-year validity; not a secret,
// just a fixture) rather than shelling out to openssl at test time, so
// this has no external tool dependency and nothing to regenerate.
//
// node-fetch (used by audit-engine.js) defers to Node's default TLS
// verification, which honors process.env.NODE_TLS_REJECT_UNAUTHORIZED —
// tests using this helper must set it to '0' before calling runAudit()
// and restore it afterward (see mixed-content.test.js), since this cert
// is self-signed and would otherwise fail verification.
const https = require('https');

const CERT = `-----BEGIN CERTIFICATE-----
MIIDHDCCAgSgAwIBAgIUPf9x5Fcfek917R+aT6dHW3DpPLkwDQYJKoZIhvcNAQEL
BQAwFDESMBAGA1UEAwwJMTI3LjAuMC4xMCAXDTI2MDkyMTAxNTMzOVoYDzIxMjYw
ODI4MDE1MzM5WjAUMRIwEAYDVQQDDAkxMjcuMC4wLjEwggEiMA0GCSqGSIb3DQEB
AQUAA4IBDwAwggEKAoIBAQDQT/SZr2kPUz6nwDqX9hzRRpltokFXnJOY/r5gO1dw
nYXwyatuzVUChXh0+Zm9mb0NsVZGU3oWSfKiCpkeFAm+rUw3wfyqx8bueADEYAEJ
Rxzne7MivXkdZu9tr0pnsz1YGZkcRGRuvDhKebDXPNKaTHiInOG4cRtrsyR3tKvA
wRh5qRJ3WuusWy0EXZiBQWD/f4iLlFfNNPx/S0VxUdwiTPKghKjb0InkBrGN1ykl
h7O8H4H3QVR2ZiUwhHucB6Xg4cctJqALge72m4w2Vu87u5RzaxWAvKFQ4y6cdxy2
y/F+0u8pDIqT/108cKfGZfD+88dhe0139apWr7xp5gxBAgMBAAGjZDBiMB0GA1Ud
DgQWBBSYSKW14FG/+MDuJiVyOclzkDpuGzAfBgNVHSMEGDAWgBSYSKW14FG/+MDu
JiVyOclzkDpuGzAPBgNVHRMBAf8EBTADAQH/MA8GA1UdEQQIMAaHBH8AAAEwDQYJ
KoZIhvcNAQELBQADggEBAA4m7c5LHu+Mpu8taEFdionQIEZ5xTfGuhRvNdwMujds
OTe3V/v/uN4OBe2uUkxamLsJEfnxqHBH/1beM0IbzFmQ/0nr7lKe4vG5ds8UHlBc
XMxivjr52DHxmiSvS5dYouKiFeRsmTalLSmFDYOEc4wTlqthf6IAI6sdo2PNnl26
9SOhnYBLNn+mT/9nnHsss5jqNF8cabIc6JvZlBU0bOshQ20e1cAryzxkfyfZVq/W
eYYPPGpeRUZVv8cJzC5YLNS0KpbDCCwRFaCblMKZ8x3YkheEUPXZ7TRqvI8Cx3b3
lbP297LRXuNUA53VK/FIvylsz8QABysMWBMy3OC8ebI=
-----END CERTIFICATE-----`;

const KEY = `-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDQT/SZr2kPUz6n
wDqX9hzRRpltokFXnJOY/r5gO1dwnYXwyatuzVUChXh0+Zm9mb0NsVZGU3oWSfKi
CpkeFAm+rUw3wfyqx8bueADEYAEJRxzne7MivXkdZu9tr0pnsz1YGZkcRGRuvDhK
ebDXPNKaTHiInOG4cRtrsyR3tKvAwRh5qRJ3WuusWy0EXZiBQWD/f4iLlFfNNPx/
S0VxUdwiTPKghKjb0InkBrGN1yklh7O8H4H3QVR2ZiUwhHucB6Xg4cctJqALge72
m4w2Vu87u5RzaxWAvKFQ4y6cdxy2y/F+0u8pDIqT/108cKfGZfD+88dhe0139apW
r7xp5gxBAgMBAAECggEAEmnO5DrWHY2tLyTVyRLvLyx/aWp7PiRQA6kY6Oa17vVt
noAlEDFEP2nO4QAjL9hEFs7DIopEc23r7ZjkOf1pcxpcb11NWUc5dWDUKIeX79sC
Wg/cIMkAyLGNnnNtL0dvt7bjitUcz5EBpMum7w+oSg1SZjj/1s7grp9yLMaim/IM
pfyaYWzoPTZeSxB5IEzewIdrZMPWYFYtU5Z9wni9RmaCAyrwKwMNGQAqe0/ygD8U
DdtDjiA0S4qQ1Biuq2PiDlrZgusv5urmBAGT/4tkGqcNdp7fB4nrqEs1it6ZsGLm
+MpJtMG0wIeFcljF7EUWMpus45nWNfMX0W/GK2kJMQKBgQD5kMjIkRKk0oRT5xYI
wNvGz44NoYFnuyFY6SMs1ASFPcePOQTTyaPa/dwjFTSd2VMntaeyuBAfrcQoZiWI
LSsbeI3fGd2nar9NUlrFFfhyvdRuK3jtP6PwE47NmquQ9G7DUbjlM8kjqLvknL61
v0W8Q63wlU5OthM/0v4/Ao/jrQKBgQDVruLV5W4TOvHe7bIkdxVv4Ov1q2aKmYrt
FkZwsHWFPj0pWe9P4XdRQWjYSy/25RTZOvQDVMSnYgbcGpmDwi6KOGJoPYBDLIvL
AkQMPHUgtGJC4yGAZdJUyC84qCs9UgDv9+2a0Kea90WBSodgl6zNSxOo9YG31v6T
gCHAyYU9ZQKBgGjdb6DnZKAhXT0sMtQGxdKqUBRmMsv1k7Oacw9ZH3UlWn9SBDdB
2TohxahwNqXFNe3PpOGx+gR1raEUGt03rY9jfqmqYrsAXdYNrtp1uunr3iFU3wFB
5o7wiObYARNtwkUMR9b3haMYOat8OZ6A+rp67dHTyw3D8B63d+HeH7wZAoGARitM
3/KcaAI2RP+HPURBrOCOe7kSTjdHkL182iqIHP4oNXkMBg5DEVLKbCSclpX5d7BN
Sv6+KT0ehY7SlJrij48eeZ6gjO6G5V5UHDSKPfgeQFq3uKM3I5ItN4y5zkQsfKDM
zMbyEwhaMa/YrtZ/71ZVGWmtdEJMjQFJlDZY46UCgYEAqrQ3UK9FmmGqRuBpkpzK
x7gOXyynyBriVTKfQQuJz7VleydsWYSHbqsvkkgiBgD9xbKsaSgwAn5Yko5p4wJ+
tXSxyfXBWVlcNwrknKBVSR8PFG0gUfzNukmdx05oPv1G9aU+t0/PkCNncfXxqhBr
+xij+AaIJtPgAxfpGaVdhJg=
-----END PRIVATE KEY-----`;

function createMockHttpsSite(routes) {
  const server = https.createServer({ cert: CERT, key: KEY }, (req, res) => {
    const [urlPath, qs] = req.url.split('?');
    const handler = routes[urlPath];
    if (!handler) {
      res.writeHead(404, { 'content-type': 'text/html' });
      res.end('Not found');
      return;
    }
    handler(req, res, new URLSearchParams(qs || ''));
  });
  return new Promise((resolve, reject) => {
    server.on('error', reject);
    server.listen(0, '127.0.0.1', () => {
      const { port } = server.address();
      resolve({
        url: `https://127.0.0.1:${port}`,
        close: () => new Promise(r => server.close(r)),
      });
    });
  });
}

module.exports = { createMockHttpsSite };
