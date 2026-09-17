// Generates the narrative sections (Top Priorities, Medium/Long-term
// recommendations, Content Quality, E-E-A-T & Local SEO analysis, Content &
// Keyword Strategy, Next Steps) that build-docx-report.js's mechanical
// sections deliberately don't attempt — these require reasoning about the
// business and actually reading page copy, not just deriving facts from
// crawl data (title lengths, missing tags, etc.). See PHASE1-DESIGN.md §8
// and REQUIREMENTS.md's Phase 2+ candidates.
//
// On-demand only (called from the /docx route right before generating the
// Word doc, same lifecycle as buildDocxReport itself) — not run on every
// crawl, so the weekly cron job across all clients never pays for it.
// Never throws — a missing API key or a failed call degrades to `null`,
// and build-docx-report.js already renders fine without narrative content
// (that's the report Phase 1 shipped), so the docx download still succeeds.
const Anthropic = require('@anthropic-ai/sdk');

const MODEL = 'claude-opus-5';

// Every recommendation/finding is a short headline + one supporting
// sentence, never a single dense paragraph — same shape as the mechanical
// Quick Wins section already renders well (bold action line + lighter
// detail line below).
const REC_ITEM = {
  type: 'object',
  properties: {
    title: { type: 'string' },
    detail: { type: 'string' },
  },
  required: ['title', 'detail'],
  additionalProperties: false,
};

// Same title/detail shape as REC_ITEM, plus which page it's about — these
// come from actually reading a page's copy (contentSample below), not from
// crawl-derived facts, so citing the specific page matters more here than
// for the site-wide REC_ITEM findings.
const CONTENT_QUALITY_ITEM = {
  type: 'object',
  properties: {
    page: { type: 'string', description: 'The page URL this finding is about.' },
    title: { type: 'string' },
    detail: { type: 'string' },
  },
  required: ['page', 'title', 'detail'],
  additionalProperties: false,
};

const OUTPUT_SCHEMA = {
  type: 'object',
  properties: {
    summary: {
      type: 'string',
      description: 'A 2-3 sentence overview, roughly 40-60 words total. Not a deep dive — every specific finding belongs in the structured sections below, not crammed in here.',
    },
    top_priorities: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          priority: { type: 'string' },
          impact: { type: 'string', enum: ['High', 'Medium', 'Low'] },
          effort: { type: 'string', enum: ['High', 'Medium', 'Low'] },
        },
        required: ['priority', 'impact', 'effort'],
        additionalProperties: false,
      },
    },
    medium_term: { type: 'array', items: REC_ITEM },
    long_term: { type: 'array', items: REC_ITEM },
    content_quality_findings: {
      type: 'array',
      items: CONTENT_QUALITY_ITEM,
      description: 'Findings from actually reading each page\'s contentSample — thin/generic copy, missing credentials or expertise signals, tone mismatches for the audience. Empty array if the copy genuinely has no such issues; do not invent findings to fill this out.',
    },
    eeat_signals_present: { type: 'array', items: REC_ITEM },
    eeat_gaps: { type: 'array', items: REC_ITEM },
    local_seo: {
      type: 'object',
      properties: {
        google_business_profile: { type: 'string' },
        nap_consistency: { type: 'string' },
        geographic_targeting: { type: 'string' },
        local_citations: { type: 'string' },
      },
      required: ['google_business_profile', 'nap_consistency', 'geographic_targeting', 'local_citations'],
      additionalProperties: false,
    },
    content_keyword_strategy: {
      type: 'object',
      properties: {
        keyword_opportunities: {
          type: 'array',
          items: {
            type: 'object',
            properties: {
              keyword: { type: 'string' },
              intent: { type: 'string' },
              priority_page: { type: 'string' },
            },
            required: ['keyword', 'intent', 'priority_page'],
            additionalProperties: false,
          },
        },
        content_gaps: { type: 'array', items: REC_ITEM },
        recommended_content: { type: 'array', items: REC_ITEM },
      },
      required: ['keyword_opportunities', 'content_gaps', 'recommended_content'],
      additionalProperties: false,
    },
    next_steps: { type: 'array', items: REC_ITEM },
  },
  required: [
    'summary', 'top_priorities', 'medium_term', 'long_term', 'content_quality_findings',
    'eeat_signals_present', 'eeat_gaps', 'local_seo', 'content_keyword_strategy', 'next_steps',
  ],
  additionalProperties: false,
};

const SYSTEM_PROMPT = `You are an expert SEO strategist and auditor. You write structured,
professional, and constructive assessments for a client-facing report.
Prioritize E-E-A-T signals, especially for therapy/health/wellness/
professional-services businesses. Be specific — cite exact page URLs,
titles, or missing elements from the crawl data provided — but every
individual finding or recommendation must be split into:
- "title": a short, scannable headline in imperative voice (5-10 words,
  e.g. "Add H1 tags to 4 service pages")
- "detail": ONE supporting sentence (under 25 words) with the specific
  fact or reasoning behind it

Never combine multiple ideas, multiple pages, or multiple reasons into one
sentence. Never write a dense paragraph inside a list item — this is a
scannable business report meant to be read in a few minutes, not an essay.
The "summary" field is a short snapshot ONLY — 2-3 sentences, about
40-60 words total. Do not list individual findings, page names, or
specific fixes in the summary; those belong in the structured sections
below. If the business context doesn't mention a physical location or
local service area, say local SEO signals are "not applicable" rather
than guessing. core_web_vitals is homepage-only lab/field data — only
raise it as a top priority or in next_steps when a rating is genuinely
"poor" (a "good" or "needs-improvement" rating is not worth mentioning
on its own).

For content_quality_findings: actually read each page's "contentSample"
(the real body copy, not just its structural facts) and judge writing
quality the way a skeptical potential client would — not just whether SEO
tags are present. Flag only genuine problems:
- Thin or generic copy: stock phrasing that could describe any business in
  the industry, not this one specifically.
- Missing credentials or expertise signals: a page that should establish
  trust (About, service pages) but doesn't name a license, certification,
  years of experience, or specific qualification, especially for
  therapy/health/wellness/professional-services businesses.
- Tone mismatches: copy that reads inconsistently with the business type or
  audience (e.g. overly casual/salesy copy for a clinical/therapeutic
  service, or a jarring shift in voice between pages).
Cite the specific page for every finding. If a page's copy is genuinely
fine, say nothing about it — an empty content_quality_findings array is the
correct output for a well-written site, not a sign you didn't look hard
enough.`;

// Keeps the prompt to a manageable size for large sites — the pages with
// the most issues are the most useful signal for prioritization anyway.
const MAX_PAGES_IN_PROMPT = 30;
// Per-page cap on how much real body copy goes into the prompt — enough
// for a genuine content-quality read (thin/generic copy, missing
// credentials, tone) without the cost/size of sending full-length pages
// (blog posts, long service pages) for up to 30 pages at once.
const MAX_CONTENT_SAMPLE_CHARS = 1500;

function summarizePages(pages) {
  return pages
    .filter(p => !p.error)
    .sort((a, b) => (b.issues.length + b.warnings.length) - (a.issues.length + a.warnings.length))
    .slice(0, MAX_PAGES_IN_PROMPT)
    .map(p => ({
      url: p.url,
      title: p.title || null,
      h1: p.h1 || null,
      metaDesc: p.metaDesc || null,
      wordCount: p.wordCount,
      schemaTypes: p.schemaTypes,
      issues: p.issues,
      warnings: p.warnings,
      contentSample: (p.bodyText || '').slice(0, MAX_CONTENT_SAMPLE_CHARS),
    }));
}

async function generateNarrative({ client, results, scoreData, pageSpeed }) {
  if (!process.env.ANTHROPIC_API_KEY) {
    console.warn('Narrative report skipped: ANTHROPIC_API_KEY is not set.');
    return null;
  }

  const userContent = JSON.stringify({
    client_name: client.name,
    client_url: client.url,
    business_notes: client.business_notes || '(none provided)',
    seo_health_score: scoreData.score,
    score_deductions: scoreData.deductions,
    // Homepage-only (see page-speed.js) — only worth mentioning in the
    // narrative when it's genuinely bad; a good/borderline score isn't a
    // priority-worthy finding.
    core_web_vitals: pageSpeed ? {
      performance_score: pageSpeed.performanceScore,
      lcp_rating: pageSpeed.lcp.rating,
      cls_rating: pageSpeed.cls.rating,
      inp_rating: pageSpeed.inp ? pageSpeed.inp.rating : 'no field data',
    } : '(not measured)',
    pages: summarizePages(results),
  });

  try {
    // Streaming (not .create()) — a non-streaming request holding a
    // connection open while Claude thinks through a json_schema response
    // can get cut by an idle-connection timeout on the network path
    // (that's the "Request timed out" seen in practice); streaming keeps
    // data flowing so that doesn't happen. Capped timeout + low retry
    // count still bounds the worst case so a genuinely stuck request
    // fails fast into the graceful-degradation path below rather than
    // holding the /docx download open indefinitely. `effort: 'high'`
    // (raised from 'medium') because content_quality_findings requires
    // actually reading and judging page copy for thin/generic writing,
    // missing credentials, and tone — a real judgment call, not just
    // summarizing/reformatting facts already handed to it in the prompt.
    const anthropic = new Anthropic({ timeout: 120000, maxRetries: 1 });
    const stream = anthropic.messages.stream({
      model: MODEL,
      max_tokens: 16000,
      system: SYSTEM_PROMPT,
      output_config: {
        effort: 'high',
        format: { type: 'json_schema', schema: OUTPUT_SCHEMA },
      },
      messages: [{
        role: 'user',
        content: `Here is crawl data and business context for an SEO audit. Write the narrative sections of the report as structured JSON.\n\n${userContent}`,
      }],
    });
    const response = await stream.finalMessage();

    const textBlock = response.content.find(b => b.type === 'text');
    if (!textBlock) return null;
    return JSON.parse(textBlock.text);
  } catch (err) {
    console.warn('Narrative report generation failed (continuing without it):', err.message);
    return null;
  }
}

module.exports = { generateNarrative };
