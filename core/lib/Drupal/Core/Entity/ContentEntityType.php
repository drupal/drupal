<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityType.
 */

namespace Drupal\Core\Entity;

/**
 * Provides an implementation of a content entity type and its metadata.
 */
class ContentEntityType extends EntityType implements ContentEntityTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($definition) {
    parent::__construct($definition);
    $this->handlers += array(
      'storage' => 'Drupal\Core\Entity\Sql\SqlContentEntityStorage',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return 'content';
  }

}
