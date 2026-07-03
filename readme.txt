=== ChamberSign Office Locator ===
Contributors: yellowtie
Tags: locator, map, leaflet, openstreetmap, certificat
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Localisateur de bureaux d'enregistrement ChamberSign, avec carte OpenStreetMap/Leaflet, recherche AJAX et gestion des bureaux/produits.

== Description ==

ChamberSign Office Locator permet aux visiteurs du site ChamberSign de trouver le bureau d'enregistrement le plus proche pour retirer leur certificat électronique.

Fonctionnalités :

* Custom Post Type "Bureaux d'enregistrement" avec coordonnées GPS, coordonnées de contact et statut actif/inactif.
* Taxonomie "Produits" (RGS, eIDAS, Signature Électronique, Cachet Serveur…) en relation many-to-many avec les bureaux.
* Carte interactive OpenStreetMap (Leaflet), sans dépendance à Google Maps.
* Recherche AJAX (texte libre, région, département, produit) sans rechargement de page.
* Import/mise à jour en masse des bureaux depuis un fichier CSV ou XLSX.
* Aide au géocodage (Nominatim/OpenStreetMap) dans l'administration.
* Réglages de la carte (position, zooms par défaut, fond de carte, icône du marqueur) via l'API Settings de WordPress.
* Design conforme à la charte graphique ChamberSign (couleurs, typographie Poppins), responsive mobile/tablette.
* Compatible Elementor via le widget Shortcode.

== Installation ==

1. Copiez le dossier `chambersign-office-locator` dans `wp-content/plugins/`, ou installez le fichier ZIP depuis Extensions > Ajouter.
2. Activez le plugin.
3. Dans le menu "ChamberSign Locator", ajoutez vos bureaux d'enregistrement (ou importez-les via l'écran Import).
4. Assignez les produits proposés par chaque bureau.
5. Ajustez éventuellement les réglages de la carte dans "ChamberSign Locator > Réglages".
6. Insérez le shortcode `[chambersign_locator]` sur la page de votre choix (éditeur classique, éditeur de blocs, ou widget Shortcode Elementor).

== Shortcode ==

`[chambersign_locator]`

Aucun attribut requis : le shortcode affiche la carte et la liste des bureaux actifs, avec les filtres de recherche.

== Import CSV/XLSX ==

Colonnes reconnues : Région, Nom du bureau, Département, Ville, Code postal, Adresse, Téléphone, Site internet, Horaires, Latitude, Longitude, Produits (séparés par une virgule ou un "|").

Un bureau existant portant le même nom est mis à jour ; sinon un nouveau bureau est créé.

== Changelog ==

= 1.1.0 =
* Ajout d'un sélecteur de fond de carte (OpenStreetMap, CartoDB Voyager/Positron/Dark Matter).
* Ajout de l'upload d'une icône de marqueur personnalisée (SVG) dans les réglages.
* Cartes bureau plus compactes dans la liste des résultats.

= 1.0.0 =
* Version initiale.

== Crédits ==

Développé par YellowTie pour ChamberSign.
