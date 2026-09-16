// Generates the narrative sections (Top Priorities, Medium/Long-term
// recommendations, E-E-A-T & Local SEO analysis, Content & Keyword
// Strategy, Next Steps) that build-docx-report.js's mechanical sections
// deliberately don't attempt — these require reasoning about the business,
// not just deriving facts from crawl data. See PHASE1-DESIGN.md §8 and
// REQUIREMENTS.md's Phase 2+ candidates.
//
// On-demand only (called from the /docx route right before generating the
// Word doc, same lifecycle as buildDocxReport itself) — not run on every
// crawl, so the weekly cron job across all clients never pays for it.
// Never throws — a missing API key or a failed call degrades to `null`,
// and build-docx-report.js already renders fine without narrative content
// (that's the report Phase 1 shipped), so the docx download still succeeds.
const Anthropic = require('@anthropic-ai/sdk');

const MODEL = 'claude-opus-5';

const OUTPUT_SCHEMA = {
  type: 'object',
  properties: {
    summary: { type: 'string' },
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
    medium_term: { type: 'array', items: { type: 'string' } },
    long_term: { type: 'array', items: { type: 'string' } },
    eeat_signals_present: { type: 'array', items: { type: 'string' } },
    eeat_gaps: { type: 'array', items: { type: 'string' } },
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
        content_gaps: { type: 'array', items: { type: 'string' } },
        recommended_content: { type: 'array', items: { type: 'string' } },
      },
      required: ['keyword_opportunities', 'content_gaps', 'recommended_content'],
      additionalProperties: false,
    },
    next_steps: { type: 'array', items: { type: 'string' } },
  },
  required: [
    'summary', 'top_priorities', 'medium_term', 'long_term', 'eeat_signals_present',
    'eeat_gaps', 'local_seo', 'content_keyword_strategy', 'next_steps',
  ],
  additionalProperties: false,
};

const SYSTEM_PROMPT = `You are an expert SEO strategist and auditor. You write structured,
professional, and constructive assessments. Prioritize E-E-A-T signals,
especially for therapy/health/wellness/professional-services businesses.
Give clear, specific, actionable recommendations rather than generic advice
— cite exact page URLs, titles, or missing elements from the crawl data
provided. If the business context doesn't mention a physical location or
local service area, say local SEO signals are "not applicable" rather than
guessing.`;

// Keeps the prompt to a manageable size for large sites — the pages with
// the most issues are the most useful signal for prioritization anyway.
const MAX_PAGES_IN_PROMPT = 30;

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
    }));
}

async function generateNarrative({ client, results, scoreData }) {
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
    // holding the /docx download open indefinitely. `effort: 'medium'`
    // keeps normal calls quick — this is a writing/summarization task from
    // data already provided in the prompt, not one needing the deepest
    // reasoning tier.
    const anthropic = new Anthropic({ timeout: 90000, maxRetries: 1 });
    const stream = anthropic.messages.stream({
      model: MODEL,
      max_tokens: 16000,
      system: SYSTEM_PROMPT,
      output_config: {
        effort: 'medium',
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
