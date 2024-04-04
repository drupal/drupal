<?php

namespace Drupal\dblog\Plugin\views\wizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsWizard;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Defines a wizard for the watchdog table.
 */
#[ViewsWizard(
  id: 'watchdog',
  title: new TranslatableMarkup('Log entries'),
  base_table: 'watchdog'
)]
class Watchdog extends WizardPluginBase {

  /**
   * Set the created column.
   *
   * @var string
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
