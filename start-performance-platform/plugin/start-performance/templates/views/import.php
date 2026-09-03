<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! sp_is_admin_member() ) { echo '<p class="sp-empty">Access denied.</p>'; return; }

$type    = sanitize_key( isset( $_GET['type'] ) ? $_GET['type'] : 'contacts' );
$result  = isset( $_GET['imported'] ) ? (int) $_GET['imported'] : null;
$errors  = isset( $_GET['skipped'] )  ? (int) $_GET['skipped']  : null;
$types   = array( 'contacts' => 'Contacts', 'companies' => 'Companies', 'leads' => 'Leads' );
if ( ! isset( $types[ $type ] ) ) $type = 'contacts';

$templates = array(
    'contacts'  => array( 'first_name', 'last_name', 'email', 'phone', 'company_name', 'source', 'status', 'notes' ),
    'companies' => array( 'name', 'industry', 'website', 'phone', 'address', 'notes' ),
    'leads'     => array( 'contact_email', 'company_name', 'source', 'status', 'score', 'notes' ),
);
?>
<div class="sp-page-header">
    <h1>CSV Import</h1>
</div>

<?php if ( $result !== null ) : ?>
<div class="sp-alert" style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#166534">
    Import complete — <strong><?php echo $result; ?></strong> records imported<?php echo $errors ? ', <strong>' . $errors . '</strong> rows skipped (missing required fields)' : ''; ?>.
</div>
<?php endif; ?>

<div class="sp-card sp-form-card">
    <div style="display:flex;gap:8px;margin-bottom:24px">
        <?php foreach ( $types as $t => $label ) : ?>
            <a href="<?php echo esc_url( home_url( '/sp-app/?view=import&type=' . $t ) ); ?>"
               class="sp-btn <?php echo $type === $t ? 'sp-btn-primary' : 'sp-btn-ghost'; ?>">
                <?php echo esc_html( $label ); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <h2 class="sp-section-heading" style="margin-bottom:12px">Import <?php echo esc_html( $types[$type] ); ?></h2>

    <p style="font-size:13px;color:#64748b;margin-bottom:16px">
        Upload a CSV file with a header row. Column names must match the template below.
        <?php if ( $type === 'contacts' ) echo 'Duplicate emails will be skipped.'; ?>
        <?php if ( $type === 'companies' ) echo 'Duplicate company names will be skipped.'; ?>
        <?php if ( $type === 'leads' ) echo 'contact_email must match an existing contact.'; ?>
    </p>

    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-family:monospace;font-size:12px;color:#475569;overflow-x:auto">
        <?php echo esc_html( implode( ',', $templates[ $type ] ) ); ?>
    </div>

    <form method="post" action="<?php echo esc_url( home_url( '/sp-app/' ) ); ?>" enctype="multipart/form-data">
        <?php wp_nonce_field( 'sp_form', 'sp_nonce' ); ?>
        <input type="hidden" name="sp_type" value="import">
        <input type="hidden" name="sp_id" value="0">
        <input type="hidden" name="import_type" value="<?php echo esc_attr( $type ); ?>">
        <div class="sp-form-row" style="align-items:flex-end;gap:12px">
            <div class="sp-field" style="flex:1">
                <label>CSV File</label>
                <input type="file" name="sp_csv" accept=".csv,text/csv" required>
            </div>
            <div class="sp-form-actions" style="margin:0;padding:0">
                <button type="submit" class="sp-btn sp-btn-primary">Import</button>
            </div>
        </div>
    </form>

    <div style="margin-top:28px;padding-top:20px;border-top:1px solid #f1f5f9">
        <h3 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:12px">Export existing data</h3>
        <div style="display:flex;gap:8px">
            <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=contacts' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Export Contacts</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=companies' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Export Companies</a>
            <a href="<?php echo esc_url( home_url( '/sp-app/?sp_export=leads' ) ); ?>" class="sp-btn sp-btn-ghost sp-btn-sm">Export Leads</a>
        </div>
    </div>
</div>
