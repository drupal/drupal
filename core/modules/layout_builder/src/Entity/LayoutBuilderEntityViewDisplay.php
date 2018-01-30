<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay as BaseEntityViewDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;

/**
 * Provides an entity view display entity that has a layout.
 *
 * @internal
 *   Layout Builder is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class LayoutBuilderEntityViewDisplay extends BaseEntityViewDisplay implements LayoutEntityDisplayInterface {

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
  public function getSections() {
    return $this->getThirdPartySetting('layout_builder', 'sections', []);
  }

  /**
   * Store the information for all sections.
   *
   * @param \Drupal\layout_builder\Section[] $sections
   *   The sections information.
   *
   * @return $this
   */
  protected function setSections(array $sections) {
    $this->setThirdPartySetting('layout_builder', 'sections', array_values($sections));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->getSections());
  }

  /**
   * {@inheritdoc}
   */
  public function getSection($delta) {
    if (!$this->hasSection($delta)) {
      throw new \OutOfBoundsException(sprintf('Invalid delta "%s" for the "%s" entity', $delta, $this->id()));
    }

    return $this->getSections()[$delta];
  }

  /**
   * Sets the section for the given delta on the display.
   *
   * @param int $delta
   *   The delta of the section.
   * @param \Drupal\layout_builder\Section $section
   *   The layout section.
   *
   * @return $this
   */
  protected function setSection($delta, Section $section) {
    $sections = $this->getSections();
    $sections[$delta] = $section;
    $this->setSections($sections);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendSection(Section $section) {
    $delta = $this->count();

    $this->setSection($delta, $section);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function insertSection($delta, Section $section) {
    if ($this->hasSection($delta)) {
      $sections = $this->getSections();
      // @todo Use https://www.drupal.org/node/66183 once resolved.
      $start = array_slice($sections, 0, $delta);
      $end = array_slice($sections, $delta);
      $this->setSections(array_merge($start, [$section], $end));
    }
    else {
      $this->appendSection($section);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSection($delta) {
    $sections = $this->getSections();
    unset($sections[$delta]);
    $this->setSections($sections);
    return $this;
  }

  /**
   * Indicates if there is a section at the specified delta.
   *
   * @param int $delta
   *   The delta of the section.
   *
   * @return bool
   *   TRUE if there is a section for this delta, FALSE otherwise.
   */
  protected function hasSection($delta) {
    $sections = $this->getSections();
    return isset($sections[$delta]);
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
        $this->addSectionField($entity_type_id, $bundle, 'layout_builder__layout');
      }
      elseif ($field = FieldConfig::loadByName($entity_type_id, $bundle, 'layout_builder__layout')) {
        $field->delete();
      }
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

    foreach ($entities as $id => $entity) {
      $sections = $this->getRuntimeSections($entity);
      if ($sections) {
        foreach ($build_list[$id] as $name => $build_part) {
          $field_definition = $this->getFieldDefinition($name);
          if ($field_definition && $field_definition->isDisplayConfigurable($this->displayContext)) {
            unset($build_list[$id][$name]);
          }
        }

        // Bypass ::getActiveContexts() in order to use the runtime entity, not
        // a sample entity.
        $contexts = $this->contextRepository()->getAvailableContexts();
        // @todo Use EntityContextDefinition after resolving
        //   https://www.drupal.org/node/2932462.
        $contexts['layout_builder.entity'] = new Context(new ContextDefinition("entity:{$entity->getEntityTypeId()}", new TranslatableMarkup('@entity being viewed', ['@entity' => $entity->getEntityType()->getLabel()])), $entity);
        foreach ($sections as $delta => $section) {
          $build_list[$id]['_layout_builder'][$delta] = $section->toRenderArray($contexts);
        }
      }
    }

    return $build_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    $entity = $this->getSampleEntity($this->getTargetEntityTypeId(), $this->getTargetBundle());
    $context_label = new TranslatableMarkup('@entity being viewed', ['@entity' => $entity->getEntityType()->getLabel()]);

    // @todo Use EntityContextDefinition after resolving
    //   https://www.drupal.org/node/2932462.
    $contexts = [];
    $contexts['layout_builder.entity'] = new Context(new ContextDefinition("entity:{$entity->getEntityTypeId()}", $context_label), $entity);
    return $contexts;
  }

  /**
   * Returns a sample entity.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An entity.
   */
  protected function getSampleEntity($entity_type_id, $bundle_id) {
    /** @var \Drupal\Core\TempStore\SharedTempStore $tempstore */
    $tempstore = \Drupal::service('tempstore.shared')->get('layout_builder.sample_entity');
    if ($entity = $tempstore->get("$entity_type_id.$bundle_id")) {
      return $entity;
    }

    $entity_storage = $this->entityTypeManager()->getStorage($entity_type_id);
    if (!$entity_storage instanceof ContentEntityStorageInterface) {
      throw new \InvalidArgumentException(sprintf('The "%s" entity storage is not supported', $entity_type_id));
    }

    $entity = $entity_storage->createWithSampleValues($bundle_id);
    // Mark the sample entity as being a preview.
    $entity->in_preview = TRUE;
    $tempstore->set("$entity_type_id.$bundle_id", $entity);
    return $entity;
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
    if ($this->isOverridable() && !$entity->get('layout_builder__layout')->isEmpty()) {
      return $entity->get('layout_builder__layout')->getSections();
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
  public static function getStorageType() {
    return 'defaults';
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonicalUrl() {
    return Url::fromRoute("entity.entity_view_display.{$this->getTargetEntityTypeId()}.view_mode", $this->getRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl() {
    return Url::fromRoute("entity.entity_view_display.{$this->getTargetEntityTypeId()}.layout_builder", $this->getRouteParameters());
  }

  /**
   * Returns the route parameters needed to build routes for this entity.
   *
   * @return string[]
   *   An array of route parameters.
   */
  protected function getRouteParameters() {
    $route_parameters = [];

    $entity_type = $this->entityTypeManager()->getDefinition($this->getTargetEntityTypeId());
    $bundle_parameter_key = $entity_type->getBundleEntityType() ?: 'bundle';
    $route_parameters[$bundle_parameter_key] = $this->getTargetBundle();

    $route_parameters['view_mode_name'] = $this->getMode();
    return $route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    foreach ($this->getSections() as $delta => $section) {
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

    // Loop through all components and determine if the removed dependencies are
    // used by their plugins.
    foreach ($this->getSections() as $delta => $section) {
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
    return $changed;
  }

  /**
   * Calculates and returns dependencies of a specific plugin instance.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $instance
   *   The plugin instance.
   *
   * @return array
   *   An array of dependencies keyed by the type of dependency.
   *
   * @todo Replace this in https://www.drupal.org/project/drupal/issues/2939925.
   */
  protected function getPluginDependencies(PluginInspectionInterface $instance) {
    $definition = $instance->getPluginDefinition();
    $dependencies['module'][] = $definition['provider'];
    // Plugins can declare additional dependencies in their definition.
    if (isset($definition['config_dependencies'])) {
      $dependencies = NestedArray::mergeDeep($dependencies, $definition['config_dependencies']);
    }

    // If a plugin is dependent, calculate its dependencies.
    if ($instance instanceof DependentPluginInterface && $plugin_dependencies = $instance->calculateDependencies()) {
      $dependencies = NestedArray::mergeDeep($dependencies, $plugin_dependencies);
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function setComponent($name, array $options = []) {
    parent::setComponent($name, $options);

    // @todo Remove workaround for EntityViewBuilder::getSingleFieldDisplay() in
    //   https://www.drupal.org/project/drupal/issues/2936464.
    if ($this->getMode() === static::CUSTOM_MODE) {
      return $this;
    }

    // Retrieve the updated options after the parent:: call.
    $options = $this->content[$name];
    // Provide backwards compatibility by converting to a section component.
    $field_definition = $this->getFieldDefinition($name);
    if ($field_definition && $field_definition->isDisplayConfigurable('view') && isset($options['type'])) {
      $configuration = [];
      $configuration['id'] = 'field_block:' . $this->getTargetEntityTypeId() . ':' . $this->getTargetBundle() . ':' . $name;
      $configuration['label_display'] = FALSE;
      $keys = array_flip(['type', 'label', 'settings', 'third_party_settings']);
      $configuration['formatter'] = array_intersect_key($options, $keys);
      $configuration['context_mapping']['entity'] = 'layout_builder.entity';

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
