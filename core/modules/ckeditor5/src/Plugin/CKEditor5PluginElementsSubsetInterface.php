<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin;

/**
 * Defines an interface for plugins that can support an elements subset.
 *
 * Plugins can support multiple elements in the `elements` property of their
 * definition. A text format may want to use a given plugin without supporting
 * every supported element. Plugins that implement this interface return a
 * subset based on the configuration in the Text Editor's settings.
 */
interface CKEditor5PluginElementsSubsetInterface extends CKEditor5PluginConfigurableInterface {

  /**
   * Returns a configured subset of the elements supported by this plugin.
   *
   * @return string[]
   *   An array of supported elements.
   */
  public function getElementsSubset(): array;

}
