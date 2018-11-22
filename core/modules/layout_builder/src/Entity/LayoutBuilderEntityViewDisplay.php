<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Core\Entity\Entity\EntityViewDisplay as BaseEntityViewDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageTrait;

/**
 * Provides an entity view display entity that has a layout.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class LayoutBuilderEntityViewDisplay extends BaseEntityViewDisplay implements LayoutEntityDisplayInterface {

  use SectionStorageTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    // Set $entityFieldManager before calling the parent constructor because the
    // constructor will call init() which then calls setComponent() which needs
    // $entityFieldManager.
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function isOverridable() {
    return $this->getThirdPartySetting('layout_builder', 'allow_custom', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setOverridable($overridable = TRUE) {
    $this->setThirdPartySetting('layout_builder', 'allow_custom', $overridable);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLayoutBuilderEnabled() {
    return (bool) $this->getThirdPartySetting('layout_builder', 'enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function enableLayoutBuilder() {
    $this->setThirdPartySetting('layout_builder', 'enabled', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disableLayoutBuilder() {
    $this->setOverridable(FALSE);
    $this->setThirdPartySetting('layout_builder', 'enabled', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    return $this->getThirdPartySetting('layout_builder', 'sections', []);
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections) {
    $this->setThirdPartySetting('layout_builder', 'sections', array_values($sections));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $original_value = isset($this->original) ? $this->original->isOverridable() : FALSE;
    $new_value = $this->isOverridable();
    if ($original_value !== $new_value) {
      $entity_type_id = $this->getTargetEntityTypeId();
      $bundle = $this->getTargetBundle();

      if ($new_value) {
        $this->addSectionField($entity_type_id, $bundle, OverridesSectionStorage::FIELD_NAME);
      }
      else {
        $this->removeSectionField($entity_type_id, $bundle, OverridesSectionStorage::FIELD_NAME);
      }
    }

    $already_enabled = isset($this->original) ? $this->original->isLayoutBuilderEnabled() : FALSE;
    $set_enabled = $this->isLayoutBuilderEnabled();
    if ($already_enabled !== $set_enabled) {
      if ($set_enabled) {
        // Loop through all existing field-based components and add them as
        // section-based components.
        $components = $this->getComponents();
        // Sort the components by weight.
        uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
        foreach ($components as $name => $component) {
          $this->setComponent($name, $component);
        }
      }
      else {
        // When being disabled, remove all existing section data.
        while (count($this) > 0) {
          $this->removeSection(0);
        }
      }
    }
  }

  /**
   * Removes a layout section field if it is no longer needed.
   *
   * Because the field is shared across all view modes, the field will only be
   * removed if no other view modes are using it.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The name for the layout section field.
   */
  protected function removeSectionField($entity_type_id, $bundle, $field_name) {
    $query = $this->entityTypeManager()->getStorage($this->getEntityTypeId())->getQuery()
      ->condition('targetEntityType', $this->getTargetEntityTypeId())
      ->condition('bundle', $this->getTargetBundle())
      ->condition('mode', $this->getMode(), '<>')
      ->condition('third_party_settings.layout_builder.allow_custom', TRUE);
    $enabled = (bool) $query->count()->execute();
    if (!$enabled && $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name)) {
      $field->delete();
    }
  }

  /**
   * Adds a layout section field to a given bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The name for the layout section field.
   */
  protected function addSectionField($entity_type_id, $bundle, $field_name) {
    $field = FieldConfig::loadByName($entity_type_id, $bundle, $field_name);
    if (!$field) {
      $field_storage = FieldStorageConfig::loadByName($entity_type_id, $field_name);
      if (!$field_storage) {
        $field_storage = FieldStorageConfig::create([
          'entity_type' => $entity_type_id,
          'field_name' => $field_name,
          'type' => 'layout_section',
          'locked' => TRUE,
        ]);
        $field_storage->save();
      }

      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
        'label' => t('Layout'),
      ]);
      $field->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createCopy($mode) {
    // Disable Layout Builder and remove any sections copied from the original.
    return parent::createCopy($mode)
      ->setSections([])
      ->disableLayoutBuilder();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultRegion() {
    if ($this->hasSection(0)) {
      return $this->getSection(0)->getDefaultRegion();
    }

    return parent::getDefaultRegion();
  }

  /**
   * Wraps the context repository service.
   *
   * @return \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   *   The context repository service.
   */
  protected function contextRepository() {
    return \Drupal::service('context.repository');
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $entities) {
    $build_list = parent::buildMultiple($entities);
    if (!$this->isLayoutBuilderEnabled()) {
      return $build_list;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $id => $entity) {
      $sections = $this->getRuntimeSections($entity);
      if ($sections) {
        foreach ($build_list[$id] as $name => $build_part) {
          $field_definition = $this->getFieldDefinition($name);
          if ($field_definition && $field_definition->isDisplayConfigurable($this->displayContext)) {
            unset($build_list[$id][$name]);
          }
        }

        // Bypass ::getContexts() in order to use the runtime entity, not a
        // sample entity.
        $contexts = $this->contextRepository()->getAvailableContexts();
        $label = new TranslatableMarkup('@entity being viewed', [
          '@entity' => $entity->getEntityType()->getSingularLabel(),
        ]);
        $contexts['layout_builder.entity'] = EntityContext::fromEntity($entity, $label);
        foreach ($sections as $delta => $section) {
          $build_list[$id]['_layout_builder'][$delta] = $section->toRenderArray($contexts);
        }
      }
    }

    return $build_list;
  }

  /**
   * Gets the runtime sections for a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\layout_builder\Section[]
   *   The sections.
   */
  protected function getRuntimeSections(FieldableEntityInterface $entity) {
    if ($this->isOverridable() && !$entity->get(OverridesSectionStorage::FIELD_NAME)->isEmpty()) {
      return $entity->get(OverridesSectionStorage::FIELD_NAME)->getSections();
    }

    return $this->getSections();
  }

  /**
   * {@inheritdoc}
   *
   * @todo Move this upstream in https://www.drupal.org/node/2939931.
   */
  public function label() {
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($this->getTargetEntityTypeId());
    $bundle_label = $bundle_info[$this->getTargetBundle()]['label'];
    $target_entity_type = $this->entityTypeManager()->getDefinition($this->getTargetEntityTypeId());
    return new TranslatableMarkup('@bundle @label', ['@bundle' => $bundle_label, '@label' => $target_entity_type->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    foreach ($this->getSections() as $delta => $section) {
      $this->calculatePluginDependencies($section->getLayout());
      foreach ($section->getComponents() as $uuid => $component) {
        $this->calculatePluginDependencies($component->getPlugin());
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    // Loop through all sections and determine if the removed dependencies are
    // used by their layout plugins.
    foreach ($this->getSections() as $delta => $section) {
      $layout_dependencies = $this->getPluginDependencies($section->getLayout());
      $layout_removed_dependencies = $this->getPluginRemovedDependencies($layout_dependencies, $dependencies);
      if ($layout_removed_dependencies) {
        // @todo Allow the plugins to react to their dependency removal in
        //   https://www.drupal.org/project/drupal/issues/2579743.
        $this->removeSection($delta);
        $changed = TRUE;
      }
      // If the section is not removed, loop through all components.
      else {
        foreach ($section->getComponents() as $uuid => $component) {
          $plugin_dependencies = $this->getPluginDependencies($component->getPlugin());
          $component_removed_dependencies = $this->getPluginRemovedDependencies($plugin_dependencies, $dependencies);
          if ($component_removed_dependencies) {
            // @todo Allow the plugins to react to their dependency removal in
            //   https://www.drupal.org/project/drupal/issues/2579743.
            $section->removeComponent($uuid);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function setComponent($name, array $options = []) {
    parent::setComponent($name, $options);

    // Only continue if Layout Builder is enabled.
    if (!$this->isLayoutBuilderEnabled()) {
      return $this;
    }

    // Retrieve the updated options after the parent:: call.
    $options = $this->content[$name];
    // Provide backwards compatibility by converting to a section component.
    $field_definition = $this->getFieldDefinition($name);
    $extra_fields = $this->entityFieldManager->getExtraFields($this->getTargetEntityTypeId(), $this->getTargetBundle());
    $is_view_configurable_non_extra_field = $field_definition && $field_definition->isDisplayConfigurable('view') && isset($options['type']);
    if ($is_view_configurable_non_extra_field || isset($extra_fields['display'][$name])) {
      $configuration = [
        'label_display' => '0',
        'context_mapping' => ['entity' => 'layout_builder.entity'],
      ];
      if ($is_view_configurable_non_extra_field) {
        $configuration['id'] = 'field_block:' . $this->getTargetEntityTypeId() . ':' . $this->getTargetBundle() . ':' . $name;
        $keys = array_flip(['type', 'label', 'settings', 'third_party_settings']);
        $configuration['formatter'] = array_intersect_key($options, $keys);
      }
      else {
        $configuration['id'] = 'extra_field_block:' . $this->getTargetEntityTypeId() . ':' . $this->getTargetBundle() . ':' . $name;
      }

      $section = $this->getDefaultSection();
      $region = isset($options['region']) ? $options['region'] : $section->getDefaultRegion();
      $new_component = (new SectionComponent(\Drupal::service('uuid')->generate(), $region, $configuration));
      $section->appendComponent($new_component);
    }
    return $this;
  }

  /**
   * Gets a default section.
   *
   * @return \Drupal\layout_builder\Section
   *   The default section.
   */
  protected function getDefaultSection() {
    // If no section exists, append a new one.
    if (!$this->hasSection(0)) {
      $this->appendSection(new Section('layout_onecol'));
    }

    // Return the first section.
    return $this->getSection(0);
  }

}
