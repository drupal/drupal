<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewTestConfigInstaller.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Config\ConfigImporter;

/**
 * Defines a configuration installer.
 *
 * A config installer imports test views for views testing.
 *
 * @see \Drupal\Core\Config\ConfigImporter
 * @see \Drupal\views\Tests\ViewTestData
 */
class ViewTestConfigInstaller extends ConfigImporter {

  /**
   * The name used to identify events and the lock.
   */
  const ID = 'views.test.installer';

}
