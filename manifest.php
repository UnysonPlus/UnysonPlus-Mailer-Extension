<?php if (!defined('FW')) die('Forbidden');

$manifest = array();

$manifest['name']        = __( 'Mailer', 'fw' );
$manifest['slug']        = 'unysonplus-mailer';
$manifest['description'] = __(
        'This extension will let you set some global email options and it is used by other extensions (like Forms) to send emails.',
        'fw'
);

$manifest['version']     = '1.2.18';
$manifest['display']     = false;
$manifest['standalone']  = false;

// Repository Info
$manifest['github_update'] = 'UnysonPlus/UnysonPlus-Mailer-Extension';
$manifest['github_repo']   = 'https://github.com/UnysonPlus/UnysonPlus-Mailer-Extension';
$manifest['github_branch'] = 'master';

// Author Info
$manifest['author']     = 'UnysonPlus';
$manifest['author_uri'] = 'https://www.lastimosa.com.ph/unysonplus';

// Meta
$manifest['license']      = 'GPL-2.0-or-later';
$manifest['text_domain']  = 'fw';
$manifest['requires_php'] = '7.4';
$manifest['requires_wp']  = '5.8';

/**
 * Changelog
 * -----------------------------------------------------------------------------
 * 1.2.16 - Security: added CSRF nonce protection to the
 *          fw_ext_mailer_test_connection AJAX endpoint. JS now posts a nonce
 *          generated via wp_create_nonce; PHP handler verifies it via
 *          check_ajax_referer. Blocks CSRF-driven test-email sends from an
 *          authenticated admin's session.
 */
