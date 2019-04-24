<?php

namespace Drupal\field_layout\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Layout\LayoutInterface;

/**
 * Provides shared code for entity displays.
 *
 * Both EntityViewDisplay and EntityFormDisplay must maintain their parent
 * hierarchy, while being identically enhanced by Field Layout. This trait
 * contains the code they both share.
 */
trait FieldLayoutEntityDisplayTrait {

  /**
   * Gets a layout definition.
   *
   * @param string $layout_id
   *   The layout ID.
   *
   * @return \Drupal\Core\Layout\LayoutDefinition
   *   The layout definition.
   */
  protected function getLayoutDefinition($layout_id) {
    return \Drupal::service('plugin.manager.core.layout')->getDefinition($layout_id);
  }

  /**
   * Implements \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface::getLayoutId().
   */
  public function getLayoutId() {
    return $this->getThirdPartySetting('field_layout', 'id');
  }

  /**
   * Implements \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface::getLayoutSettings().
   */
  public function getLayoutSettings() {
    return $this->getThirdPartySetting('field_layout', 'settings', []);
  }

  /**
   * Implements \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface::setLayoutId().
   */
  public function setLayoutId($layout_id, array $layout_settings = []) {
    if ($this->getLayoutId() !== $layout_id) {
      // @todo Devise a mechanism for mapping old regions to new ones in
      //   https://www.drupal.org/node/2796877.
      $layout_definition = $this->getLayoutDefinition($layout_id);
      $new_region = $layout_definition->getDefaultRegion();
      $layout_regions = $layout_definition->getRegions();
      foreach ($this->getComponents() as $name => $component) {
        if (isset($component['region']) && !isset($layout_regions[$component['region']])) {
          $component['region'] = $new_region;
          $this->setComponent($name, $component);
        }
      }
    }
    $this->setThirdPartySetting('field_layout', 'id', $layout_id);
    // Instantiate the plugin and consult it for the updated plugin
    // configuration. Once layouts are no longer stored as third party settings,
    // this will be handled by the code in
    // \Drupal\Core\Config\Entity\ConfigEntityBase::set() that handles
    // \Drupal\Core\Entity\EntityWithPluginCollectionInterface.
    $layout_settings = $this->doGetLayout($layout_id, $layout_settings)->getConfiguration();
    $this->setThirdPartySetting('field_layout', 'settings', $layout_settings);
    return $this;
  }

  /**
   * Implements \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface::setLayout().
   */
  public function setLayout(LayoutInterface $layout) {
    $this->setLayoutId($layout->getPluginId(), $layout->getConfiguration());
    return $this;
  }

  /**
   * Implements \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface::getLayout().
   */
  public function getLayout() {
    return $this->doGetLayout($this->getLayoutId(), $this->getLayoutSettings());
  }

  /**
   * Gets the layout plugin.
   *
   * @param string $layout_id
   *   A layout plugin ID.
   * @param array $layout_settings
   *   An array of settings.
   *
   * @return \Drupal\Core\Layout\LayoutInterface
   *   The layout plugin.
   */
  protected function doGetLayout($layout_id, array $layout_settings) {
    return \Drupal::service('plugin.manager.core.layout')->createInstance($layout_id, $layout_settings);
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityDisplayBase::init().
   */
  protected function init() {
    $this->ensureLayout();
    parent::init();
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityDisplayBase::preSave().
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Ensure the plugin configuration is updated. Once layouts are no longer
    // stored as third party settings, this will be handled by the code in
    // \Drupal\Core\Config\Entity\ConfigEntityBase::preSave() that handles
    // \Drupal\Core\Entity\EntityWithPluginCollectionInterface.
    if ($this->getLayoutId()) {
      $this->setLayout($this->getLayout());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function ensureLayout($default_layout_id = 'layout_onecol') {
    if (!$this->getLayoutId()) {
      $this->setLayoutId($default_layout_id);
    }

    return $this;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityDisplayBase::calculateDependencies().
   *
   * Ensure the plugin dependencies are included. Once layouts are no longer
   * stored as third party settings, this will be handled by the code in
   * \Drupal\Core\Config\Entity\ConfigEntityBase::calculateDependencies() that
   * handles \Drupal\Core\Entity\EntityWithPluginCollectionInterface.
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // This can be called during uninstallation, so check for a valid ID first.
    if ($this->getLayoutId()) {
      $this->calculatePluginDependencies($this->getLayout());
    }
    return $this;
  }

}
