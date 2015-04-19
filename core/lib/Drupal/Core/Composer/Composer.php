<?php

/**
 * @file
 * Contains \Drupal\Core\Composer\Composer.
 */

namespace Drupal\Core\Composer;

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
      'vendor/symfony/http-foundation/Symfony/Component/HttpFoundation/Request.php',
      'vendor/symfony/http-foundation/Symfony/Component/HttpFoundation/ParameterBag.php',
      'vendor/symfony/http-foundation/Symfony/Component/HttpFoundation/FileBag.php',
      'vendor/symfony/http-foundation/Symfony/Component/HttpFoundation/ServerBag.php',
      'vendor/symfony/http-foundation/Symfony/Component/HttpFoundation/HeaderBag.php',
      'vendor/symfony/http-kernel/Symfony/Component/HttpKernel/HttpKernel.php',
      'vendor/symfony/http-kernel/Symfony/Component/HttpKernel/HttpKernelInterface.php',
      'vendor/symfony/http-kernel/Symfony/Component/HttpKernel/TerminableInterface.php'
    ));
    $package->setAutoload($autoload);
  }

}
