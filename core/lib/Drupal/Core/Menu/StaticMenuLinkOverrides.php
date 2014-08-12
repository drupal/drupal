<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\StaticMenuLinkOverrides.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines an implementation of the menu link override using a config file.
 */
class StaticMenuLinkOverrides implements StaticMenuLinkOverridesInterface {

  /**
   * The config name used to store the overrides.
   *
   * @var string
   */
  protected $configName = 'menu_link.static.overrides';

  /**
   * The menu link overrides config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a StaticMenuLinkOverrides object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the configuration object when needed.
   *
   * Since this service is injected into all static menu link objects, but
   * only used when updating one, avoid actually loading the config when it's
   * not needed.
   */
  protected function getConfig() {
    if (empty($this->config)) {
      $this->config = $this->configFactory->get($this->configName);
    }
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    $this->config = NULL;
    $this->configFactory->reset($this->configName);
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverride($id) {
    $all_overrides = $this->getConfig()->get('definitions');
    $id = static::encodeId($id);
    return $id && isset($all_overrides[$id]) ? $all_overrides[$id] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultipleOverrides(array $ids) {
    $all_overrides = $this->getConfig()->get('definitions');
    $save = FALSE;
    foreach ($ids as $id) {
      $id = static::encodeId($id);
      if (isset($all_overrides[$id])) {
        unset($all_overrides[$id]);
        $save = TRUE;
      }
    }
    if ($save) {
      $this->getConfig()->set('definitions', $all_overrides)->save();
    }
    return $save;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteOverride($id) {
    return $this->deleteMultipleOverrides(array($id));
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleOverrides(array $ids) {
    $result = array();
    if ($ids) {
      $all_overrides = $this->getConfig()->get('definitions') ?: array();
      foreach ($ids as $id) {
        $encoded_id = static::encodeId($id);
        if (isset($all_overrides[$encoded_id])) {
          $result[$id] = $all_overrides[$encoded_id];
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function saveOverride($id, array $definition) {
    // Only allow to override a specific subset of the keys.
    $expected = array(
      'menu_name' => 1,
      'parent' => 1,
      'weight' => 1,
      'expanded' => 1,
      'enabled' => 1,
    );
    // Filter the overrides to only those that are expected.
    $definition = array_intersect_key($definition, $expected);
    if ($definition) {
      $id = static::encodeId($id);
      $all_overrides = $this->getConfig()->get('definitions');
      // Combine with any existing data.
      $all_overrides[$id] = $definition + $this->loadOverride($id);
      $this->getConfig()->set('definitions', $all_overrides)->save();
    }
    return array_keys($definition);
  }

  /**
   * Encodes the ID by replacing dots with double underscores.
   *
   * This is done because config schema uses dots for its internal type
   * hierarchy. Double underscores are converted to triple underscores to
   * avoid accidental conflicts.
   *
   * @param string $id
   *   The menu plugin ID.
   *
   * @return string
   *   The menu plugin ID with double underscore instead of dots.
   */
  protected static function encodeId($id) {
    return strtr($id, array('.' => '__', '__' => '___'));
  }

}
