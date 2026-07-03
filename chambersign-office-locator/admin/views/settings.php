<?php
/**
 * Vue : page Réglages ChamberSign Locator.
 *
 * @package ChamberSign\Locator
 */

use ChamberSign\Locator\Admin\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}
?>
<div class="wrap csol-admin-wrap">
	<h1><?php esc_html_e( 'ChamberSign Locator — Réglages', 'chambersign-office-locator' ); ?></h1>
	<form action="options.php" method="post">
		<?php
		settings_fields( Settings::OPTION_GROUP );
		do_settings_sections( Settings::PAGE_SLUG );
		submit_button( __( 'Enregistrer les réglages', 'chambersign-office-locator' ) );
		?>
	</form>
</div>
