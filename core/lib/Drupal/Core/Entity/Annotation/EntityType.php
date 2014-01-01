<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Annotation\EntityType.
 */

namespace Drupal\Core\Entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Entity type annotation object.
 *
 * @Annotation
 */
class EntityType extends Plugin {

  /**
   * The class used to represent the entity type.
   *
   * It must implement \Drupal\Core\Entity\EntityTypeInterface.
   *
   * @var string
   */
  public $entity_type_class = 'Drupal\Core\Entity\EntityType';

  /**
   * @todo content_translation_entity_info_alter() uses this but it is
   *   undocumented. Fix in https://drupal.org/node/1968970.
   *
   * @var array
   */
  public $translation = array();

  /**
   * {@inheritdoc}
   */
  public function get() {
    $values = $this->definition;

    // Use the specified entity type class, and remove it before instantiating.
    $class = $values['entity_type_class'];
    unset($values['entity_type_class']);

    return new $class($values);
  }

}
