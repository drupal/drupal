<?php

namespace Drupal\media_library;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The media library opener for field widgets.
 *
 * @internal
 *   This service is an internal part of Media Library's field widget.
 */
class MediaLibraryFieldWidgetOpener implements MediaLibraryOpenerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * MediaLibraryFieldWidgetOpener constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(MediaLibraryState $state, AccountInterface $account) {
    $parameters = $state->getOpenerParameters() + ['entity_id' => NULL];

    $process_result = function ($result) {
      if ($result instanceof RefinableCacheableDependencyInterface) {
        $result->addCacheContexts(['url.query_args']);
      }
      return $result;
    };

    // Forbid access if any of the required parameters are missing.
    foreach (['entity_type_id', 'bundle', 'field_name'] as $key) {
      if (empty($parameters[$key])) {
        return $process_result(AccessResult::forbidden("$key parameter is missing."));
      }
    }

    $entity_type_id = $parameters['entity_type_id'];
    $bundle = $parameters['bundle'];
    $field_name = $parameters['field_name'];

    // Since we defer to a field to determine access, ensure we are dealing with
    // a fieldable entity type.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
      throw new \LogicException("The media library can only be opened by fieldable entities.");
    }

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $access_handler = $this->entityTypeManager->getAccessControlHandler($entity_type_id);

    if ($parameters['entity_id']) {
      $entity = $storage->load($parameters['entity_id']);
      $entity_access = $access_handler->access($entity, 'update', $account, TRUE);
    }
    else {
      $entity_access = $access_handler->createAccess($bundle, $account, [], TRUE);
    }

    // If entity-level access is denied, there's no point in continuing.
    if (!$entity_access->isAllowed()) {
      return $process_result($entity_access);
    }

    // If the entity has not been loaded, create it in memory now.
    if (!isset($entity)) {
      $values = [];
      if ($bundle_key = $entity_type->getKey('bundle')) {
        $values[$bundle_key] = $bundle;
      }
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      $entity = $storage->create($values);
    }

    $items = $entity->get($field_name);
    $field_definition = $items->getFieldDefinition();

    if ($field_definition->getType() !== 'entity_reference') {
      throw new \LogicException('Expected the media library to be opened by an entity reference field.');
    }
    if ($field_definition->getFieldStorageDefinition()->getSetting('target_type') !== 'media') {
      throw new \LogicException('Expected the media library to be opened by an entity reference field that target media items.');
    }

    $field_access = $access_handler->fieldAccess('edit', $field_definition, $account, $items, TRUE);
    return $process_result($entity_access->andIf($field_access));
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionResponse(MediaLibraryState $state, array $selected_ids) {
    $response = new AjaxResponse();

    $parameters = $state->getOpenerParameters();
    if (empty($parameters['field_widget_id'])) {
      throw new \InvalidArgumentException('field_widget_id parameter is missing.');
    }

    // Create a comma-separated list of media IDs, insert them in the hidden
    // field of the widget, and trigger the field update via the hidden submit
    // button.
    $widget_id = $parameters['field_widget_id'];
    $ids = implode(',', $selected_ids);
    $response
      ->addCommand(new InvokeCommand("[data-media-library-widget-value=\"$widget_id\"]", 'val', [$ids]))
      ->addCommand(new InvokeCommand("[data-media-library-widget-update=\"$widget_id\"]", 'trigger', ['mousedown']));

    return $response;
  }

}
