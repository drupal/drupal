<?php

namespace Drupal\comment\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;

/**
 * Provides common functionality for the Comment test classes.
 */
trait CommentTestTrait {

  /**
   * Adds the default comment field to an entity.
   *
   * Attaches a comment field named 'comment' to the given entity type and
   * bundle. Largely replicates the default behavior in Drupal 7 and earlier.
   *
   * @param string $entity_type
   *   The entity type to attach the default comment field to.
   * @param string $bundle
   *   The bundle to attach the default comment field to.
   * @param string $field_name
   *   (optional) Field name to use for the comment field. Defaults to
   *     'comment'.
   * @param int $default_value
   *   (optional) Default value, one of CommentItemInterface::HIDDEN,
   *   CommentItemInterface::OPEN, CommentItemInterface::CLOSED. Defaults to
   *   CommentItemInterface::OPEN.
   * @param string $comment_type_id
   *   (optional) ID of comment type to use. Defaults to 'comment'.
   * @param string $comment_view_mode
   *   (optional) The comment view mode to be used in comment field formatter.
   *   Defaults to 'full'.
   */
  public function addDefaultCommentField($entity_type, $bundle, $field_name = 'comment', $default_value = CommentItemInterface::OPEN, $comment_type_id = 'comment', $comment_view_mode = 'full') {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_display_repository = \Drupal::service('entity_display.repository');
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');
    // Create the comment type if needed.
    $comment_type_storage = $entity_type_manager->getStorage('comment_type');
    if ($comment_type = $comment_type_storage->load($comment_type_id)) {
      if ($comment_type->getTargetEntityTypeId() !== $entity_type) {
        throw new \InvalidArgumentException("The given comment type id $comment_type_id can only be used with the $entity_type entity type");
      }
    }
    else {
      $comment_type_storage->create([
        'id' => $comment_type_id,
        'label' => Unicode::ucfirst($comment_type_id),
        'target_entity_type_id' => $entity_type,
        'description' => 'Default comment field',
      ])->save();
    }
    // Add a body field to the comment type.
    \Drupal::service('comment.manager')->addBodyField($comment_type_id);

    // Add a comment field to the host entity type. Create the field storage if
    // needed.
    if (!array_key_exists($field_name, $entity_field_manager->getFieldStorageDefinitions($entity_type))) {
      $entity_type_manager->getStorage('field_storage_config')->create([
        'entity_type' => $entity_type,
        'field_name' => $field_name,
        'type' => 'comment',
        'translatable' => TRUE,
        'settings' => [
          'comment_type' => $comment_type_id,
        ],
      ])->save();
    }
    // Create the field if needed, and configure its form and view displays.
    if (!array_key_exists($field_name, $entity_field_manager->getFieldDefinitions($entity_type, $bundle))) {
      $entity_type_manager->getStorage('field_config')->create([
        'label' => 'Comments',
        'description' => '',
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'required' => 1,
        'default_value' => [
          [
            'status' => $default_value,
            'cid' => 0,
            'last_comment_name' => '',
            'last_comment_timestamp' => 0,
            'last_comment_uid' => 0,
          ],
        ],
      ])->save();

      // Entity form displays: assign widget settings for the default form
      // mode, and hide the field in all other form modes.
      $entity_display_repository->getFormDisplay($entity_type, $bundle)
        ->setComponent($field_name, [
          'type' => 'comment_default',
          'weight' => 20,
        ])
        ->save();
      foreach ($entity_display_repository->getFormModes($entity_type) as $id => $form_mode) {
        $display = $entity_display_repository->getFormDisplay($entity_type, $bundle, $id);
        // Only update existing displays.
        if ($display && !$display->isNew()) {
          $display->removeComponent($field_name)->save();
        }
      }

      // Entity view displays: assign widget settings for the default view
      // mode, and hide the field in all other view modes.
      $entity_display_repository->getViewDisplay($entity_type, $bundle)
        ->setComponent($field_name, [
          'label' => 'above',
          'type' => 'comment_default',
          'weight' => 20,
          'settings' => ['view_mode' => $comment_view_mode],
        ])
        ->save();
      foreach ($entity_display_repository->getViewModes($entity_type) as $id => $view_mode) {
        $display = $entity_display_repository->getViewDisplay($entity_type, $bundle, $id);
        // Only update existing displays.
        if ($display && !$display->isNew()) {
          $display->removeComponent($field_name)->save();
        }
      }
    }
  }

}
