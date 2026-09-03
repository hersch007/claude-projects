<footer>
  <div class="wrap footer-grid">
    <div>
      <strong>Karen Welch Buttars, LMHC</strong>
      <span>IFS Certified Therapist</span>
    </div>
    <div>
      <span>Online therapy for adults in Massachusetts</span>
      <?php $phone = get_theme_mod( 'kwb_phone', '617-230-3180' ); ?>
      <a href="tel:+1<?php echo preg_replace('/[^0-9]/', '', esc_attr( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
    </div>
    <a href="/contact/"
       data-spwidget-scope-id="8bb13639-a65a-41be-8513-47d286734364"
       data-spwidget-scope-uri="karenwelch"
       data-spwidget-application-id="7c72cb9f9a9b913654bb89d6c7b4e71a77911b30192051da35384b4d0c6d505b"
       data-spwidget-channel="embedded_widget"
       data-spwidget-type="Contact form"
       data-spwidget-contact=""
       data-spwidget-scope-global=""
       data-spwidget-autobind="">Contact Karen &#x2197;</a>
    <span>&copy; <?php echo date( 'Y' ); ?> Karen Welch Buttars</span>
  </div>
</footer>

<script src="https://widget-cdn.simplepractice.com/assets/integration-1.0.js"></script>
<div class="spwidget--preload">
  <iframe class="widget-iframe"
    src="https://karenwelch.clientsecure.me/widget-redirect?scopeId=8bb13639-a65a-41be-8513-47d286734364&scopeGlobal=true&applicationId=7c72cb9f9a9b913654bb89d6c7b4e71a77911b30192051da35384b4d0c6d505b&channel=embedded_widget&appearance=%7B%22fullScreen%22%3Atrue%7D&contact=true"
    frameborder="0" allowtransparency="true" allow="geolocation"
    style="display:block;position:relative;top:0%;width:100%;max-width:720px;height:0px;margin:0 auto"
    title="Send message"></iframe>
</div>

<?php wp_footer(); ?>
</body>
</html>
