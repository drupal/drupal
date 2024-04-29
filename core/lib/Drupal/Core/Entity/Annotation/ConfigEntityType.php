<?php

namespace Drupal\Core\Entity\Annotation;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a config entity type annotation object.
 *
 * The annotation properties of entity types are found on
 * \Drupal\Core\Config\Entity\ConfigEntityType and are accessed using
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
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $entity_type_class = 'Drupal\Core\Config\Entity\ConfigEntityType';

  /**
   * {@inheritdoc}
   */
  public $group = 'configuration';

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->definition['group_label'] = new TranslatableMarkup('Configuration', [], ['context' => 'Entity type group']);

    return parent::get();
  }

}
