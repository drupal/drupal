<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\InstallerKernel.
 */

namespace Drupal\Core\Installer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * Extend DrupalKernel to handle force some kernel behaviors.
 */
class InstallerKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  protected function initializeContainer($rebuild = TRUE) {
    $container = parent::initializeContainer($rebuild);
    return $container;
  }

  /**
   * {@inheritdoc}
   */
  protected function dumpDrupalContainer(ContainerBuilder $container, $baseClass) {
    if (Settings::get('hash_salt')) {
      return parent::dumpDrupalContainer($container, $baseClass);
    }
  }
}
