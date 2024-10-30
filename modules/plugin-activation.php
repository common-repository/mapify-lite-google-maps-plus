<?php
function mpfy_activate_flush() {
	flush_rewrite_rules();
}

function mpfy_activate() {
	add_action('wp', 'mpfy_activate_flush');
}
register_activation_hook(MAPIFY_PLUGIN_FILE, 'mpfy_activate');

function mpfy_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook(MAPIFY_PLUGIN_FILE, 'mpfy_deactivate');