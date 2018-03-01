<?php

namespace Drupal\layout_builder\Entity;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityViewDisplay as BaseEntityViewDisplay;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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

        // Bypass ::getContexts() in order to use the runtime entity, not a
        // sample entity.
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
    $dependencies = [];
    $definition = $instance->getPluginDefinition();
    if ($definition instanceof PluginDefinitionInterface) {
      $dependencies['module'][] = $definition->getProvider();
      if ($definition instanceof DependentPluginDefinitionInterface && $config_dependencies = $definition->getConfigDependencies()) {
        $dependencies = NestedArray::mergeDeep($dependencies, $config_dependencies);
      }
    }
    elseif (is_array($definition)) {
      $dependencies['module'][] = $definition['provider'];
      // Plugins can declare additional dependencies in their definition.
      if (isset($definition['config_dependencies'])) {
        $dependencies = NestedArray::mergeDeep($dependencies, $definition['config_dependencies']);
      }
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
