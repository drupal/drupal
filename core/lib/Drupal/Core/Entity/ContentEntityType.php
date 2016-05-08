<?php

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
      'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return 'content';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   *   If the provided class does not implement
   *   \Drupal\Core\Entity\ContentEntityStorageInterface.
   *
   * @see \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected function checkStorageClass($class) {
    $required_interface = ContentEntityStorageInterface::class;
    if (!is_subclass_of($class, $required_interface)) {
      throw new \InvalidArgumentException("$class does not implement $required_interface");
    }
  }

}
