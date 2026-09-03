<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$is_configured = function_exists( 'sp_ai_is_configured' ) && sp_ai_is_configured();
$model         = function_exists( 'sp_ai_get_model' ) ? sp_ai_get_model() : '';

$model_labels = array(
    'gpt-4o-mini'               => 'GPT-4o Mini',
    'gpt-4o'                    => 'GPT-4o',
    'claude-sonnet-4-6'         => 'Claude Sonnet 4.6',
    'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
);
$model_label = isset( $model_labels[ $model ] ) ? $model_labels[ $model ] : $model;

$tickets_active = function_exists( 'sp_tickets_get_statuses' );
?>

<div class="sp-page-header">
    <h1>AI Assistant <span class="sp-nav-addon" style="font-size:11px;padding:3px 8px;background:#6366f1;color:#fff;border-radius:4px;margin-left:4px">PREMIUM</span></h1>
</div>

<?php if ( ! $is_configured ) : ?>
<div class="sp-ai-banner">
    <div class="sp-ai-banner-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
            <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
    </div>
    <div>
        <div class="sp-ai-banner-title">Connect your AI provider</div>
        <div class="sp-ai-banner-sub">Add an API key in <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings#section-ai' ) ); ?>">Settings → AI</a> to unlock AI-powered features.</div>
    </div>
</div>
<?php else : ?>
<div class="sp-ai-banner" style="background:#f0fdf4;border-color:#86efac">
    <div class="sp-ai-banner-icon" style="color:#16a34a">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <div>
        <div class="sp-ai-banner-title" style="color:#15803d">AI connected</div>
        <div class="sp-ai-banner-sub">Using <strong><?php echo esc_html( $model_label ); ?></strong> &mdash; <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings#section-ai' ) ); ?>">change in Settings</a></div>
    </div>
</div>
<?php endif; ?>

<div class="sp-grid-2" style="margin-top:16px">

    <!-- Active Features -->
    <div class="sp-card sp-form-card">
        <div class="sp-section-heading">Active Features</div>
        <div class="sp-ai-feature-list">

            <div class="sp-ai-feature sp-ai-feature-ready">
                <span class="sp-ai-feature-dot" style="background:#22c55e"></span>
                <div>
                    <div class="sp-ai-feature-name">
                        Contact Summaries
                        <span style="font-size:10px;font-weight:600;background:#dcfce7;color:#15803d;border-radius:4px;padding:1px 6px;margin-left:6px;vertical-align:middle">LIVE</span>
                    </div>
                    <div class="sp-ai-feature-desc">Open any contact → Edit to generate a briefing before a call.</div>
                </div>
            </div>

            <div class="sp-ai-feature sp-ai-feature-ready">
                <span class="sp-ai-feature-dot" style="background:#22c55e"></span>
                <div>
                    <div class="sp-ai-feature-name">
                        Pipeline Insights
                        <span style="font-size:10px;font-weight:600;background:#dcfce7;color:#15803d;border-radius:4px;padding:1px 6px;margin-left:6px;vertical-align:middle">LIVE</span>
                    </div>
                    <div class="sp-ai-feature-desc">Dashboard → Pipeline Insights card — on-demand analysis of your lead pipeline.</div>
                </div>
            </div>

            <?php if ( $tickets_active ) : ?>
            <div class="sp-ai-feature sp-ai-feature-ready">
                <span class="sp-ai-feature-dot" style="background:#22c55e"></span>
                <div>
                    <div class="sp-ai-feature-name">
                        Ticket Triage
                        <span style="font-size:10px;font-weight:600;background:#dcfce7;color:#15803d;border-radius:4px;padding:1px 6px;margin-left:6px;vertical-align:middle">LIVE</span>
                    </div>
                    <div class="sp-ai-feature-desc">Open any ticket → Edit to get AI-suggested priority and next steps.</div>
                </div>
            </div>
            <div class="sp-ai-feature sp-ai-feature-ready">
                <span class="sp-ai-feature-dot" style="background:#22c55e"></span>
                <div>
                    <div class="sp-ai-feature-name">
                        Ticket Analysis
                        <span style="font-size:10px;font-weight:600;background:#dcfce7;color:#15803d;border-radius:4px;padding:1px 6px;margin-left:6px;vertical-align:middle">LIVE</span>
                    </div>
                    <div class="sp-ai-feature-desc">Tickets list → Analyze Tickets — queue health, aging issues, repeat contacts, team recommendations.</div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Coming Soon -->
    <div class="sp-card sp-form-card">
        <div class="sp-section-heading">Coming Soon</div>
        <div class="sp-ai-feature-list">

            <?php if ( ! $tickets_active ) : ?>
            <div class="sp-ai-feature">
                <span class="sp-ai-feature-dot"></span>
                <div>
                    <div class="sp-ai-feature-name">Ticket Triage</div>
                    <div class="sp-ai-feature-desc">Requires the Tickets add-on. Suggest priority and next steps automatically.</div>
                </div>
            </div>
            <?php endif; ?>

            <div class="sp-ai-feature">
                <span class="sp-ai-feature-dot"></span>
                <div>
                    <div class="sp-ai-feature-name">Lead Scoring</div>
                    <div class="sp-ai-feature-desc">Automatically score and prioritize leads based on engagement and profile data.</div>
                </div>
            </div>

            <div class="sp-ai-feature">
                <span class="sp-ai-feature-dot"></span>
                <div>
                    <div class="sp-ai-feature-name">Weekly Digest</div>
                    <div class="sp-ai-feature-desc">Scheduled email with AI-generated pipeline and ticket analysis every Monday.</div>
                </div>
            </div>

            <div class="sp-ai-feature">
                <span class="sp-ai-feature-dot"></span>
                <div>
                    <div class="sp-ai-feature-name">Quote Follow-up Drafts</div>
                    <div class="sp-ai-feature-desc">Auto-draft follow-up emails for quotes that have gone cold.</div>
                </div>
            </div>

        </div>

        <?php if ( ! $is_configured ) : ?>
            <p class="sp-hint" style="margin-top:16px">Configure your API key in <a href="<?php echo esc_url( home_url( '/sp-app/?view=settings#section-ai' ) ); ?>">Settings → AI</a> to enable live features.</p>
        <?php endif; ?>
    </div>

</div>
