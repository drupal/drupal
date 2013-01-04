<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\Mapper\ConfigMapper.
 */

namespace Drupal\Core\Plugin\Mapper;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Retrieves plugin instances from the configuration system.
 */
class ConfigMapper implements MapperInterface {

  /**
   * The plugin manager instance used by this mapper.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a \Drupal\Core\Plugin\Mapper\ConfigMapper object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager instance to use for this mapper.
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * Implements \Drupal\Component\Plugin\Mapper\MapperInterface::getInstance().
   */
  public function getInstance(array $options) {
    $config = config($options['config']);
    if ($config) {
      $plugin_id = $config->get('id');
      $settings = $config->get();
      $settings['config_id'] = $options['config'];
      // Attempt to create an instance with this plugin ID and settings.
      try {
        return $this->manager->createInstance($plugin_id, $settings);
      }
      catch (PluginException $e) {
        return FALSE;
      }
    }
    return FALSE;
  }

}
