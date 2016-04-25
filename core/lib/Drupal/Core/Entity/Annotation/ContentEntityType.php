<?php

namespace Drupal\Core\Entity\Annotation;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a content entity type annotation object.
 *
 * Content Entity type plugins use an object-based annotation method, rather than an
 * array-type annotation method (as commonly used on other annotation types).
 * The annotation properties of content entity types are found on
 * \Drupal\Core\Entity\ContentEntityType and are accessed using
 * get/set methods defined in \Drupal\Core\Entity\ContentEntityTypeInterface.
 *
 * @ingroup entity_api
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
    $this->definition['group_label'] = new TranslatableMarkup('Content', array(), array('context' => 'Entity type group'));

    return parent::get();
  }

}
