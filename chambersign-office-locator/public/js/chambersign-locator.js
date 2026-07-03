/**
 * ChamberSign Office Locator - front-end
 * Carte Leaflet/OpenStreetMap + recherche AJAX + interactions carte/liste.
 * JavaScript natif, sans dépendance autre que Leaflet.
 */
( function () {
	'use strict';

	if ( typeof csolLocator === 'undefined' || typeof L === 'undefined' ) {
		return;
	}

	// Icônes Leaflet servies localement (pas de dépendance CDN externe).
	L.Icon.Default.mergeOptions( {
		iconRetinaUrl: csolLocator.leafletImageUrl + 'marker-icon-2x.png',
		iconUrl: csolLocator.leafletImageUrl + 'marker-icon.png',
		shadowUrl: csolLocator.leafletImageUrl + 'marker-shadow.png',
	} );

	/**
	 * Débounce simple pour la recherche texte.
	 */
	function debounce( fn, wait ) {
		var timeout;
		return function () {
			var args = arguments;
			clearTimeout( timeout );
			timeout = setTimeout( function () {
				fn.apply( null, args );
			}, wait );
		};
	}

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = value === null || value === undefined ? '' : String( value );
		return div.innerHTML;
	}

	/**
	 * Échappe une valeur pour une insertion sûre dans un attribut HTML
	 * entre guillemets doubles (échappe aussi les guillemets).
	 */
	function escapeAttr( value ) {
		return escapeHtml( value ).replace( /"/g, '&quot;' ).replace( /'/g, '&#39;' );
	}

	/**
	 * Construit une instance du localisateur pour un conteneur donné.
	 */
	function CsolLocatorInstance( root ) {
		this.root = root;
		this.id = root.id;
		this.settings = csolLocator.settings;

		this.mapEl = document.getElementById( this.id + '-map' );
		this.listEl = document.getElementById( this.id + '-list' );
		this.countEl = document.getElementById( this.id + '-count' );
		this.searchInput = root.querySelector( '.csol-search-input' );
		this.regionSelect = root.querySelector( '.csol-filter-region' );
		this.departementSelect = root.querySelector( '.csol-filter-departement' );
		this.produitSelect = root.querySelector( '.csol-filter-produit' );

		this.markers = {};
		this.activeCardId = null;

		this.initMap();
		this.bindFilters();
		this.search();
	}

	CsolLocatorInstance.prototype.initMap = function () {
		this.map = L.map( this.mapEl ).setView(
			[ this.settings.defaultLat, this.settings.defaultLng ],
			this.settings.zoomFrance
		);

		L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
		} ).addTo( this.map );

		this.markersLayer = L.layerGroup().addTo( this.map );
	};

	CsolLocatorInstance.prototype.bindFilters = function () {
		var self = this;
		var triggerSearch = debounce( function () {
			self.search();
		}, 350 );

		if ( this.searchInput ) {
			this.searchInput.addEventListener( 'input', triggerSearch );
		}
		if ( this.regionSelect ) {
			this.regionSelect.addEventListener( 'change', function () {
				self.search();
			} );
		}
		if ( this.departementSelect ) {
			this.departementSelect.addEventListener( 'change', function () {
				self.search();
			} );
		}
		if ( this.produitSelect ) {
			this.produitSelect.addEventListener( 'change', function () {
				self.search();
			} );
		}
	};

	CsolLocatorInstance.prototype.search = function () {
		var self = this;

		this.listEl.innerHTML = '<p class="csol-list-loading">' + escapeHtml( csolLocator.i18n.loading ) + '</p>';

		var formData = new FormData();
		formData.append( 'action', csolLocator.action );
		formData.append( 'nonce', csolLocator.nonce );
		formData.append( 'search', this.searchInput ? this.searchInput.value : '' );
		formData.append( 'region', this.regionSelect ? this.regionSelect.value : '' );
		formData.append( 'departement', this.departementSelect ? this.departementSelect.value : '' );
		formData.append( 'produit', this.produitSelect ? this.produitSelect.value : '' );

		fetch( csolLocator.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( response ) {
				if ( response.success ) {
					self.render( response.data.bureaux );
				} else {
					self.renderEmpty();
				}
			} )
			.catch( function () {
				self.renderEmpty();
			} );
	};

	CsolLocatorInstance.prototype.render = function ( bureaux ) {
		this.markersLayer.clearLayers();
		this.markers = {};
		this.activeCardId = null;

		this.countEl.textContent = bureaux.length + ' ' + ( bureaux.length > 1 ? 'bureaux' : 'bureau' );

		if ( ! bureaux.length ) {
			this.renderEmpty();
			return;
		}

		var self = this;
		var listHtml = '';
		var bounds = [];

		bureaux.forEach( function ( bureau ) {
			listHtml += self.buildCardHtml( bureau );

			if ( bureau.lat && bureau.lng ) {
				var marker = L.marker( [ bureau.lat, bureau.lng ] ).addTo( self.markersLayer );
				marker.bindPopup( self.buildPopupHtml( bureau ) );
				marker.on( 'click', function () {
					self.highlightCard( bureau.id );
				} );
				self.markers[ bureau.id ] = marker;
				bounds.push( [ bureau.lat, bureau.lng ] );
			}
		} );

		this.listEl.innerHTML = listHtml;

		if ( bounds.length > 1 ) {
			this.map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: this.settings.zoomRegion } );
		} else if ( 1 === bounds.length ) {
			this.map.setView( bounds[ 0 ], this.settings.zoomDepartement );
		}

		this.bindCardEvents();
	};

	CsolLocatorInstance.prototype.renderEmpty = function () {
		this.listEl.innerHTML = '<p class="csol-list-empty">' + escapeHtml( csolLocator.i18n.noResults ) + '</p>';
		this.countEl.textContent = '';
	};

	CsolLocatorInstance.prototype.buildPopupHtml = function ( bureau ) {
		var html = '<div class="csol-popup">';
		html += '<strong>' + escapeHtml( bureau.title ) + '</strong><br>';
		if ( bureau.adresse ) {
			html += escapeHtml( bureau.adresse ) + '<br>';
		}
		if ( bureau.ville ) {
			html += escapeHtml( bureau.code_postal ) + ' ' + escapeHtml( bureau.ville );
		}
		html += '</div>';
		return html;
	};

	CsolLocatorInstance.prototype.buildCardHtml = function ( bureau ) {
		var produitsHtml = '';
		if ( bureau.produits && bureau.produits.length ) {
			produitsHtml = '<div class="csol-card-produits">';
			bureau.produits.forEach( function ( produit ) {
				produitsHtml += '<span class="csol-badge-produit">' + escapeHtml( produit.name ) + '</span>';
			} );
			produitsHtml += '</div>';
		}

		var siteHtml = '';
		if ( bureau.site ) {
			siteHtml = '<a href="' + escapeAttr( bureau.site ) + '" class="csol-card-link" target="_blank" rel="noopener noreferrer">' + escapeHtml( bureau.site ) + '</a>';
		}

		var telHtml = '';
		if ( bureau.telephone ) {
			telHtml = '<a href="tel:' + escapeAttr( bureau.telephone.replace( /\s+/g, '' ) ) + '" class="csol-card-tel">' + escapeHtml( bureau.telephone ) + '</a>';
		}

		var directionsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent( bureau.lat + ',' + bureau.lng );

		return (
			'<article class="csol-card" id="' + this.id + '-card-' + bureau.id + '" data-bureau-id="' + bureau.id + '">' +
				'<h3 class="csol-card-title">' + escapeHtml( bureau.title ) + '</h3>' +
				'<p class="csol-card-location">' + escapeHtml( bureau.ville ) + ( bureau.region ? ' &middot; ' + escapeHtml( bureau.region ) : '' ) + '</p>' +
				( bureau.adresse ? '<p class="csol-card-adresse">' + escapeHtml( bureau.adresse ) + '</p>' : '' ) +
				( telHtml ? '<p class="csol-card-tel-row">' + telHtml + '</p>' : '' ) +
				( siteHtml ? '<p class="csol-card-site-row">' + siteHtml + '</p>' : '' ) +
				produitsHtml +
				'<div class="csol-card-actions">' +
					'<button type="button" class="csol-btn csol-btn-secondary csol-card-view" data-bureau-id="' + bureau.id + '">' + escapeHtml( csolLocator.i18n.viewOnMap ) + '</button>' +
					'<a href="' + escapeAttr( directionsUrl ) + '" class="csol-btn csol-btn-primary" target="_blank" rel="noopener noreferrer">' + escapeHtml( csolLocator.i18n.directions ) + '</a>' +
				'</div>' +
			'</article>'
		);
	};

	CsolLocatorInstance.prototype.bindCardEvents = function () {
		var self = this;

		this.listEl.querySelectorAll( '.csol-card-view' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var bureauId = button.getAttribute( 'data-bureau-id' );
				self.focusBureau( bureauId );
			} );
		} );
	};

	CsolLocatorInstance.prototype.focusBureau = function ( bureauId ) {
		var marker = this.markers[ bureauId ];
		if ( ! marker ) {
			return;
		}

		this.map.flyTo( marker.getLatLng(), this.settings.zoomBureau );
		marker.openPopup();
		this.highlightCard( bureauId );
	};

	CsolLocatorInstance.prototype.highlightCard = function ( bureauId ) {
		if ( this.activeCardId ) {
			var previous = document.getElementById( this.id + '-card-' + this.activeCardId );
			if ( previous ) {
				previous.classList.remove( 'csol-card-active' );
			}
		}

		var card = document.getElementById( this.id + '-card-' + bureauId );
		if ( card ) {
			card.classList.add( 'csol-card-active' );
			card.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}

		this.activeCardId = bureauId;
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.csol-locator' ).forEach( function ( root ) {
			new CsolLocatorInstance( root );
		} );
	} );
} )();
