<?php

namespace Drupal\layout_builder;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;

/**
 * Methods to help with entities using the layout builder.
 */
trait LayoutEntityHelperTrait {

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * Determines if an entity can have a layout.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity can have a layout otherwise FALSE.
   */
  protected function isLayoutCompatibleEntity(EntityInterface $entity) {
    return $this->getSectionStorageForEntity($entity) !== NULL;
  }

  /**
   * Gets revision IDs for layout sections.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   The layout sections.
   *
   * @return int[]
   *   The revision IDs.
   */
  protected function getInlineBlockRevisionIdsInSections(array $sections) {
    $revision_ids = [];
    foreach ($this->getInlineBlockComponents($sections) as $component) {
      $configuration = $component->getPlugin()->getConfiguration();
      if (!empty($configuration['block_revision_id'])) {
        $revision_ids[] = $configuration['block_revision_id'];
      }
    }
    return $revision_ids;
  }

  /**
   * Gets the sections for an entity if any.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\layout_builder\Section[]
   *   The entity layout sections if available.
   */
  protected function getEntitySections(EntityInterface $entity) {
    $section_storage = $this->getSectionStorageForEntity($entity);
    return $section_storage ? $section_storage->getSections() : [];
  }

  /**
   * Gets components that have Inline Block plugins.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   The layout sections.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   The components that contain Inline Block plugins.
   */
  protected function getInlineBlockComponents(array $sections) {
    $inline_block_components = [];
    foreach ($sections as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin instanceof DerivativeInspectionInterface && $plugin->getBaseId() === 'inline_block') {
          $inline_block_components[] = $component;
        }
      }
    }
    return $inline_block_components;
  }

  /**
   * Gets the section storage for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage if found otherwise NULL.
   */
  protected function getSectionStorageForEntity(EntityInterface $entity) {
    // @todo Take into account other view modes in
    //   https://www.drupal.org/node/3008924.
    $view_mode = 'full';
    if ($entity instanceof LayoutEntityDisplayInterface) {
      $contexts['display'] = EntityContext::fromEntity($entity);
      $contexts['view_mode'] = new Context(new ContextDefinition('string'), $entity->getMode());
    }
    else {
      $contexts['entity'] = EntityContext::fromEntity($entity);
      if ($entity instanceof FieldableEntityInterface) {
        $display = EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
        if ($display instanceof LayoutEntityDisplayInterface) {
          $contexts['display'] = EntityContext::fromEntity($display);
        }
        $contexts['view_mode'] = new Context(new ContextDefinition('string'), $view_mode);
      }
    }
    return $this->sectionStorageManager()->findByContext($contexts, new CacheableMetadata());
  }

  /**
   * Determines if an entity is using a field for the layout override.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity is using a field for a layout override.
   *
   * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0.
   *   To determine if an entity has a layout override, use
   *   \Drupal\layout_builder\LayoutEntityHelperTrait::getSectionStorageForEntity()
   *   and check whether the result is an instance of
   *   \Drupal\layout_builder\DefaultsSectionStorageInterface.
   *
   * @see https://www.drupal.org/node/3030609
   */
  protected function isEntityUsingFieldOverride(EntityInterface $entity) {
    @trigger_error('\Drupal\layout_builder\LayoutEntityHelperTrait::isEntityUsingFieldOverride() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Internal storage of overrides may change so the existence of the field does not necessarily guarantee an overridable entity. See https://www.drupal.org/node/3030609.', E_USER_DEPRECATED);
    return $entity instanceof FieldableEntityInterface && $entity->hasField(OverridesSectionStorage::FIELD_NAME);
  }

  /**
   * Determines if the original entity used the default section storage.
   *
   * This method can be used during the entity save process to determine whether
   * $entity->original is set and used the default section storage plugin as
   * determined by ::getSectionStorageForEntity().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the original entity used the default storage.
   */
  protected function originalEntityUsesDefaultStorage(EntityInterface $entity) {
    $section_storage = $this->getSectionStorageForEntity($entity);
    if ($section_storage instanceof OverridesSectionStorageInterface && !$entity->isNew() && isset($entity->original)) {
      $original_section_storage = $this->getSectionStorageForEntity($entity->original);
      return $original_section_storage instanceof DefaultsSectionStorageInterface;
    }
    return FALSE;
  }

  /**
   * Gets the section storage manager.
   *
   * @return \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   *   The section storage manager.
   */
  private function sectionStorageManager() {
    return $this->sectionStorageManager ?: \Drupal::service('plugin.manager.layout_builder.section_storage');
  }

}
