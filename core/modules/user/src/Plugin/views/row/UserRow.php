<?php

namespace Drupal\user\Plugin\views\row;

use Drupal\views\Attribute\ViewsRow;
use Drupal\views\Plugin\views\row\EntityRow;

/**
 * A row plugin which renders a user.
 *
 * @ingroup views_row_plugins
 */
#[ViewsRow("entity:user")]
class UserRow extends EntityRow {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode']['default'] = 'full';

    return $options;
  }

}
