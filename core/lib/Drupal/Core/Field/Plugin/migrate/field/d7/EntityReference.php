<?php

namespace Drupal\Core\Field\Plugin\migrate\field\d7;

use Drupal\field\Plugin\migrate\field\d7\EntityReference as EntityReferenceNew;

/**
 * MigrateField plugin for Drupal 7 entity_reference fields.
 *
 * @deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use
 *   \Drupal\field\Plugin\migrate\field\d7\EntityReference instead.
 *
 * @see https://www.drupal.org/node/3009286
 */
class EntityReference extends EntityReferenceNew {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__NAMESPACE__ . '\EntityReference is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\field\Plugin\migrate\field\d7\EntityReference instead. See https://www.drupal.org/node/3009286', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
