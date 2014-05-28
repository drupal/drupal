<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\ProcessPluginBase.
 */

namespace Drupal\migrate;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * The base class for all migrate process plugins.
 *
 * Migrate process plugins are taking a value and transform them. For example,
 * transform a human provided name into a machine name, look up an identifier
 * in a previous migration and so on.
 *
 * @see https://drupal.org/node/2129651
 */
abstract class ProcessPluginBase extends PluginBase implements MigrateProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

}
