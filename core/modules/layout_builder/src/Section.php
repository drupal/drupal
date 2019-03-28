<?php

namespace Drupal\layout_builder;

use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Provides a domain object for layout sections.
 *
 * A section consists of three parts:
 * - The layout plugin ID for the layout applied to the section (for example,
 *   'layout_onecol').
 * - An array of settings for the layout plugin.
 * - An array of components that can be rendered in the section.
 *
 * @see \Drupal\Core\Layout\LayoutDefinition
 * @see \Drupal\layout_builder\SectionComponent
 */
class Section implements ThirdPartySettingsInterface {

  /**
   * The layout plugin ID.
   *
   * @var string
   */
  protected $layoutId;

  /**
   * The layout plugin settings.
   *
   * @var array
   */
  protected $layoutSettings = [];

  /**
   * An array of components, keyed by UUID.
   *
   * @var \Drupal\layout_builder\SectionComponent[]
   */
  protected $components = [];

  /**
   * Third party settings.
   *
   * An array of key/value pairs keyed by provider.
   *
   * @var array[]
   */
  protected $thirdPartySettings = [];

  /**
   * Constructs a new Section.
   *
   * @param string $layout_id
   *   The layout plugin ID.
   * @param array $layout_settings
   *   (optional) The layout plugin settings.
   * @param \Drupal\layout_builder\SectionComponent[] $components
   *   (optional) The components.
   * @param array[] $third_party_settings
   *   (optional) Any third party settings.
   */
  public function __construct($layout_id, array $layout_settings = [], array $components = [], array $third_party_settings = []) {
    $this->layoutId = $layout_id;
    $this->layoutSettings = $layout_settings;
    foreach ($components as $component) {
      $this->setComponent($component);
    }
    $this->thirdPartySettings = $third_party_settings;
  }

  /**
   * Returns the renderable array for this section.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of available contexts.
   * @param bool $in_preview
   *   TRUE if the section is being previewed, FALSE otherwise.
   *
   * @return array
   *   A renderable array representing the content of the section.
   */
  public function toRenderArray(array $contexts = [], $in_preview = FALSE) {
    $regions = [];
    foreach ($this->getComponents() as $component) {
      if ($output = $component->toRenderArray($contexts, $in_preview)) {
        $regions[$component->getRegion()][$component->getUuid()] = $output;
      }
    }

    return $this->getLayout()->build($regions);
  }

  /**
   * Gets the layout plugin for this section.
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   *   The layout plugin.
   */
  public function getLayout() {
    return $this->layoutPluginManager()->createInstance($this->getLayoutId(), $this->getLayoutSettings());
  }

  /**
   * Gets the layout plugin ID for this section.
   *
   * @return string
   *   The layout plugin ID.
   *
   * @internal
   *   This method should only be used by code responsible for storing the data.
   */
  public function getLayoutId() {
    return $this->layoutId;
  }

  /**
   * Gets the layout plugin settings for this section.
   *
   * @return mixed[]
   *   The layout plugin settings.
   *
   * @internal
   *   This method should only be used by code responsible for storing the data.
   */
  public function getLayoutSettings() {
    return $this->layoutSettings;
  }

  /**
   * Sets the layout plugin settings for this section.
   *
   * @param mixed[] $layout_settings
   *   The layout plugin settings.
   *
   * @return $this
   */
  public function setLayoutSettings(array $layout_settings) {
    $this->layoutSettings = $layout_settings;
    return $this;
  }

  /**
   * Gets the default region.
   *
   * @return string
   *   The machine-readable name of the default region.
   */
  public function getDefaultRegion() {
    return $this->layoutPluginManager()->getDefinition($this->getLayoutId())->getDefaultRegion();
  }

  /**
   * Returns the components of the section.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   The components.
   */
  public function getComponents() {
    return $this->components;
  }

  /**
   * Gets the component for a given UUID.
   *
   * @param string $uuid
   *   The UUID of the component to retrieve.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The component.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected UUID does not exist.
   */
  public function getComponent($uuid) {
    if (!isset($this->components[$uuid])) {
      throw new \InvalidArgumentException(sprintf('Invalid UUID "%s"', $uuid));
    }

    return $this->components[$uuid];
  }

  /**
   * Helper method to set a component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The component.
   *
   * @return $this
   */
  protected function setComponent(SectionComponent $component) {
    $this->components[$component->getUuid()] = $component;
    return $this;
  }

  /**
   * Removes a given component from a region.
   *
   * @param string $uuid
   *   The UUID of the component to remove.
   *
   * @return $this
   */
  public function removeComponent($uuid) {
    unset($this->components[$uuid]);
    return $this;
  }

  /**
   * Appends a component to the end of a region.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The component being appended.
   *
   * @return $this
   */
  public function appendComponent(SectionComponent $component) {
    $component->setWeight($this->getNextHighestWeight($component->getRegion()));
    $this->setComponent($component);
    return $this;
  }

  /**
   * Returns the next highest weight of the component in a region.
   *
   * @param string $region
   *   The region name.
   *
   * @return int
   *   A number higher than the highest weight of the component in the region.
   */
  protected function getNextHighestWeight($region) {
    $components = $this->getComponentsByRegion($region);
    $weights = array_map(function (SectionComponent $component) {
      return $component->getWeight();
    }, $components);
    return $weights ? max($weights) + 1 : 0;
  }

  /**
   * Gets the components for a specific region.
   *
   * @param string $region
   *   The region name.
   *
   * @return \Drupal\layout_builder\SectionComponent[]
   *   An array of components in the specified region, sorted by weight.
   */
  public function getComponentsByRegion($region) {
    $components = array_filter($this->getComponents(), function (SectionComponent $component) use ($region) {
      return $component->getRegion() === $region;
    });
    uasort($components, function (SectionComponent $a, SectionComponent $b) {
      return $a->getWeight() > $b->getWeight() ? 1 : -1;
    });
    return $components;
  }

  /**
   * Inserts a component after a specified existing component.
   *
   * @param string $preceding_uuid
   *   The UUID of the existing component to insert after.
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The component being inserted.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   Thrown when the expected UUID does not exist.
   */
  public function insertAfterComponent($preceding_uuid, SectionComponent $component) {
    // Find the delta of the specified UUID.
    $uuids = array_keys($this->getComponentsByRegion($component->getRegion()));
    $delta = array_search($preceding_uuid, $uuids, TRUE);
    if ($delta === FALSE) {
      throw new \InvalidArgumentException(sprintf('Invalid preceding UUID "%s"', $preceding_uuid));
    }
    return $this->insertComponent($delta + 1, $component);
  }

  /**
   * Inserts a component at a specified delta.
   *
   * @param int $delta
   *   The zero-based delta in which to insert the component.
   * @param \Drupal\layout_builder\SectionComponent $new_component
   *   The component being inserted.
   *
   * @return $this
   *
   * @throws \OutOfBoundsException
   *   Thrown when the specified delta is invalid.
   */
  public function insertComponent($delta, SectionComponent $new_component) {
    $components = $this->getComponentsByRegion($new_component->getRegion());
    $count = count($components);
    if ($delta > $count) {
      throw new \OutOfBoundsException(sprintf('Invalid delta "%s" for the "%s" component', $delta, $new_component->getUuid()));
    }

    // If the delta is the end of the list, append the component instead.
    if ($delta === $count) {
      return $this->appendComponent($new_component);
    }

    // Find the weight of the component that exists at the specified delta.
    $weight = array_values($components)[$delta]->getWeight();
    $this->setComponent($new_component->setWeight($weight++));

    // Increase the weight of every subsequent component.
    foreach (array_slice($components, $delta) as $component) {
      $component->setWeight($weight++);
    }
    return $this;
  }

  /**
   * Wraps the layout plugin manager.
   *
   * @return \Drupal\Core\Layout\LayoutPluginManagerInterface
   *   The layout plugin manager.
   */
  protected function layoutPluginManager() {
    return \Drupal::service('plugin.manager.core.layout');
  }

  /**
   * Returns an array representation of the section.
   *
   * Only use this method if you are implementing custom storage for sections.
   *
   * @return array
   *   An array representation of the section component.
   */
  public function toArray() {
    return [
      'layout_id' => $this->getLayoutId(),
      'layout_settings' => $this->getLayoutSettings(),
      'components' => array_map(function (SectionComponent $component) {
        return $component->toArray();
      }, $this->getComponents()),
      'third_party_settings' => $this->thirdPartySettings,
    ];
  }

  /**
   * Creates an object from an array representation of the section.
   *
   * Only use this method if you are implementing custom storage for sections.
   *
   * @param array $section
   *   An array of section data in the format returned by ::toArray().
   *
   * @return static
   *   The section object.
   */
  public static function fromArray(array $section) {
    // Ensure expected array keys are present.
    $section += [
      'layout_id' => '',
      'layout_settings' => [],
      'components' => [],
      'third_party_settings' => [],
    ];
    return new static(
      $section['layout_id'],
      $section['layout_settings'],
      array_map([SectionComponent::class, 'fromArray'], $section['components']),
      $section['third_party_settings']
    );
  }

  /**
   * Magic method: Implements a deep clone.
   */
  public function __clone() {
    foreach ($this->components as $uuid => $component) {
      $this->components[$uuid] = clone $component;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($provider, $key, $default = NULL) {
    return isset($this->thirdPartySettings[$provider][$key]) ? $this->thirdPartySettings[$provider][$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($provider) {
    return isset($this->thirdPartySettings[$provider]) ? $this->thirdPartySettings[$provider] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($provider, $key, $value) {
    $this->thirdPartySettings[$provider][$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($provider, $key) {
    unset($this->thirdPartySettings[$provider][$key]);
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this provider.
    if (empty($this->thirdPartySettings[$provider])) {
      unset($this->thirdPartySettings[$provider]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return array_keys($this->thirdPartySettings);
  }

}
