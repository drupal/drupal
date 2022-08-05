<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

// cspell:ignore enableable

use Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition;
use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common functionality for many CKEditor 5 validation constraints.
 *
 * @internal
 */
trait PluginManagerDependentValidatorTrait {

  /**
   * The CKEditor 5 plugin manager.
   *
   * @var \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a CKEditor5ConstraintValidatorTrait object.
   *
   * @param \Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface $plugin_manager
   *   The CKEditor 5 plugin manager.
   */
  public function __construct(CKEditor5PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.ckeditor5.plugin')
    );
  }

  /**
   * Gets all other enabled CKEditor 5 plugin definitions.
   *
   * @param \Drupal\editor\EditorInterface $text_editor
   *   A Text Editor config entity configured to use CKEditor 5.
   * @param string $except
   *   A CKEditor 5 plugin ID to exclude: all enabled plugins other than this
   *   one are returned.
   *
   * @return \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[]
   *   A list of CKEditor 5 plugin definitions keyed by plugin ID.
   */
  private function getOtherEnabledPlugins(EditorInterface $text_editor, string $except): array {
    $enabled_plugins = $this->pluginManager->getEnabledDefinitions($text_editor);
    unset($enabled_plugins[$except]);
    return $enabled_plugins;
  }

  /**
   * Gets all disabled CKEditor 5 plugin definitions the user can enable.
   *
   * @param \Drupal\editor\EditorInterface $text_editor
   *   A Text Editor config entity configured to use CKEditor 5.
   *
   * @return \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition[]
   *   A list of CKEditor 5 plugin definitions keyed by plugin ID.
   */
  private function getEnableableDisabledPlugins(EditorInterface $text_editor) {
    $disabled_plugins = array_diff_key(
      $this->pluginManager->getDefinitions(),
      $this->pluginManager->getEnabledDefinitions($text_editor)
    );
    // Only consider plugins that can be explicitly enabled by the user: plugins
    // that have a toolbar item and do not have conditions. Those are the only
    // plugins that are truly available for the site builder to enable without
    // other consequences.
    // In the future, we may choose to expand this, but it will require complex
    // infrastructure to generate messages that explain which of the conditions
    // are already fulfilled and which are not.
    $enableable_disabled_plugins = array_filter($disabled_plugins, function (CKEditor5PluginDefinition $definition) {
      return $definition->hasToolbarItems() && !$definition->hasConditions();
    });
    return $enableable_disabled_plugins;
  }

}
