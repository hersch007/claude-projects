<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<style>
.sp-pipeline-wrap{display:flex;gap:14px;overflow-x:auto;padding-bottom:16px;align-items:flex-start;-webkit-overflow-scrolling:touch}
.sp-pipeline-col{flex:0 0 240px;min-width:240px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
.sp-pipeline-col-header{display:flex;align-items:center;gap:8px;padding:12px 14px;background:#fff;border-bottom:1px solid #e2e8f0}
.sp-pipeline-col-label{font-size:13px;font-weight:700;color:#1e293b;flex:1}
.sp-pipeline-col-count{font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
.sp-pipeline-avg{font-size:11px;color:#94a3b8;margin-left:auto}
.sp-pipeline-cards{padding:10px;display:flex;flex-direction:column;gap:8px;min-height:80px}
.sp-pipeline-empty{font-size:12px;color:#cbd5e1;text-align:center;padding:16px 0}
.sp-pipeline-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px;cursor:grab;transition:box-shadow .15s,border-color .15s}
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
@media(max-width:768px){.sp-pipeline-col{flex:0 0 220px;min-width:220px}}
</style>
<script>
(function(){
    var ajaxUrl = <?php echo json_encode( home_url( '/sp-app/?sp_ajax=pipeline_move' ) ); ?>;
    var toast   = document.getElementById('sp-pipeline-toast');
    var dragged = null;

    function showToast(msg, ok) {
        if (!toast) return;
        toast.textContent = msg;
        toast.style.background = ok ? '#15803d' : '#dc2626';
        toast.style.display = 'block';
        clearTimeout(toast._t);
        toast._t = setTimeout(function(){ toast.style.display='none'; }, 2500);
    }

    function moveLead(id, newStatus, card) {
        var fd = new FormData();
        fd.append('lead_id', id);
        fd.append('status', newStatus);
        fetch(ajaxUrl, { method:'POST', body:fd, credentials:'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    if (card) {
                        var cards = document.getElementById('col-' + newStatus);
                        if (cards) {
                            var empty = cards.querySelector('.sp-pipeline-empty');
                            if (empty) empty.remove();
                            cards.appendChild(card);
                            card.setAttribute('data-status', newStatus);
                        }
                        updateColCounts();
                    }
                    showToast('Moved to ' + newStatus, true);
                } else {
                    showToast('Move failed — ' + (d.data || ''), false);
                }
            })
            .catch(function(e){ showToast('Request failed', false); });
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

    document.querySelectorAll('.sp-pipeline-card').forEach(function(card) {
        card.addEventListener('dragstart', function(e) {
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function() {
            card.classList.remove('dragging');
            document.querySelectorAll('.sp-pipeline-col').forEach(function(c){ c.classList.remove('drag-over'); });
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
            var newStatus = col.getAttribute('data-status');
            var oldStatus = dragged.getAttribute('data-status');
            if (newStatus === oldStatus) return;
            moveLead(dragged.getAttribute('data-id'), newStatus, dragged);
        });
    });

    document.querySelectorAll('.sp-pipeline-move-select').forEach(function(sel) {
        sel.addEventListener('change', function() {
            var id        = sel.getAttribute('data-id');
            var newStatus = sel.value;
            var card      = sel.closest('.sp-pipeline-card');
            var oldStatus = card ? card.getAttribute('data-status') : '';
            if (newStatus === oldStatus) return;
            moveLead(id, newStatus, card);
        });
    });
})();
</script>
