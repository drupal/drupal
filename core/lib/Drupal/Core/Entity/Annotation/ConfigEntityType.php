<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Annotation\ConfigEntityType.
 */

namespace Drupal\Core\Entity\Annotation;
use Drupal\Core\StringTranslation\TranslatableString;

/**
 * Defines a config entity type annotation object.
 *
 * Config Entity type plugins use an object-based annotation method, rather than an
 * array-type annotation method (as commonly used on other annotation types).
 * The annotation properties of entity types are found on
 * \Drupal\Core\Entity\ConfigEntityType and are accessed using
 * get/set methods defined in \Drupal\Core\Entity\EntityTypeInterface.
 *
 * @ingroup entity_api
 *
 * @Annotation
 */
class ConfigEntityType extends EntityType {

  /**
   * {@inheritdoc}
   */
  public $entity_type_class = 'Drupal\Core\Config\Entity\ConfigEntityType';

  /**
   * {@inheritdoc}
   */
  public $group = 'configuration';

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->definition['group_label'] = new TranslatableString('Configuration', array(), array('context' => 'Entity type group'));

    return parent::get();
  }

}
