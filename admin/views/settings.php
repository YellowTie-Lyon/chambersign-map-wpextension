<?php
/**
 * Vue : page Réglages ChamberSign Locator.
 *
 * @package ChamberSign\Locator
 */

use ChamberSign\Locator\Admin\Settings;
use ChamberSign\Locator\Admin\Marker_Icon;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

wp_enqueue_style( 'csol-admin', CSOL_PLUGIN_URL . 'admin/css/admin.css', array(), CSOL_VERSION );

$icon_status = isset( $_GET['csol_icon'] ) ? sanitize_key( wp_unslash( $_GET['csol_icon'] ) ) : '';
$icon_url    = Marker_Icon::get_icon_url();
?>
<div class="wrap csol-admin-wrap">
	<h1><?php esc_html_e( 'ChamberSign Locator — Réglages', 'chambersign-office-locator' ); ?></h1>

	<?php if ( 'success' === $icon_status ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Icône du marqueur mise à jour.', 'chambersign-office-locator' ); ?></p></div>
	<?php elseif ( 'removed' === $icon_status ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Icône personnalisée supprimée, le pin par défaut est de nouveau utilisé.', 'chambersign-office-locator' ); ?></p></div>
	<?php elseif ( 'format' === $icon_status ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Fichier invalide : seul le format SVG est accepté.', 'chambersign-office-locator' ); ?></p></div>
	<?php elseif ( 'size' === $icon_status ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Fichier trop volumineux (100 Ko maximum).', 'chambersign-office-locator' ); ?></p></div>
	<?php elseif ( 'error' === $icon_status ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Erreur lors de l\'envoi du fichier.', 'chambersign-office-locator' ); ?></p></div>
	<?php endif; ?>

	<form action="options.php" method="post">
		<?php
		settings_fields( Settings::OPTION_GROUP );
		do_settings_sections( Settings::PAGE_SLUG );
		?>

		<h2 class="title"><?php esc_html_e( 'Icône du marqueur', 'chambersign-office-locator' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Icône actuelle', 'chambersign-office-locator' ); ?></th>
				<td>
					<?php if ( $icon_url ) : ?>
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="" class="csol-marker-icon-preview" />
					<?php else : ?>
						<span class="description"><?php esc_html_e( 'Pin rouge par défaut (aucune icône personnalisée).', 'chambersign-office-locator' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Enregistrer les réglages', 'chambersign-office-locator' ) ); ?>
	</form>

	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data" class="csol-icon-upload-form">
		<input type="hidden" name="action" value="csol_upload_marker_icon" />
		<?php wp_nonce_field( Marker_Icon::NONCE_ACTION, Marker_Icon::NONCE_NAME ); ?>
		<table class="form-table">
			<tr>
				<th><label for="csol_marker_icon"><?php esc_html_e( 'Remplacer l\'icône (SVG)', 'chambersign-office-locator' ); ?></label></th>
				<td>
					<input type="file" id="csol_marker_icon" name="csol_marker_icon" accept=".svg,image/svg+xml" required />
					<p class="description"><?php esc_html_e( 'Fichier SVG, 100 Ko maximum. Utilisez de préférence une icône en forme de pin/goutte, la pointe vers le bas.', 'chambersign-office-locator' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Envoyer l\'icône', 'chambersign-office-locator' ), 'secondary' ); ?>
	</form>

	<?php if ( $icon_url ) : ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="csol_remove_marker_icon" />
			<?php wp_nonce_field( Marker_Icon::NONCE_ACTION, Marker_Icon::NONCE_NAME ); ?>
			<?php submit_button( __( 'Revenir à l\'icône par défaut', 'chambersign-office-locator' ), 'delete' ); ?>
		</form>
	<?php endif; ?>
</div>
