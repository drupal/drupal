<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\editor\EditorInterface;

/**
 * Provides the interface for a plugin manager of CKEditor 5 plugins.
 *
 * @see \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface
 * @see \Drupal\ckeditor5\Annotation\CKEditor5Plugin
 * @see plugin_api
 */
interface CKEditor5PluginManagerInterface extends DiscoveryInterface {

  /**
   * Returns a CKEditor 5 plugin with configuration from the editor.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\editor\EditorInterface|null $editor
   *   The editor to load configuration from.
   *
   * @return \Drupal\ckeditor5\Plugin\CKEditor5PluginInterface
   *   The CKEditor 5 plugin instance.
   */
  public function getPlugin(string $plugin_id, ?EditorInterface $editor): CKEditor5PluginInterface;

  /**
   * Gets a list of all toolbar items.
   *
   * @return string[]
   *   List of all toolbar items provided by plugins.
   */
  public function getToolbarItems(): array;

  /**
   * Gets a list of all admin library names.
   *
   * @return string[]
   *   List of all admin libraries provided by plugins.
   */
  public function getAdminLibraries(): array;

  /**
   * Gets a list of libraries required for the editor.
   *
   * This list is filtered by enabled plugins because it is needed at runtime.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return string[]
   *   The list of enabled libraries.
   */
  public function getEnabledLibraries(EditorInterface $editor): array;

  /**
   * Filter list of definitions by enabled plugins only.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return array
   *   Enabled plugin definitions.
   */
  public function getEnabledDefinitions(EditorInterface $editor): array;

  /**
   * Searches for CKEditor 5 plugin that supports a given tag.
   *
   * @param string $tag
   *   The HTML tag to be searched for within plugin definitions.
   *
   * @return string|null
   *   The ID of the plugin that supports the given tag.
   */
  public function findPluginSupportingElement(string $tag): ?string;

  /**
   * Gets the configuration for the CKEditor 5 plugins enabled in this editor.
   *
   * @param \Drupal\editor\EditorInterface $editor
   *   A configured text editor object.
   *
   * @return array[]
   *   An array with two key-value pairs:
   *   1. 'plugins' lists all plugins to load
   *   2. 'config' lists the configuration for all these plugins.
   *
   * @see https://ckeditor.com/docs/ckeditor5/latest/api/module_editor-classic_classiceditor-ClassicEditor.html
   *
   * @see \Drupal\ckeditor5\Plugin\Editor\CKEditor5::getJSSettings()
   */
  public function getCKEditor5PluginConfig(EditorInterface $editor): array;

  /**
   * Gets all supported elements for the given plugins and text editor.
   *
   * @param string[] $plugin_ids
   *   (optional) An array of CKEditor 5 plugin IDs. When not set, gets elements
   *   for all plugins.
   * @param \Drupal\editor\EditorInterface|null $editor
   *   (optional) A configured text editor object using CKEditor 5. When not
   *   set, plugins depending on the text editor cannot provide elements.
   * @param bool $resolve_wildcards
   *   (optional) Whether to resolve wildcards. Defaults to TRUE. When set to
   *   FALSE, the raw allowed elements will be returned (with no processing
   *   applied hence no resolved wildcards).
   *
   * @return array
   *   A nested array with a structure as described in
   *   \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions().
   *
   * @throws \LogicException
   *   Thrown when an invalid CKEditor5PluginElementsSubsetInterface
   *   implementation is encountered.
   *
   * @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
   */
  public function getProvidedElements(array $plugin_ids = [], EditorInterface $editor = NULL, bool $resolve_wildcards = TRUE): array;

}
