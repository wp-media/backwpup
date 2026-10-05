<?php
/**
 * Consent screen view template.
 *
 * Rendered by AuthorizeCallback::output_consent_screen() via Render::view().
 * Pure presentation — assumes `$data` is fully populated and pre-validated.
 *
 * @var array<string, mixed> $data {
 *     @type string $state        OAuth state token.
 *     @type string $client_name  Requesting client's display name.
 *     @type string $client_id    esc_url'd client_id.
 *     @type string $client_uri   esc_url'd client_uri (may be empty).
 *     @type bool   $verified     Whether the client's publisher is verified.
 *     @type string $publisher    Verified publisher name (may be empty).
 *     @type string $site_name    This site's display name.
 *     @type string $consent_url  esc_url'd POST target for the Allow/Deny form.
 *     @type string $display_href client_uri if set, otherwise client_id.
 * }
 */

declare(strict_types=1);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( __( 'Authorize Access', 'mcp-oauth' ) . ' — ' . $data['site_name'] ); ?></title>
	<style>
		*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			background: #f0f0f1;
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 100vh;
			padding: 20px;
		}
		.consent-card {
			background: #fff;
			border-radius: 4px;
			box-shadow: 0 1px 3px rgba(0,0,0,.13);
			max-width: 420px;
			width: 100%;
			padding: 36px 40px 40px;
		}
		.consent-card h1 {
			font-size: 1.1rem;
			font-weight: 600;
			color: #1d2327;
			margin-bottom: 20px;
			text-align: center;
		}
		.client-block {
			border: 1px solid #ddd;
			border-radius: 3px;
			padding: 14px 16px;
			margin-bottom: 20px;
		}
		.client-name {
			font-size: 1rem;
			font-weight: 600;
			color: #1d2327;
		}
		.client-name a { color: inherit; text-decoration: none; }
		.client-name a:hover { text-decoration: underline; }
		.client-url {
			font-size: .78rem;
			color: #646970;
			word-break: break-all;
			margin-top: 4px;
		}
		.client-url a { color: #646970; }
		.verified-badge {
			display: inline-block;
			font-size: .72rem;
			font-weight: 600;
			color: #00a32a;
			background: #edfaef;
			border: 1px solid #a7e8b1;
			border-radius: 2px;
			padding: 2px 6px;
			margin-top: 8px;
		}
		.scope-text {
			font-size: .875rem;
			color: #3c434a;
			margin-bottom: 24px;
			line-height: 1.5;
		}
		.consent-actions {
			display: flex;
			gap: 10px;
		}
		.btn {
			flex: 1;
			padding: 9px 14px;
			font-size: .875rem;
			font-weight: 600;
			border-radius: 3px;
			border: 1px solid transparent;
			cursor: pointer;
			text-align: center;
		}
		.btn-allow {
			background: #2271b1;
			color: #fff;
			border-color: #2271b1;
		}
		.btn-allow:hover { background: #135e96; border-color: #135e96; }
		.btn-deny {
			background: #fff;
			color: #d63638;
			border-color: #d63638;
		}
		.btn-deny:hover { background: #fcf0f1; }
	</style>
</head>
<body>
	<div class="consent-card">
		<h1><?php esc_html_e( 'Authorize access to your site?', 'mcp-oauth' ); ?></h1>

		<div class="client-block">
			<div class="client-name">
				<?php if ( '' !== $data['display_href'] ) : ?>
					<a href="<?php echo esc_url( $data['display_href'] ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( $data['client_name'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $data['client_name'] ); ?>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $data['client_id'] ) : ?>
				<div class="client-url">
					<?php esc_html_e( 'ID:', 'mcp-oauth' ); ?>
					<a href="<?php echo esc_url( $data['client_id'] ); ?>" rel="noopener noreferrer" target="_blank"><?php echo esc_html( $data['client_id'] ); ?></a>
				</div>
			<?php endif; ?>
			<?php if ( $data['verified'] && '' !== $data['publisher'] ) : ?>
				<div class="verified-badge">
					<?php
					/* translators: %s: publisher name */
					printf( esc_html__( 'Verified publisher: %s', 'mcp-oauth' ), esc_html( $data['publisher'] ) );
					?>
				</div>
			<?php endif; ?>
		</div>

		<p class="scope-text">
			<?php
			printf(
				/* translators: 1: client name, 2: site name */
				esc_html__( '%1$s is requesting access to the MCP tools on %2$s on your behalf.', 'mcp-oauth' ),
				'<strong>' . esc_html( $data['client_name'] ) . '</strong>',
				'<strong>' . esc_html( $data['site_name'] ) . '</strong>'
			);
			?>
		</p>

		<form method="post" action="<?php echo esc_url( $data['consent_url'] ); ?>">
			<input type="hidden" name="state" value="<?php echo esc_attr( $data['state'] ); ?>">
			<?php wp_nonce_field( 'mcp_consent_' . $data['state'], 'mcp_consent_nonce' ); ?>
			<div class="consent-actions">
				<button type="submit" name="mcp_action" value="allow" class="btn btn-allow">
					<?php esc_html_e( 'Allow', 'mcp-oauth' ); ?>
				</button>
				<button type="submit" name="mcp_action" value="deny" class="btn btn-deny">
					<?php esc_html_e( 'Deny', 'mcp-oauth' ); ?>
				</button>
			</div>
		</form>
	</div>
</body>
</html>
