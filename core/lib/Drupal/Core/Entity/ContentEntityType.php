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
   * The required revision metadata keys.
   *
   * This property should only be filled in the constructor. This ensures that
   * only new instances get newly added required revision metadata keys.
   * Unserialized objects will only retrieve the keys that they already have
   * been cached with.
   *
   * @var array
   */
  protected $requiredRevisionMetadataKeys = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($definition) {
    parent::__construct($definition);

    $this->handlers += [
      'storage' => 'Drupal\Core\Entity\Sql\SqlContentEntityStorage',
      'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    ];

    // Only new instances should provide the required revision metadata keys.
    // The cached instances should return only what already has been stored
    // under the property $revision_metadata_keys. The BC layer in
    // ::getRevisionMetadataKeys() has to detect if the revision metadata keys
    // have been provided by the entity type annotation, therefore we add keys
    // to the property $requiredRevisionMetadataKeys only if those keys aren't
    // set in the entity type annotation.
    if (!isset($this->revision_metadata_keys['revision_default'])) {
      $this->requiredRevisionMetadataKeys['revision_default'] = 'revision_default';
    }

    // Add the required revision metadata fields here instead in the getter
    // method, so that they are serialized as part of the object even if the
    // getter method doesn't get called. This allows the list to be further
    // extended. Only new instances of the class will contain the new list,
    // while the cached instances contain the previous version of the list.
    $this->revision_metadata_keys += $this->requiredRevisionMetadataKeys;
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
    if ((!$this->revision_metadata_keys || ($this->revision_metadata_keys == $this->requiredRevisionMetadataKeys)) && $include_backwards_compatibility_field_names) {
      $base_fields = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions($this->id());
      if ((isset($base_fields['revision_uid']) && $revision_user = 'revision_uid') || (isset($base_fields['revision_user']) && $revision_user = 'revision_user')) {
        @trigger_error('The revision_user revision metadata key is not set for entity type: ' . $this->id . ' See: https://www.drupal.org/node/2831499', E_USER_DEPRECATED);
        $this->revision_metadata_keys['revision_user'] = $revision_user;
      }
      if ((isset($base_fields['revision_timestamp']) && $revision_timestamp = 'revision_timestamp') || (isset($base_fields['revision_created'])) && $revision_timestamp = 'revision_created') {
        @trigger_error('The revision_created revision metadata key is not set for entity type: ' . $this->id . ' See: https://www.drupal.org/node/2831499', E_USER_DEPRECATED);
        $this->revision_metadata_keys['revision_created'] = $revision_timestamp;
      }
      if ((isset($base_fields['revision_log']) && $revision_log = 'revision_log') || (isset($base_fields['revision_log_message']) && $revision_log = 'revision_log_message')) {
        @trigger_error('The revision_log_message revision metadata key is not set for entity type: ' . $this->id . ' See: https://www.drupal.org/node/2831499', E_USER_DEPRECATED);
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
