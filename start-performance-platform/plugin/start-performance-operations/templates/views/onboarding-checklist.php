<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// Redirect to the onboarding list — this view exists only so the slug is registered
wp_redirect( home_url( '/sp-app/?view=onboarding' ) ); exit;
