<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Annotation\ConfigEntityType.
 */

namespace Drupal\Core\Entity\Annotation;

/**
 * Defines a config entity type annotation object.
 *
 * @Annotation
 */
class ConfigEntityType extends EntityType {

  /**
   * {@inheritdoc}
   */
  public $entity_type_class = 'Drupal\Core\Config\Entity\ConfigEntityType';

}
