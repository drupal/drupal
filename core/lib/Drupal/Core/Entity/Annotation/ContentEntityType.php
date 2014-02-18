<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Annotation\ContentEntityType.
 */

namespace Drupal\Core\Entity\Annotation;

/**
 * Defines a content entity type annotation object.
 *
 * @Annotation
 */
class ContentEntityType extends EntityType {

  /**
   * {@inheritdoc}
   */
  public $entity_type_class = 'Drupal\Core\Entity\ContentEntityType';

}
