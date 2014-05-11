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

  /**
   * {@inheritdoc}
   */
  public $group = 'configuration';

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->definition['group_label'] = $this->t('Configuration', array(), array('context' => 'Entity type group'));

    return parent::get();
  }

}
