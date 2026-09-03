<?php
// Bridge file for Start Performance's sp_register_view() -- core does a raw
// `require` on this path when a team member navigates to ?view=fruth-quotes.
// All real logic lives in the main plugin file (sales-quote-system.php);
// this just calls it with $embedded=true so the Quote Builder renders
// without its own standalone chrome (app-bar, sign-out button), since
// Start Performance's own sidebar/shell already provides that. Visible to
// any logged-in team member (admin or agent) -- core has already gated the
// whole /sp-app/ shell behind requiring a logged-in team member before this
// file is ever reached.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
sqs_pricing_calculator_activate();
sqs_pricing_calculator_render( true );
