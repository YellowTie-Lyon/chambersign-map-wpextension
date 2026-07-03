<?php
/**
 * Vue : page d'import CSV/XLSX des bureaux.
 *
 * @package ChamberSign\Locator
 */

use ChamberSign\Locator\Admin\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$status = isset( $_GET['csol_import'] ) ? sanitize_key( wp_unslash( $_GET['csol_import'] ) ) : '';
$result = get_transient( 'csol_import_result_' . get_current_user_id() );

if ( $result ) {
	delete_transient( 'csol_import_result_' . get_current_user_id() );
}
?>
<div class="wrap csol-admin-wrap">
	<h1><?php esc_html_e( 'Import de bureaux d\'enregistrement', 'chambersign-office-locator' ); ?></h1>

	<?php if ( 'success' === $status && $result ) : ?>
		<div class="notice notice-success csol-import-results">
			<p>
				<?php
				printf(
					/* translators: 1: created count, 2: updated count, 3: skipped count */
					esc_html__( 'Import terminé : %1$d bureau(x) créé(s), %2$d mis à jour, %3$d ignoré(s).', 'chambersign-office-locator' ),
					(int) $result['created'],
					(int) $result['updated'],
					(int) $result['skipped']
				);
				?>
			</p>
		</div>
	<?php elseif ( 'error' === $status ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Aucun fichier n\'a été reçu.', 'chambersign-office-locator' ); ?></p></div>
	<?php elseif ( 'format' === $status ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Format de fichier non pris en charge. Utilisez un fichier .csv ou .xlsx.', 'chambersign-office-locator' ); ?></p></div>
	<?php elseif ( 'empty' === $status ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Le fichier ne contient aucune ligne exploitable.', 'chambersign-office-locator' ); ?></p></div>
	<?php endif; ?>

	<p><?php esc_html_e( 'Importez ou mettez à jour plusieurs bureaux d\'enregistrement en une fois à partir d\'un fichier CSV ou XLSX.', 'chambersign-office-locator' ); ?></p>

	<table class="widefat csol-import-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Colonnes attendues', 'chambersign-office-locator' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr><td>Région, Nom du bureau, Adresse, Téléphone, Site internet, Latitude, Longitude, Produits</td></tr>
		</tbody>
	</table>
	<p class="description">
		<?php esc_html_e( 'Les colonnes Département, Ville, Code postal et Horaires sont également reconnues si présentes. La colonne "Produits" accepte plusieurs valeurs séparées par une virgule ou un "|". Un bureau existant portant le même nom est mis à jour plutôt que dupliqué.', 'chambersign-office-locator' ); ?>
	</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="csol_import_bureaux" />
		<?php wp_nonce_field( Import::NONCE_ACTION, Import::NONCE_NAME ); ?>
		<table class="form-table">
			<tr>
				<th><label for="csol_import_file"><?php esc_html_e( 'Fichier CSV ou XLSX', 'chambersign-office-locator' ); ?></label></th>
				<td><input type="file" id="csol_import_file" name="csol_import_file" accept=".csv,.xlsx" required /></td>
			</tr>
		</table>
		<?php submit_button( __( 'Importer', 'chambersign-office-locator' ) ); ?>
	</form>
</div>
