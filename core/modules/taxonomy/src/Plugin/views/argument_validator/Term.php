<?php

namespace Drupal\taxonomy\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Adds legacy vocabulary handling to standard Entity Argument validation.
 *
 * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
 *   \Drupal\views\Plugin\views\argument_validator\Entity instead.
 *
 * @see https://www.drupal.org/node/3221870
 */
class Term extends Entity {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    @trigger_error('The ' . __NAMESPACE__ . '\Term is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\views\Plugin\views\argument_validator\Entity instead. See https://www.drupal.org/node/3221870', E_USER_DEPRECATED);

    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_type_bundle_info);
  }

}
