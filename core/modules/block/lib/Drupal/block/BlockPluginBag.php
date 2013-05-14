<?php

/**
 * @file
 * Contains \Drupal\block\BlockPluginBag.
 */

namespace Drupal\block;

use Drupal\block\Plugin\Core\Entity\Block;
use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Provides a collection of block plugins.
 */
class BlockPluginBag extends PluginBag {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a BlockPluginBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $instance_ids
   *   The ids of the plugin instances with which we are dealing.
   * @param \Drupal\block\Plugin\Core\Entity\Block $entity
   *   The Block entity that holds our configuration.
   */
  public function __construct(PluginManagerInterface $manager, array $instance_ids, Block $entity) {
    $this->manager = $manager;
    $this->entity = $entity;

    $this->instanceIDs = drupal_map_assoc($instance_ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException(format_string("The block '@block' did not specify a plugin.", array('@block' => $this->entity->id())));
    }
    if (isset($this->pluginInstances[$instance_id])) {
      return;
    }

    $settings = $this->entity->get('settings');
    try {
      $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, $settings);
    }
    catch (PluginException $e) {
      $module = $settings['module'];
      // Ignore blocks belonging to disabled modules, but re-throw valid
      // exceptions when the module is enabled and the plugin is misconfigured.
      if (!$module || \Drupal::moduleHandler()->moduleExists($module)) {
        throw $e;
      }
    }
  }

}
