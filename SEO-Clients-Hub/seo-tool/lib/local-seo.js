// Google Business Profile signals (star rating, review count, and the phone/
// address Google itself has on file) via the Places API — same graceful-
// degradation pattern as page-speed.js and competitor-analysis.js: no API
// key configured, no place_id set on the client, or the call failing all
// degrade to null rather than blocking the rest of the Full Strategy Report.
// Unscored (see audit-engine.js's calcScore doc comments on why LLM/
// external-API-dependent signals never affect the numeric score) — this is
// purely a Full Strategy Report section, same lifecycle as Core Web Vitals
// and competitor analysis.
const fetch = require('node-fetch');
const { normalizePhone } = require('./audit-engine');

const TIMEOUT_MS = 10000;
const FIELDS = 'name,rating,user_ratings_total,formatted_phone_number,formatted_address,opening_hours,url';

async function getGoogleBusinessProfile(placeId) {
  const apiKey = process.env.GOOGLE_PLACES_API_KEY;
  if (!apiKey || !placeId) return null;
  try {
    const url = `https://maps.googleapis.com/maps/api/place/details/json?place_id=${encodeURIComponent(placeId)}&fields=${FIELDS}&key=${apiKey}`;
    const res = await fetch(url, { timeout: TIMEOUT_MS });
    const data = await res.json();
    if (data.status !== 'OK' || !data.result) {
      console.warn(`Google Business Profile lookup failed for place_id ${placeId}: ${data.status}${data.error_message ? ' — ' + data.error_message : ''}`);
      return null;
    }
    const r = data.result;
    return {
      name: r.name || null,
      rating: typeof r.rating === 'number' ? r.rating : null,
      reviewCount: typeof r.user_ratings_total === 'number' ? r.user_ratings_total : null,
      phone: r.formatted_phone_number || null,
      phoneDigits: normalizePhone(r.formatted_phone_number),
      address: r.formatted_address || null,
      hasHours: !!(r.opening_hours && r.opening_hours.weekday_text && r.opening_hours.weekday_text.length),
      mapsUrl: r.url || null,
    };
  } catch (err) {
    console.warn(`Google Business Profile lookup failed: ${err.message}`);
    return null;
  }
}

module.exports = { getGoogleBusinessProfile };
