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

  /**
   * {@inheritdoc}
   */
  public $group = 'content';

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->definition['group_label'] = $this->t('Content', array(), array('context' => 'Entity type group'));

    return parent::get();
  }

}
