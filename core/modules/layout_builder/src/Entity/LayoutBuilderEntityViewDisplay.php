<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Action\Attribute\ActionMethod;
use Drupal\Core\Entity\Entity\EntityViewDisplay as BaseEntityViewDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\LayoutEntityHelperTrait;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionListTrait;

/**
 * Provides an entity view display entity that has a layout.
 */
class LayoutBuilderEntityViewDisplay extends BaseEntityViewDisplay implements LayoutEntityDisplayInterface {

  use LayoutEntityHelperTrait;
  use SectionListTrait;

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
    return $this->isLayoutBuilderEnabled() && $this->getThirdPartySetting('layout_builder', 'allow_custom', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Toggle overridable layouts'), pluralize: FALSE, name: 'allowLayoutOverrides')]
  public function setOverridable($overridable = TRUE) {
    $this->setThirdPartySetting('layout_builder', 'allow_custom', $overridable);
    // Enable Layout Builder if it's not already enabled and overriding.
    if ($overridable && !$this->isLayoutBuilderEnabled()) {
      $this->enableLayoutBuilder();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isLayoutBuilderEnabled() {
    // Layout Builder must not be enabled for the '_custom' view mode that is
    // used for on-the-fly rendering of fields in isolation from the entity.
    if ($this->isCustomMode()) {
      return FALSE;
    }
    return (bool) $this->getThirdPartySetting('layout_builder', 'enabled');
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Enable Layout Builder'), pluralize: FALSE)]
  public function enableLayoutBuilder() {
    $this->setThirdPartySetting('layout_builder', 'enabled', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[ActionMethod(adminLabel: new TranslatableMarkup('Disable Layout Builder'), pluralize: FALSE)]
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
    // Third-party settings must be completely unset instead of stored as an
    // empty array.
    if (!$sections) {
      $this->unsetThirdPartySetting('layout_builder', 'sections');
    }
    else {
      $this->setThirdPartySetting('layout_builder', 'sections', array_values($sections));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {

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

    parent::preSave($storage);

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
        $this->removeAllSections();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(): int {
    $return = parent::save();
    if (!\Drupal::moduleHandler()->moduleExists('layout_builder_expose_all_field_blocks')) {
      // Invalidate the block cache in order to regenerate field block
      // definitions.
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
    return $return;
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
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage($this->getEntityTypeId());
    $query = $storage->getQuery()
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
        $field_storage->setTranslatable(FALSE);
        $field_storage->save();
      }

      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
        'label' => t('Layout'),
      ]);
      $field->setTranslatable(FALSE);
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
   * Indicates if this display is using the '_custom' view mode.
   *
   * @return bool
   *   TRUE if this display is using the '_custom' view mode, FALSE otherwise.
   */
  protected function isCustomMode() {
    return $this->getOriginalMode() === static::CUSTOM_MODE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildMultiple(array $entities) {
    $build_list = parent::buildMultiple($entities);

    // Layout Builder can not be enabled for the '_custom' view mode that is
    // used for on-the-fly rendering of fields in isolation from the entity.
    if ($this->isCustomMode()) {
      return $build_list;
    }

    foreach ($entities as $id => $entity) {
      $build_list[$id]['_layout_builder'] = $this->buildSections($entity);

      // If there are any sections, remove all fields with configurable display
      // from the existing build. These fields are replicated within sections as
      // field blocks by ::setComponent().
      if (!Element::isEmpty($build_list[$id]['_layout_builder'])) {
        foreach ($build_list[$id] as $name => $build_part) {
          $field_definition = $this->getFieldDefinition($name);
          if ($field_definition && $field_definition->isDisplayConfigurable($this->displayContext)) {
            unset($build_list[$id][$name]);
          }
        }
      }
    }

    return $build_list;
  }

  /**
   * Builds the render array for the sections of a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The render array representing the sections of the entity.
   */
  protected function buildSections(FieldableEntityInterface $entity) {
    $contexts = $this->getContextsForEntity($entity);
    $label = new TranslatableMarkup('@entity being viewed', [
      '@entity' => $entity->getEntityType()->getSingularLabel(),
    ]);
    $contexts['layout_builder.entity'] = EntityContext::fromEntity($entity, $label);

    $cacheability = new CacheableMetadata();
    $storage = $this->sectionStorageManager()->findByContext($contexts, $cacheability);

    $build = [];
    if ($storage) {
      foreach ($storage->getSections() as $delta => $section) {
        $build[$delta] = $section->toRenderArray($contexts);
      }
    }
    // The render array is built based on decisions made by SectionStorage
    // plugins and therefore it needs to depend on the accumulated
    // cacheability of those decisions.
    $cacheability->applyTo($build);
    return $build;
  }

  /**
   * Gets the available contexts for a given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of context objects for a given entity.
   */
  protected function getContextsForEntity(FieldableEntityInterface $entity) {
    $available_context_ids = array_keys($this->contextRepository()->getAvailableContexts());
    return [
      'view_mode' => new Context(ContextDefinition::create('string'), $this->getMode()),
      'entity' => EntityContext::fromEntity($entity),
      'display' => EntityContext::fromEntity($this),
    ] + $this->contextRepository()->getRuntimeContexts($available_context_ids);
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

    foreach ($this->getSections() as $section) {
      $this->calculatePluginDependencies($section->getLayout());
      foreach ($section->getComponents() as $component) {
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
      $region = $options['region'] ?? $section->getDefaultRegion();
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

  /**
   * Gets the section storage manager.
   *
   * @return \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   *   The section storage manager.
   */
  private function sectionStorageManager() {
    return \Drupal::service('plugin.manager.layout_builder.section_storage');
  }

  /**
   * {@inheritdoc}
   */
  public function getComponent($name) {
    if ($this->isLayoutBuilderEnabled() && $section_component = $this->getSectionComponentForFieldName($name)) {
      $plugin = $section_component->getPlugin();
      if ($plugin instanceof ConfigurableInterface) {
        $configuration = $plugin->getConfiguration();
        if (isset($configuration['formatter'])) {
          return $configuration['formatter'];
        }
      }
    }
    return parent::getComponent($name);
  }

  /**
   * Gets the component for a given field name if any.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\layout_builder\SectionComponent|null
   *   The section component if it is available.
   */
  private function getSectionComponentForFieldName($field_name) {
    // Loop through every component until the first match is found.
    foreach ($this->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if ($plugin instanceof DerivativeInspectionInterface && in_array($plugin->getBaseId(), ['field_block', 'extra_field_block'], TRUE)) {
          // FieldBlock derivative IDs are in the format
          // [entity_type]:[bundle]:[field].
          [, , $field_block_field_name] = explode(PluginBase::DERIVATIVE_SEPARATOR, $plugin->getDerivativeId());
          if ($field_block_field_name === $field_name) {
            return $component;
          }
        }
      }
    }
    return NULL;
  }

}
