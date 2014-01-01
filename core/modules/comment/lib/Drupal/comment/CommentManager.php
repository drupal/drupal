<?php

/**
 * @file
 * Contains \Drupal\comment\CommentManager.
 */

namespace Drupal\comment;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\field\FieldInfo;

/**
 * Comment manager contains common functions to manage comment fields.
 */
class CommentManager implements CommentManagerInterface {

  /**
   * The field info service.
   *
   * @var \Drupal\field\FieldInfo
   */
  protected $fieldInfo;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Construct the CommentManager object.
   *
   * @param \Drupal\field\FieldInfo $field_info
   *   The field info service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(FieldInfo $field_info, EntityManagerInterface $entity_manager) {
    $this->fieldInfo = $field_info;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntityUri(CommentInterface $comment) {
    return $this->entityManager
      ->getStorageController($comment->entity_type->value)
      ->load($comment->entity_id->value)
      ->uri();
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($entity_type) {
    $info = $this->entityManager->getDefinition($entity_type);
    if (!$info->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      return array();
    }

    $map = $this->getAllFields();
    return isset($map[$entity_type]) ? $map[$entity_type] : array();
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getFieldUIPageTitle($commented_entity_type, $field_name) {
    $field_info = $this->fieldInfo->getField($commented_entity_type, $field_name);
    $bundles = $field_info->getBundles();
    $sample_bundle = reset($bundles);
    $sample_instance = $this->fieldInfo->getInstance($commented_entity_type, $sample_bundle, $field_name);
    return String::checkPlain($sample_instance->label);
  }

}
