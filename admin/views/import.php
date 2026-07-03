<?php
/**
 * Vue : page d'import CSV/XLSX des bureaux.
 *
 * @package ChamberSign\Locator
 */

use ChamberSign\Locator\Admin\Import;
use ChamberSign\Locator\Ajax\Geocode;

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

$missing_count = count( Geocode::get_bureaux_missing_coordinates() );
$total_count   = count( Geocode::get_bureaux_missing_coordinates( true ) );

wp_enqueue_style( 'csol-admin', CSOL_PLUGIN_URL . 'admin/css/admin.css', array(), CSOL_VERSION );
wp_enqueue_script( 'csol-admin-geocode-batch', CSOL_PLUGIN_URL . 'admin/js/geocode-batch.js', array(), CSOL_VERSION, true );
wp_localize_script(
	'csol-admin-geocode-batch',
	'csolAdminBatch',
	array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'action'  => Geocode::BATCH_ACTION,
		'nonce'   => wp_create_nonce( Geocode::ACTION ),
		'i18n'    => array(
			'starting'      => __( 'Démarrage du géocodage…', 'chambersign-office-locator' ),
			/* translators: 1: remaining count, 2: succeeded count, 3: failed count */
			'progress'      => __( '%1$d bureau(x) restant(s)… (%2$d géocodé(s), %3$d en échec)', 'chambersign-office-locator' ),
			/* translators: 1: remaining count that could not be geocoded */
			'done'          => __( 'Terminé. %1$d bureau(x) n\'ont pas pu être géocodés automatiquement (adresse incomplète ou introuvable) : complétez-les manuellement dans la fiche du bureau.', 'chambersign-office-locator' ),
			'error'         => __( 'Erreur pendant le géocodage. Réessayez.', 'chambersign-office-locator' ),
			'confirmForce'  => __( 'Ceci va recalculer les coordonnées GPS de TOUS les bureaux publiés, y compris ceux qui en ont déjà (elles seront remplacées). Continuer ?', 'chambersign-office-locator' ),
		),
	)
);
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

	<hr />

	<h2><?php esc_html_e( 'Géocodage automatique', 'chambersign-office-locator' ); ?></h2>
	<p class="description"><?php esc_html_e( 'Le géocodage restreint désormais la recherche à la France, pour éviter qu\'une adresse ambiguë ne soit associée par erreur à un pays homonyme.', 'chambersign-office-locator' ); ?></p>

	<?php if ( $missing_count > 0 ) : ?>
		<p>
			<?php
			printf(
				/* translators: %d: number of bureaus without coordinates */
				esc_html( _n( '%d bureau publié n\'a pas de coordonnées GPS.', '%d bureaux publiés n\'ont pas de coordonnées GPS.', $missing_count, 'chambersign-office-locator' ) ),
				(int) $missing_count
			);
			?>
			<?php esc_html_e( 'Lancez le géocodage automatique (OpenStreetMap Nominatim) à partir de leur adresse, code postal et ville : le traitement se fait par petits lots pour respecter les limites du serveur, restez sur cette page jusqu\'à la fin.', 'chambersign-office-locator' ); ?>
		</p>
		<p>
			<button type="button" id="csol_geocode_batch_button" class="button button-primary"><?php esc_html_e( 'Géocoder les bureaux manquants', 'chambersign-office-locator' ); ?></button>
		</p>
	<?php else : ?>
		<p><?php esc_html_e( 'Tous les bureaux publiés ont des coordonnées GPS.', 'chambersign-office-locator' ); ?></p>
	<?php endif; ?>

	<p>
		<?php
		printf(
			/* translators: %d: total number of published bureaus */
			esc_html( _n( 'Vous pouvez aussi forcer le re-géocodage du seul bureau publié, par exemple si des coordonnées existantes semblent erronées.', 'Vous pouvez aussi forcer le re-géocodage des %d bureaux publiés, par exemple si des coordonnées existantes semblent erronées.', $total_count, 'chambersign-office-locator' ) ),
			(int) $total_count
		);
		?>
	</p>
	<p>
		<button type="button" id="csol_geocode_force_button" class="button"><?php esc_html_e( 'Re-géocoder tous les bureaux', 'chambersign-office-locator' ); ?></button>
	</p>

	<p id="csol_geocode_batch_status" class="csol-geocode-status" aria-live="polite"></p>
</div>
