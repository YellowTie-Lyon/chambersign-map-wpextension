/**
 * Bouton de géocodage dans l'admin : appelle l'action AJAX csol_geocode
 * (server-side, via Nominatim) pour pré-remplir latitude/longitude.
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var button = document.getElementById( 'csol_geocode_button' );
		if ( ! button ) {
			return;
		}

		var statusEl = document.getElementById( 'csol_geocode_status' );

		button.addEventListener( 'click', function () {
			var adresse = ( document.getElementById( 'csol_adresse' ).value || '' ).trim();
			var codePostal = ( document.getElementById( 'csol_code_postal' ).value || '' ).trim();
			var ville = ( document.getElementById( 'csol_ville' ).value || '' ).trim();
			var query = [ adresse, codePostal, ville ].filter( Boolean ).join( ', ' );

			if ( '' === query ) {
				statusEl.textContent = csolAdmin.i18n.notFound;
				return;
			}

			button.disabled = true;
			statusEl.textContent = csolAdmin.i18n.searching;

			var formData = new FormData();
			formData.append( 'action', 'csol_geocode' );
			formData.append( 'nonce', csolAdmin.nonce );
			formData.append( 'query', query );

			fetch( csolAdmin.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( response ) {
					button.disabled = false;

					if ( response.success && response.data ) {
						document.getElementById( 'csol_latitude' ).value = response.data.lat;
						document.getElementById( 'csol_longitude' ).value = response.data.lon;
						statusEl.textContent = response.data.display_name || '';
					} else {
						statusEl.textContent = csolAdmin.i18n.notFound;
					}
				} )
				.catch( function () {
					button.disabled = false;
					statusEl.textContent = csolAdmin.i18n.error;
				} );
		} );
	} );
} )();
