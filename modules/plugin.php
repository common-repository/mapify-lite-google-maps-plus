<?php
include_once('plugin-activation.php');
include_once('map-settings/map-settings.php');

// Includes the Carbon fields framework
include_once('carbon.php');

// Provides on-the-fly thumbnails
include_once('wpthumb.php');

// Provides an ajax service for map settings
include_once('service-map-settings.php');

// Overrides Open Graph meta when viewing single map locations
include_once('og-meta.php');

// Overrides the comments form when viewing it in the Mapify popup
include_once('comments.php');

// Groups Mapify menu items into a single menu
include_once('admin-menu-grouping.php');

// Handles the plugin data update process
include_once('updater.php');

// Enable location popup
include_once('map-location-popup.php');

/* MapifyLite */
include_once('plugin-lite.php');
