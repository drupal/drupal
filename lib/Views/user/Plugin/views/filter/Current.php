<?php

/**
 * @file
 * Definition of views_handler_filter_user_current.
 */

namespace Views\user\Plugin\views\filter;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Filter handler for the current user.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "user_current",
 *   module = "user"
 * )
 */
class Current extends BooleanOperator {

  /**
   * Constructs a Current object.
   */
  public function __construct(array $configuration, $plugin_id, DiscoveryInterface $discovery) {
    parent::__construct($configuration, $plugin_id, $discovery);

    $this->value_value = t('Is the logged in user');
  }

  public function query() {
    $this->ensureMyTable();

    $field = $this->tableAlias . '.' . $this->realField . ' ';
    $or = db_or();

    if (empty($this->value)) {
      $or->condition($field, '***CURRENT_USER***', '<>');
      if ($this->accept_null) {
        $or->isNull($field);
      }
    }
    else {
      $or->condition($field, '***CURRENT_USER***', '=');
    }
    $this->query->add_where($this->options['group'], $or);
  }

}
