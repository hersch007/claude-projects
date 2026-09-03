<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$statuses = array(
    'new'         => array( 'label' => 'New',         'color' => '#3b82f6', 'bg' => '#dbeafe' ),
    'contacted'   => array( 'label' => 'Contacted',   'color' => '#f59e0b', 'bg' => '#fef3c7' ),
    'qualified'   => array( 'label' => 'Qualified',   'color' => '#8b5cf6', 'bg' => '#ede9fe' ),
    'unqualified' => array( 'label' => 'Unqualified', 'color' => '#ef4444', 'bg' => '#fee2e2' ),
    'closed'      => array( 'label' => 'Closed',      'color' => '#10b981', 'bg' => '#d1fae5' ),
);

// Load all leads with contact + company names
$leads = $wpdb->get_results(
    "SELECT l.*,
            CONCAT(COALESCE(c.first_name,''),' ',COALESCE(c.last_name,'')) AS contact_name,
            c.email AS contact_email,
            co.name AS company_name
     FROM {$wpdb->prefix}sp_leads l
     LEFT JOIN {$wpdb->prefix}sp_contacts c  ON c.id  = l.contact_id
     LEFT JOIN {$wpdb->prefix}sp_companies co ON co.id = l.company_id
     ORDER BY l.score DESC, l.created_at DESC"
);

// Group by status
$by_status = array();
foreach ( $statuses as $key => $_ ) $by_status[ $key ] = array();
foreach ( $leads as $lead ) {
    $s = isset( $by_status[ $lead->status ] ) ? $lead->status : 'new';
    $by_status[ $s ][] = $lead;
}

?>
<div class="sp-page-header">
    <h1>Pipeline</h1>
    <div class="sp-header-actions">
        <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=add' ) ); ?>" class="sp-btn sp-btn-primary">+ Add Lead</a>
    </div>
</div>

<div class="sp-pipeline-wrap">
<?php foreach ( $statuses as $key => $meta ) :
    $col_leads = $by_status[ $key ];
    $count     = count( $col_leads );
    $avg_score = $count ? round( array_sum( array_column( $col_leads, 'score' ) ) / $count ) : 0;
?>
<div class="sp-pipeline-col" data-status="<?php echo esc_attr( $key ); ?>">
    <div class="sp-pipeline-col-header" style="border-top:3px solid <?php echo esc_attr( $meta['color'] ); ?>">
        <span class="sp-pipeline-col-label"><?php echo esc_html( $meta['label'] ); ?></span>
        <span class="sp-pipeline-col-count" style="background:<?php echo esc_attr( $meta['bg'] ); ?>;color:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo $count; ?></span>
        <?php if ( $count ) : ?>
            <span class="sp-pipeline-avg">avg <?php echo $avg_score; ?></span>
        <?php endif; ?>
    </div>
    <div class="sp-pipeline-cards" id="col-<?php echo esc_attr( $key ); ?>">
        <?php if ( empty( $col_leads ) ) : ?>
            <div class="sp-pipeline-empty">No leads</div>
        <?php endif; ?>
        <?php foreach ( $col_leads as $lead ) :
            $name = trim( $lead->contact_name ) ?: $lead->company_name ?: 'Unnamed Lead';
            $sub  = trim( $lead->contact_name ) && $lead->company_name ? $lead->company_name : $lead->contact_email;
            $age  = human_time_diff( strtotime( $lead->created_at ), current_time( 'timestamp' ) );
        ?>
        <div class="sp-pipeline-card"
             draggable="true"
             data-id="<?php echo esc_attr( $lead->id ); ?>"
             data-status="<?php echo esc_attr( $lead->status ); ?>">
            <div class="sp-pipeline-card-name">
                <a href="<?php echo esc_url( home_url( '/sp-app/?view=leads&action=view&id=' . $lead->id ) ); ?>">
                    <?php echo esc_html( $name ); ?>
                </a>
            </div>
            <?php if ( $sub ) : ?>
                <div class="sp-pipeline-card-sub"><?php echo esc_html( $sub ); ?></div>
            <?php endif; ?>
            <div class="sp-pipeline-card-footer">
                <?php if ( $lead->source ) : ?>
                    <span class="sp-pipeline-source"><?php echo esc_html( $lead->source ); ?></span>
                <?php endif; ?>
                <span class="sp-pipeline-age"><?php echo esc_html( $age ); ?> ago</span>
                <?php if ( $lead->score ) : ?>
                    <span class="sp-pipeline-score"><?php echo esc_html( $lead->score ); ?></span>
                <?php endif; ?>
            </div>
            <div class="sp-pipeline-move">
                <select class="sp-pipeline-move-select" data-id="<?php echo esc_attr( $lead->id ); ?>">
                    <?php foreach ( $statuses as $s_key => $s_meta ) : ?>
                        <option value="<?php echo esc_attr( $s_key ); ?>" <?php selected( $lead->status, $s_key ); ?>>
                            Move to: <?php echo esc_html( $s_meta['label'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<div id="sp-pipeline-toast" class="sp-pipeline-toast" style="display:none"></div>

<style>
.sp-pipeline-wrap{display:flex;gap:14px;overflow-x:auto;padding-bottom:16px;align-items:flex-start;-webkit-overflow-scrolling:touch}
.sp-pipeline-col{flex:0 0 240px;min-width:240px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
.sp-pipeline-col-header{display:flex;align-items:center;gap:8px;padding:12px 14px;background:#fff;border-bottom:1px solid #e2e8f0}
.sp-pipeline-col-label{font-size:13px;font-weight:700;color:#1e293b;flex:1}
.sp-pipeline-col-count{font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
.sp-pipeline-avg{font-size:11px;color:#94a3b8;margin-left:auto}
.sp-pipeline-cards{padding:10px;display:flex;flex-direction:column;gap:8px;min-height:80px}
.sp-pipeline-empty{font-size:12px;color:#cbd5e1;text-align:center;padding:16px 0}
.sp-pipeline-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px;cursor:grab;transition:box-shadow .15s,border-color .15s;position:relative}
.sp-pipeline-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08);border-color:#cbd5e1}
.sp-pipeline-card.dragging{opacity:.5;box-shadow:0 8px 24px rgba(0,0,0,.15)}
.sp-pipeline-col.drag-over .sp-pipeline-cards{background:rgba(99,102,241,.04);border-radius:8px;outline:2px dashed #c7d2fe}
.sp-pipeline-card-name{font-size:13px;font-weight:600;color:#1e293b;margin-bottom:3px}
.sp-pipeline-card-name a{color:#1e293b;text-decoration:none}
.sp-pipeline-card-name a:hover{color:var(--sp-accent,#CC1F1F)}
.sp-pipeline-card-sub{font-size:11px;color:#94a3b8;margin-bottom:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sp-pipeline-card-footer{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.sp-pipeline-source{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;background:#f1f5f9;color:#64748b;padding:2px 6px;border-radius:4px}
.sp-pipeline-age{font-size:11px;color:#94a3b8;margin-left:auto}
.sp-pipeline-score{font-size:11px;font-weight:700;color:#fff;background:var(--sp-accent,#CC1F1F);padding:2px 6px;border-radius:4px}
.sp-pipeline-move{margin-top:10px;border-top:1px solid #f1f5f9;padding-top:8px}
.sp-pipeline-move-select{width:100%;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:5px 8px;font-size:11px;color:#64748b;cursor:pointer;outline:none}
.sp-pipeline-move-select:focus{border-color:var(--sp-accent,#CC1F1F)}
.sp-pipeline-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:500;z-index:999;box-shadow:0 4px 16px rgba(0,0,0,.2)}
@media(max-width:768px){
  .sp-pipeline-col{flex:0 0 220px;min-width:220px}
}
</style>

<script>
(function(){
    var ajaxUrl = <?php echo json_encode( home_url( '/sp-app/?sp_ajax=pipeline_move' ) ); ?>;
    var toast   = document.getElementById('sp-pipeline-toast');
    var dragged = null;

    function showToast(msg, ok) {
        toast.textContent = msg;
        toast.style.background = ok ? '#15803d' : '#dc2626';
        toast.style.display = 'block';
        clearTimeout(toast._t);
        toast._t = setTimeout(function(){ toast.style.display='none'; }, 2500);
    }

    function moveLead(id, newStatus, card, targetCol) {
        var fd = new FormData();
        fd.append('lead_id', id);
        fd.append('status', newStatus);
        fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    if (card && targetCol) {
                        var cards = document.getElementById('col-' + newStatus);
                        if (cards) {
                            // Remove empty placeholder if present
                            var empty = cards.querySelector('.sp-pipeline-empty');
                            if (empty) empty.remove();
                            cards.appendChild(card);
                            card.setAttribute('data-status', newStatus);
                        }
                        updateColCounts();
                    }
                    showToast('Moved to ' + newStatus, true);
                } else {
                    showToast('Move failed', false);
                }
            })
            .catch(function(){ showToast('Move failed', false); });
    }

    function updateColCounts() {
        document.querySelectorAll('.sp-pipeline-col').forEach(function(col) {
            var status = col.getAttribute('data-status');
            var count  = col.querySelectorAll('.sp-pipeline-card').length;
            var badge  = col.querySelector('.sp-pipeline-col-count');
            if (badge) badge.textContent = count;
            var cards = col.querySelector('.sp-pipeline-cards');
            if (cards && count === 0 && !cards.querySelector('.sp-pipeline-empty')) {
                var em = document.createElement('div');
                em.className = 'sp-pipeline-empty';
                em.textContent = 'No leads';
                cards.appendChild(em);
            }
        });
    }

    // Drag and drop
    document.querySelectorAll('.sp-pipeline-card').forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function() {
            card.classList.remove('dragging');
            document.querySelectorAll('.sp-pipeline-col').forEach(function(c){
                c.classList.remove('drag-over');
            });
            dragged = null;
        });
    });

    document.querySelectorAll('.sp-pipeline-col').forEach(function(col) {
        col.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            col.classList.add('drag-over');
        });
        col.addEventListener('dragleave', function(e) {
            if (!col.contains(e.relatedTarget)) col.classList.remove('drag-over');
        });
        col.addEventListener('drop', function(e) {
            e.preventDefault();
            col.classList.remove('drag-over');
            if (!dragged) return;
            var newStatus  = col.getAttribute('data-status');
            var oldStatus  = dragged.getAttribute('data-status');
            if (newStatus === oldStatus) return;
            moveLead(dragged.getAttribute('data-id'), newStatus, dragged, col);
        });
    });

    // Dropdown move
    document.querySelectorAll('.sp-pipeline-move-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var id        = sel.getAttribute('data-id');
            var newStatus = sel.value;
            var card      = sel.closest('.sp-pipeline-card');
            var oldStatus = card ? card.getAttribute('data-status') : '';
            if (newStatus === oldStatus) return;
            moveLead(id, newStatus, card, null);
        });
    });
})();
</script>
