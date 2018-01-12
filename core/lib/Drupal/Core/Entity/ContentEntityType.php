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
  public function getRevisionMetadataKeys($include_backwards_compatibility_field_names = TRUE) {
    // Provide backwards compatibility in case the revision metadata keys are
    // not defined in the entity annotation.
    if (!$this->revision_metadata_keys && $include_backwards_compatibility_field_names) {
      $base_fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($this->id());
      if ((isset($base_fields['revision_uid']) && $revision_user = 'revision_uid') || (isset($base_fields['revision_user']) && $revision_user = 'revision_user')) {
        @trigger_error('The revision_user revision metadata key is not set.', E_USER_DEPRECATED);
        $this->revision_metadata_keys['revision_user'] = $revision_user;
      }
      if ((isset($base_fields['revision_timestamp']) && $revision_timestamp = 'revision_timestamp') || (isset($base_fields['revision_created'])) && $revision_timestamp = 'revision_created') {
        @trigger_error('The revision_created revision metadata key is not set.', E_USER_DEPRECATED);
        $this->revision_metadata_keys['revision_created'] = $revision_timestamp;
      }
      if ((isset($base_fields['revision_log']) && $revision_log = 'revision_log') || (isset($base_fields['revision_log_message']) && $revision_log = 'revision_log_message')) {
        @trigger_error('The revision_log_message revision metadata key is not set.', E_USER_DEPRECATED);
        $this->revision_metadata_keys['revision_log_message'] = $revision_log;
      }
    }
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

}
