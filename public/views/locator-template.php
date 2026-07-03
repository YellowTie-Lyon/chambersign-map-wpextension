<?php
/**
 * Vue front : localisateur de bureaux (carte + liste), shortcode
 * [chambersign_locator].
 *
 * @var array<int, string>     $regions
 * @var array<int, \WP_Term>   $produits
 * @package ChamberSign\Locator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

static $csol_instance = 0;
$csol_instance++;
$csol_id = 'csol-locator-' . $csol_instance;
?>
<div id="<?php echo esc_attr( $csol_id ); ?>" class="csol-locator" data-csol-instance="<?php echo esc_attr( $csol_instance ); ?>">

	<div class="csol-locator-filters">
		<div class="csol-filter csol-filter-search">
			<label for="<?php echo esc_attr( $csol_id ); ?>-search" class="screen-reader-text">
				<?php esc_html_e( 'Rechercher un bureau', 'chambersign-office-locator' ); ?>
			</label>
			<input
				type="search"
				id="<?php echo esc_attr( $csol_id ); ?>-search"
				class="csol-input csol-search-input"
				placeholder="<?php esc_attr_e( 'Rechercher une ville, un bureau…', 'chambersign-office-locator' ); ?>"
			/>
		</div>

		<button type="button" class="csol-btn csol-btn-secondary csol-geoloc-button" id="<?php echo esc_attr( $csol_id ); ?>-geoloc">
			<?php esc_html_e( 'Autour de moi', 'chambersign-office-locator' ); ?>
		</button>

		<div class="csol-filter">
			<label for="<?php echo esc_attr( $csol_id ); ?>-region" class="screen-reader-text">
				<?php esc_html_e( 'Région', 'chambersign-office-locator' ); ?>
			</label>
			<select id="<?php echo esc_attr( $csol_id ); ?>-region" class="csol-select csol-filter-region">
				<option value=""><?php esc_html_e( 'Toutes les régions', 'chambersign-office-locator' ); ?></option>
				<?php foreach ( $regions as $region ) : ?>
					<option value="<?php echo esc_attr( $region ); ?>"><?php echo esc_html( $region ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="csol-filter">
			<label for="<?php echo esc_attr( $csol_id ); ?>-produit" class="screen-reader-text">
				<?php esc_html_e( 'Produit', 'chambersign-office-locator' ); ?>
			</label>
			<select id="<?php echo esc_attr( $csol_id ); ?>-produit" class="csol-select csol-filter-produit">
				<option value=""><?php esc_html_e( 'Tous les produits', 'chambersign-office-locator' ); ?></option>
				<?php foreach ( $produits as $produit ) : ?>
					<option value="<?php echo esc_attr( $produit->slug ); ?>"><?php echo esc_html( $produit->name ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<p class="csol-geoloc-status" id="<?php echo esc_attr( $csol_id ); ?>-geoloc-status" aria-live="polite"></p>

	<div class="csol-locator-body">
		<div class="csol-locator-map-col">
			<div class="csol-map" id="<?php echo esc_attr( $csol_id ); ?>-map" role="application" aria-label="<?php esc_attr_e( 'Carte des bureaux d\'enregistrement', 'chambersign-office-locator' ); ?>"></div>
		</div>

		<div class="csol-locator-list-col">
			<div class="csol-list-count" id="<?php echo esc_attr( $csol_id ); ?>-count" aria-live="polite"></div>
			<div class="csol-list" id="<?php echo esc_attr( $csol_id ); ?>-list">
				<p class="csol-list-loading"><?php esc_html_e( 'Chargement des bureaux…', 'chambersign-office-locator' ); ?></p>
			</div>
		</div>
	</div>
</div>
