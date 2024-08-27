<?php

declare(strict_types=1);

namespace Drupal\Core\Command;

use Drupal\Core\DrupalKernel;
use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains helper methods for console commands that boot up Drupal.
 */
trait BootableCommandTrait {

  /**
   * The class loader.
   *
   * @var object
   */
  protected object $classLoader;

  /**
   * Boots up a Drupal environment.
   *
   * @return \Drupal\Core\DrupalKernelInterface
   *   The Drupal kernel.
   *
   * @throws \Exception
   *   Exception thrown if kernel does not boot.
   */
  protected function boot(): DrupalKernelInterface {
    $kernel = new DrupalKernel('prod', $this->classLoader);
    $kernel::bootEnvironment();
    $kernel->setSitePath($this->getSitePath());
    Settings::initialize($kernel->getAppRoot(), $kernel->getSitePath(), $this->classLoader);
    $kernel->boot();
    $kernel->preHandle(Request::createFromGlobals());
    return $kernel;
  }

  /**
   * Gets the site path.
   *
   * Defaults to 'sites/default'. For testing purposes this can be overridden
   * using the DRUPAL_DEV_SITE_PATH environment variable.
   *
   * @return string
   *   The site path to use.
   */
  protected function getSitePath(): string {
    return getenv('DRUPAL_DEV_SITE_PATH') ?: 'sites/default';
  }

}
