<?php

namespace Drupal\Core\Entity;

/**
 * Provides an implementation of a content entity type and its metadata.
 */
class ContentEntityType extends EntityType implements ContentEntityTypeInterface {

  /**
   * An array of entity revision metadata keys.
   *
   * @var array
   */
  protected $revision_metadata_keys = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($definition) {
    parent::__construct($definition);

    $this->handlers += [
      'storage' => 'Drupal\Core\Entity\Sql\SqlContentEntityStorage',
      'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    ];

    $this->revision_metadata_keys += [
      'revision_default' => 'revision_default',
    ];
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

  /**
   * {@inheritdoc}
   */
  public function getRevisionMetadataKeys() {
    return $this->revision_metadata_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionMetadataKey($key) {
    $keys = $this->getRevisionMetadataKeys();
    return isset($keys[$key]) ? $keys[$key] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRevisionMetadataKey($key) {
    $keys = $this->getRevisionMetadataKeys();
    return isset($keys[$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionMetadataKey($key, $field_name) {
    if ($field_name !== NULL) {
      $this->revision_metadata_keys[$key] = $field_name;
    }
    else {
      unset($this->revision_metadata_keys[$key]);
    }
    return $this;
  }

}
