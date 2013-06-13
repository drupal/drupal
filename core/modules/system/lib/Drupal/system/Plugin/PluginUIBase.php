<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\PluginUIBase.
 */

namespace Drupal\system\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;

/**
 * Provides defaults for creating user interfaces for plugins of a given type.
 *
 * @todo This class needs more documetation and/or @see references.
 */
abstract class PluginUIBase extends PluginBase implements PluginUIInterface {

  /**
   * Implements \Drupal\system\Plugin\PluginUIInterface::form().
   */
  public function form($form, &$form_state) {
    $plugin_definition = $this->getPluginDefinition();
    // @todo Find out how to let the manager be injected into the class.
    if (class_exists($plugin_definition['manager'])) {
      $manager = new $plugin_definition['manager']();
    }
    else {
      $manager = drupal_container()->get($plugin_definition['manager']);
    }
    $plugins = $manager->getDefinitions();

    $rows = array();
    foreach ($plugins as $plugin_id => $display_plugin_definition) {
      $rows[] = $this->row($plugin_id, $display_plugin_definition);
    }
    $form['plugins'] = array(
      '#theme' => 'table',
      '#header' => $this->tableHeader(),
      '#rows' => $rows,
    );

    return $form;
  }

  /**
   * Implements \Drupal\system\Plugin\PluginUIInterface::formValidate().
   */
  public function formValidate($form, &$form_state) {
  }

  /**
   * Implements \Drupal\system\Plugin\PluginUIInterface::formSumbit().
   */
  public function formSubmit($form, &$form_state) {
  }

  /**
   * Checks access for plugins of this type.
   *
   * @return bool
   *   Returns TRUE if plugins of this type can be accessed.
   */
  public function access() {
    $definition = $this->getPluginDefinition();
    return call_user_func_array($definition['access_callback'], $definition['access_arguments']);
  }

  /**
   * Displays a plugin row for configuring plugins in the user interface.
   *
   * @param string $display_plugin_id
   *   The ID of the specific plugin definition being passed to us.
   * @param array $display_plugin_definition
   *   The plugin definition associated with the passed $plugin_id.
   *
   * @return array
   *   An array that represents a table row in the final user interface output.
   */
  public function row($display_plugin_id, array $display_plugin_definition) {
    $plugin_definition = $this->getPluginDefinition();
    return array($display_plugin_definition['title'], l($plugin_definition['link_title'], $plugin_definition['config_path'] . '/' . $display_plugin_id));
  }

  /**
   * Provides a theme_table compatible array of headers.
   *
   * @return array
   *   A theme_table compatible array of headers.
   */
  public function tableHeader() {
    return array(t('Title'), t('Operations'));
  }

}
