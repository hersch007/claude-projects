<?php
$city_name = get_option( 'sp_city_name', get_bloginfo( 'name' ) );
?>
<footer class="cc-footer">
    &copy; <?php echo date( 'Y' ); ?> <?php echo esc_html( $city_name ); ?>
    &nbsp;&middot;&nbsp; <a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>">Terms of Service</a>
    &nbsp;&middot;&nbsp; <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Privacy Policy</a>
    &nbsp;&middot;&nbsp; Powered by <a href="https://startperformance.com" target="_blank">Start Performance</a>
</footer>

<?php wp_footer(); ?>
</body>
</html>
