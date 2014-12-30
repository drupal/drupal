<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkDefault.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default implementation for menu link plugins.
 */
class MenuLinkDefault extends MenuLinkBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = array(
    'menu_name' => 1,
    'parent' => 1,
    'weight' => 1,
    'expanded' => 1,
    'enabled' => 1,
  );

  /**
   * The static menu link service used to store updates to weight/parent etc.
   *
   * @var \Drupal\Core\Menu\StaticMenuLinkOverridesInterface
   */
  protected $staticOverride;

  /**
   * Constructs a new MenuLinkDefault.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->staticOverride = $static_override;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // Subclasses may pull in the request or specific attributes as parameters.
    $options = array();
    if (!empty($this->pluginDefinition['title_context'])) {
      $options['context'] = $this->pluginDefinition['title_context'];
    }
    $args = array();
    if (isset($this->pluginDefinition['title_arguments']) && $title_arguments = $this->pluginDefinition['title_arguments']) {
      $args = (array) $title_arguments;
    }
    return $this->t($this->pluginDefinition['title'], $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($this->pluginDefinition['description']) {
      return $this->t($this->pluginDefinition['description']);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable() {
    // The link can be reset if it has an override.
    return (bool) $this->staticOverride->loadOverride($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $new_definition_values, $persist) {
    // Filter the list of updates to only those that are allowed.
    $overrides = array_intersect_key($new_definition_values, $this->overrideAllowed);
    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();
    if ($persist) {
      // Always save the menu name as an override to avoid defaulting to tools.
      $overrides['menu_name'] = $this->pluginDefinition['menu_name'];
      $this->staticOverride->saveOverride($this->getPluginId(), $overrides);
    }
    return $this->pluginDefinition;
  }

}
