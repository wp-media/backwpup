<p class="notice-titre">📈 <?php echo esc_html__( 'Help Us Improve BackWPup!', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_5_3_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<p>
	<?php echo esc_html__( 'Can we collect anonymous data to make BackWPup better?', 'backwpup' ); ?>
</p>
<ul>
	<li><?php echo esc_html__( 'What we track: Only features usage, onboarding, errors, & environment info.', 'backwpup' ); ?></li>
	<li><?php echo esc_html__( 'Why: To understand what works, fix bugs faster, and prioritize new features.', 'backwpup' ); ?></li>
</ul>
<p>
	<a href="#" class="bwu-onboarding-optin" data-optin="yes"><?php echo esc_html__( 'Yes, help improve BackWPup!', 'backwpup' ); ?></a><br />
	<a href="#" class="bwu-onboarding-optin" data-optin="no"><?php echo esc_html__( 'No, thanks.', 'backwpup' ); ?></a>
</p>
<p><?php echo esc_html__( 'You can change this setting at any time in the plugin settings.', 'backwpup' ); ?></p>
</p>
<script>var bwuAnalyticsOptin={"_ajax_nonce":"<?php echo esc_js( wp_create_nonce( 'backwpup_analytics_optin' ) ); ?>"};</script>