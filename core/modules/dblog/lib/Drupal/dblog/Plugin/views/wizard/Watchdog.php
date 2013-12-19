<?php

/**
 * @file
 * Contains \Drupal\dblog\Plugin\views\wizard\Watchdog.
 */

namespace Drupal\dblog\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the watchdog table.
 *
 * @ViewsWizard(
 *   id = "watchdog",
 *   module = "dblog",
 *   base_table = "watchdog",
 *   title = @Translation("Log entries")
 * )
 */
class Watchdog extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'timestamp';

}
