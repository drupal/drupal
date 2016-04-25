<?php

namespace Drupal\Core\Field\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Parent plugin for entity reference formatters.
 */
abstract class EntityReferenceFormatterBase extends FormatterBase {

  /**
   * Returns the referenced entities for display.
   *
   * The method takes care of:
   * - checking entity access,
   * - placing the entities in the language expected for display.
   * It is thus strongly recommended that formatters use it in their
   * implementation of viewElements($items) rather than dealing with $items
   * directly.
   *
   * For each entity, the EntityReferenceItem by which the entity is referenced
   * is available in $entity->_referringItem. This is useful for field types
   * that store additional values next to the reference itself.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    $entities = array();

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = \Drupal::entityManager()->getTranslationFromContext($entity, $langcode);
        }

        $access = $this->checkAccess($entity);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          $entities[$delta] = $entity;
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   *
   * @see ::prepareView()
   * @see ::getEntitiestoView()
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);

    $field_level_access_cacheability = new CacheableMetadata();

    // Try to map the cacheability of the access result that was set at
    // _accessCacheability in getEntitiesToView() to the corresponding render
    // subtree. If no such subtree is found, then merge it with the field-level
    // access cacheability.
    foreach ($items as $delta => $item) {
      // Ignore items for which access cacheability could not be determined in
      // prepareView().
      if (!empty($item->_accessCacheability)) {
        if (isset($elements[$delta])) {
          CacheableMetadata::createFromRenderArray($elements[$delta])
            ->merge($item->_accessCacheability)
            ->applyTo($elements[$delta]);
        }
        else {
          $field_level_access_cacheability = $field_level_access_cacheability->merge($item->_accessCacheability);
        }
      }
    }

    // Apply the cacheability metadata for the inaccessible entities and the
    // entities for which the corresponding render subtree could not be found.
    // This causes the field to be rendered (and cached) according to the cache
    // contexts by which the access results vary, to ensure only users with
    // access to this field can view it. It also tags this field with the cache
    // tags on which the access results depend, to ensure users that cannot view
    // this field at the moment will gain access once any of those cache tags
    // are invalidated.
    $field_level_access_cacheability->applyTo($elements);

    return $elements;
  }

  /**
   * {@inheritdoc}
   *
   * Loads the entities referenced in that field across all the entities being
   * viewed.
   */
  public function prepareView(array $entities_items) {
    // Collect entity IDs to load. For performance, we want to use a single
    // "multiple entity load" to load all the entities for the multiple
    // "entity reference item lists" being displayed. We thus cannot use
    // \Drupal\Core\Field\EntityReferenceFieldItemList::referencedEntities().
    $ids = array();
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        // To avoid trying to reload non-existent entities in
        // getEntitiesToView(), explicitly mark the items where $item->entity
        // contains a valid entity ready for display. All items are initialized
        // at FALSE.
        $item->_loaded = FALSE;
        if ($this->needsEntityLoad($item)) {
          $ids[] = $item->target_id;
        }
      }
    }
    if ($ids) {
      $target_type = $this->getFieldSetting('target_type');
      $target_entities = \Drupal::entityManager()->getStorage($target_type)->loadMultiple($ids);
    }

    // For each item, pre-populate the loaded entity in $item->entity, and set
    // the 'loaded' flag.
    foreach ($entities_items as $items) {
      foreach ($items as $item) {
        if (isset($target_entities[$item->target_id])) {
          $item->entity = $target_entities[$item->target_id];
          $item->_loaded = TRUE;
        }
        elseif ($item->hasNewEntity()) {
          $item->_loaded = TRUE;
        }
      }
    }
  }

  /**
   * Returns whether the entity referenced by an item needs to be loaded.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item
   *    The item to check.
   *
   * @return bool
   *   TRUE if the entity needs to be loaded.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * Checks access to the given entity.
   *
   * By default, entity access is checked. However, a subclass can choose to
   * exclude certain items from entity access checking by immediately granting
   * access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   A cacheable access result.
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view', NULL, TRUE);
  }

}
