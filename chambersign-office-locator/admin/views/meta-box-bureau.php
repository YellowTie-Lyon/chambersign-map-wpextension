<?php
/**
 * Vue : meta box des informations d'un bureau.
 *
 * @var array<string, string> $values
 * @package ChamberSign\Locator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="form-table csol-meta-box">
	<tbody>
		<tr>
			<th><label for="csol_region"><?php esc_html_e( 'Région', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="text" id="csol_region" name="csol_region" value="<?php echo esc_attr( $values['region'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="csol_departement"><?php esc_html_e( 'Département', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="text" id="csol_departement" name="csol_departement" value="<?php echo esc_attr( $values['departement'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="csol_ville"><?php esc_html_e( 'Ville', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="text" id="csol_ville" name="csol_ville" value="<?php echo esc_attr( $values['ville'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="csol_code_postal"><?php esc_html_e( 'Code postal', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="text" id="csol_code_postal" name="csol_code_postal" value="<?php echo esc_attr( $values['code_postal'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="csol_adresse"><?php esc_html_e( 'Adresse complète', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="text" id="csol_adresse" name="csol_adresse" value="<?php echo esc_attr( $values['adresse'] ); ?>" class="large-text" /></td>
		</tr>
		<tr>
			<th><label for="csol_telephone"><?php esc_html_e( 'Téléphone', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="text" id="csol_telephone" name="csol_telephone" value="<?php echo esc_attr( $values['telephone'] ); ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="csol_site_internet"><?php esc_html_e( 'Site internet', 'chambersign-office-locator' ); ?></label></th>
			<td><input type="url" id="csol_site_internet" name="csol_site_internet" value="<?php echo esc_attr( $values['site_internet'] ); ?>" class="large-text" placeholder="https://" /></td>
		</tr>
		<tr>
			<th><label for="csol_horaires"><?php esc_html_e( 'Horaires', 'chambersign-office-locator' ); ?></label></th>
			<td><textarea id="csol_horaires" name="csol_horaires" class="large-text" rows="3"><?php echo esc_textarea( $values['horaires'] ); ?></textarea></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Coordonnées GPS', 'chambersign-office-locator' ); ?></th>
			<td>
				<label for="csol_latitude"><?php esc_html_e( 'Latitude', 'chambersign-office-locator' ); ?></label>
				<input type="text" id="csol_latitude" name="csol_latitude" value="<?php echo esc_attr( $values['latitude'] ); ?>" class="small-text" />
				&nbsp;
				<label for="csol_longitude"><?php esc_html_e( 'Longitude', 'chambersign-office-locator' ); ?></label>
				<input type="text" id="csol_longitude" name="csol_longitude" value="<?php echo esc_attr( $values['longitude'] ); ?>" class="small-text" />
				&nbsp;
				<button type="button" id="csol_geocode_button" class="button">
					<?php esc_html_e( 'Géocoder depuis l\'adresse', 'chambersign-office-locator' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Utilise OpenStreetMap Nominatim à partir des champs Adresse, Code postal et Ville.', 'chambersign-office-locator' ); ?></p>
				<p id="csol_geocode_status" class="csol-geocode-status" aria-live="polite"></p>
			</td>
		</tr>
		<tr>
			<th><label for="csol_actif"><?php esc_html_e( 'Actif', 'chambersign-office-locator' ); ?></label></th>
			<td>
				<label>
					<input type="checkbox" id="csol_actif" name="csol_actif" value="1" <?php checked( '1', $values['actif'] ); ?> />
					<?php esc_html_e( 'Afficher ce bureau sur le localisateur', 'chambersign-office-locator' ); ?>
				</label>
			</td>
		</tr>
	</tbody>
</table>
