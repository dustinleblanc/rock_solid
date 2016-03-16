<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

if (!isset($_ENV['PANTHEON_ENVIRONMENT'])) {
    $settings['hash_salt'] = 'YtvcheoGbURHzhaAADkcNGZPBSaK5RX1dIpRb0B-JsyJhjiBC3BB0jXJr9iEbFZgnSiIepw7ZA';
    $dotenv = new Dotenv\Dotenv('../');
    $dotenv->load();
    $databases['default']['default'] = [
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'driver' => getenv('DB_DRIVER'),
        'prefix' => getenv('DB_PREFIX'),
        'collation' => getenv('DB_COLLATION'),
    ];
}

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
    include $local_settings;
}
$databases['default']['default'] = array (
  'database' => 'drupal',
  'username' => 'drupal',
  'password' => 'drupal',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
$settings['install_profile'] = 'standard';
$config_directories['sync'] = 'sites/default/files/config_xyAfBUYrfPhyUD_frk_Iu4DO7fDih8OwN_oTIo_5Vh2SpTKj_xanvmQfMKiAe-d2B_30j7gLgw/sync';
