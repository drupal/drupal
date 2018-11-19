<?php

namespace Drupal\Core\Field\Plugin\migrate\field;

use Drupal\field\Plugin\migrate\field\Email as EmailNew;

/**
 * MigrateField Plugin for Drupal 6 and 7 email fields.
 *
 * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use
 *   \Drupal\field\Plugin\migrate\field\Email instead.
 *
 * @see https://www.drupal.org/node/3009286
 */
class Email extends EmailNew {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__NAMESPACE__ . '\Email is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\field\Plugin\migrate\field\Email instead. See https://www.drupal.org/node/3009286', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
