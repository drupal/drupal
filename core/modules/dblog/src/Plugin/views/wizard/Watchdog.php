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

  /**
   * {@inheritdoc}
   */
  protected function defaultDisplayOptions() {
    $display_options = parent::defaultDisplayOptions();

    // Add permission-based access control.
    $display_options['access']['type'] = 'perm';
    $display_options['access']['options']['perm'] = 'access site reports';

    return $display_options;
  }

}
