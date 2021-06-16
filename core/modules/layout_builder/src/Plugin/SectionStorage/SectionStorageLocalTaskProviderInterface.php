<?php

namespace Drupal\layout_builder\Plugin\SectionStorage;

/**
 * Allows section storage plugins to provide local tasks.
 *
 * @see \Drupal\layout_builder\Plugin\Derivative\LayoutBuilderLocalTaskDeriver
 * @see \Drupal\layout_builder\SectionStorageInterface
 */
interface SectionStorageLocalTaskProviderInterface {

  /**
   * Provides the local tasks dynamically for Layout Builder plugins.
   *
   * @param mixed $base_plugin_definition
   *   The definition of the base plugin.
   *
   * @return array
   *   An array of full derivative definitions keyed on derivative ID.
   */
  public function buildLocalTasks($base_plugin_definition);

}
