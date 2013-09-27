<?php

/**
 * @file
 * Contains \Drupal\comment\CommentManager.
 */

namespace Drupal\comment;

use Drupal\Component\Utility\String;
use Drupal\field\FieldInfo;
use Drupal\Core\Entity\EntityManager;

/**
 * Comment manager contains common functions to manage comment fields.
 */
class CommentManager {

  /**
   * The field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Construct the CommentManager object.
   *
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   */
  public function __construct(FieldInfo $field_info, EntityManager $entity_manager) {
    $this->fieldInfo = $field_info;
    $this->entityManager = $entity_manager;
  }

  /**
   * Utility function to return URI of the comment's parent entity.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment entity.
   *
   * @return array
   *   An array returned by \Drupal\Core\Entity\EntityInterface::uri().
   */
  public function getParentEntityUri(CommentInterface $comment) {
    return $this->entityManager
      ->getStorageController($comment->entity_type->value)
      ->load($comment->entity_id->value)
      ->uri();
  }

  /**
   * Utility function to return an array of comment fields.
   *
   * @param string $entity_type
   *   The entity type to return fields which are attached on.
   *
   * @return array
   *   An array of comment field map definitions, keyed by field name. Each
   *   value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears, as an array with entity
   *     types as keys and the array of bundle names as values.
   *
   * @see field_info_field_map()
   */
  public function getFields($entity_type = NULL) {
    $map = $this->getAllFields();
    if (!isset($map[$entity_type])) {
      return array();
    }
    return $map[$entity_type];
  }

  /**
   * Utility function to return all comment fields.
   */
  public function getAllFields() {
    $map = $this->fieldInfo->getFieldMap();
    // Build a list of comment fields only.
    $comment_fields = array();
    foreach ($map as $entity_type => $data) {
      foreach ($data as $field_name => $field_info) {
        if ($field_info['type'] == 'comment') {
          $comment_fields[$entity_type][$field_name] = $field_info;
        }
      }
    }
    return $comment_fields;
  }

  /**
   * Utility method to add the default comment field to an entity.
   *
   * Attaches a comment field named 'comment' to the given entity type and
   * bundle. Largely replicates the default behaviour in Drupal 7 and earlier.
   *
   * @param string $entity_type
   *   The entity type to attach the default comment field to.
   * @param string $bundle
   *   The bundle to attach the default comment field instance to.
   * @param string $field_name
   *   (optional) Field name to use for the comment field. Defaults to 'comment'.
   * @param int $default_value
   *   (optional) Default value, one of COMMENT_HIDDEN, COMMENT_OPEN,
   *   COMMENT_CLOSED. Defaults to COMMENT_OPEN.
   */
  public function addDefaultField($entity_type, $bundle, $field_name = 'comment', $default_value = COMMENT_OPEN) {
    // Make sure the field doesn't already exist.
    if (!$this->fieldInfo->getField($entity_type, $field_name)) {
      // Add a default comment field for existing node comments.
      $field = $this->entityManager->getStorageController('field_entity')->create(array(
        'entity_type' => $entity_type,
        'name' => $field_name,
        'type' => 'comment',
        'translatable' => '0',
      ));
      // Create the field.
      $field->save();
    }
    // Make sure the instance doesn't already exist.
    if (!$this->fieldInfo->getInstance($entity_type, $bundle, $field_name)) {
      $instance = $this->entityManager->getStorageController('field_instance')->create(array(
        'label' => 'Comment settings',
        'description' => '',
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'required' => 1,
        'default_value' => array(
          array(
            'status' => $default_value,
            'cid' => 0,
            'last_comment_name' => '',
            'last_comment_timestamp' => 0,
            'last_comment_uid' => 0,
          ),
        ),
      ));
      $instance->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'comment_default',
          'weight' => 20,
        ))
        ->save();

      // Set default to display comment list.
      entity_get_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'label' => 'hidden',
          'type' => 'comment_default',
          'weight' => 20,
        ))
        ->save();
    }
    $this->addBodyField($entity_type, $field_name);
  }

  /**
   * Creates a comment_body field instance.
   *
   * @param string $entity_type
   *   The type of the entity to which the comment field attached.
   * @param string $field_name
   *   Name of the comment field to add comment_body field.
   */
  public function addBodyField($entity_type, $field_name) {
    // Create the field if needed.
    $field = $this->entityManager->getStorageController('field_entity')->load('comment.comment_body');
    if (!$field) {
      $field = $this->entityManager->getStorageController('field_entity')->create(array(
        'name' => 'comment_body',
        'type' => 'text_long',
        'entity_type' => 'comment',
      ));
      $field->save();
    }
    // Create the instance if needed, field name defaults to 'comment'.
    $comment_bundle = $entity_type . '__' . $field_name;
    $field_instance = $this->entityManager
      ->getStorageController('field_instance')
      ->load("comment.$comment_bundle.comment_body");
    if (!$field_instance) {
      // Attaches the body field by default.
      $field_instance = $this->entityManager->getStorageController('field_instance')->create(array(
        'field_name' => 'comment_body',
        'label' => 'Comment',
        'entity_type' => 'comment',
        'bundle' => $comment_bundle,
        'settings' => array('text_processing' => 1),
        'required' => TRUE,
      ));
      $field_instance->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('comment', $comment_bundle, 'default')
        ->setComponent('comment_body', array(
          'type' => 'text_textarea',
        ))
        ->save();

      // Assign display settings for the 'default' view mode.
      entity_get_display('comment', $comment_bundle, 'default')
        ->setComponent('comment_body', array(
          'label' => 'hidden',
          'type' => 'text_default',
          'weight' => 0,
        ))
        ->save();
    }
  }

  /**
   * Builds human readable page title for field_ui management screens.
   *
   * @param string $field_name
   *   The comment field for which the overview is to be displayed.
   *
   * @return string
   *   The human readable field name.
   */
  public function getFieldUIPageTitle($field_name) {
    list($entity_type, $field) = explode('__', $field_name, 2);
    $field_info = $this->fieldInfo->getField($entity_type, $field);
    $bundles = $field_info->getBundles();
    $sample_bundle = reset($bundles);
    $sample_instance = $this->fieldInfo->getInstance($entity_type, $sample_bundle, $field);
    return String::checkPlain($sample_instance->label);
  }

}
