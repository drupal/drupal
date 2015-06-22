<?php

/**
 * @file
 * Contains \Drupal\Core\Composer\Composer.
 */

namespace Drupal\Core\Composer;

use Drupal\Component\PhpStorage\FileStorage;
use Composer\Script\Event;

/**
 * Provides static functions for composer script events.
 *
 * @see https://getcomposer.org/doc/articles/scripts.md
 */
class Composer {

  /**
   * Add vendor classes to composers static classmap.
   */
  public static function preAutoloadDump(Event $event) {
    $composer = $event->getComposer();
    $package = $composer->getPackage();
    $autoload = $package->getAutoload();
    $autoload['classmap'] = array_merge($autoload['classmap'], array(
      'vendor/symfony/http-foundation/Request.php',
      'vendor/symfony/http-foundation/ParameterBag.php',
      'vendor/symfony/http-foundation/FileBag.php',
      'vendor/symfony/http-foundation/ServerBag.php',
      'vendor/symfony/http-foundation/HeaderBag.php',
      'vendor/symfony/http-kernel/HttpKernel.php',
      'vendor/symfony/http-kernel/HttpKernelInterface.php',
      'vendor/symfony/http-kernel/TerminableInterface.php',
    ));
    $package->setAutoload($autoload);
  }

  /**
   * Ensures that .htaccess and web.config files are present in Composer root.
   *
   * @param \Composer\Script\Event $event
   */
  public static function ensureHtaccess(Event $event) {

    // The current working directory for composer scripts is where you run
    // composer from.
    $vendor_dir = $event->getComposer()->getConfig()->get('vendor-dir');

    // Prevent access to vendor directory on Apache servers.
    $htaccess_file = $vendor_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
      file_put_contents($htaccess_file, FileStorage::htaccessLines(TRUE) . "\n");
    }

    // Prevent access to vendor directory on IIS servers.
    $webconfig_file = $vendor_dir . '/web.config';
    if (!file_exists($webconfig_file)) {
      $lines = <<<EOT
<configuration>
  <system.webServer>
    <authorization>
      <deny users="*">
    </authorization>
  </system.webServer>
</configuration>
EOT;
      file_put_contents($webconfig_file, $lines . "\n");
    }
  }

}
