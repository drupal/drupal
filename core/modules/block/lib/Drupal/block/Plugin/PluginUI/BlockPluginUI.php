<?php

/**
 * Contains \Drupal\block\Plugin\PluginUI\BlockPluginUI.
 */

namespace Drupal\block\Plugin\PluginUI;

use Drupal\system\Plugin\PluginUIBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines an overrideable UI for block selection, configuration, and placement.
 *
 * @Plugin(
 *   id = "block_plugin_ui",
 *   module = "block",
 *   all_plugins = @Translation("All Blocks"),
 *   config_path = "admin/structure/block/add",
 *   default_task = TRUE,
 *   derivative = "Drupal\block\Plugin\Derivative\BlockPluginUI",
 *   facets = {
 *     "module" = @Translation("Modules")
 *   },
 *   link_title = @Translation("Place block"),
 *   manager = "plugin.manager.block",
 *   menu = TRUE,
 *   path = "admin/structure/block/list",
 *   suffix = "add",
 *   task_suffix = "library",
 *   task_title = @Translation("Library"),
 *   title = @Translation("Place blocks"),
 *   title_attribute = "admin_label",
 *   type = MENU_LOCAL_ACTION
 * )
 */
class BlockPluginUI extends PluginUIBase {

  /**
   * Overrides \Drupal\system\Plugin\PluginUIBase::form().
   *
   * @todo Add inline documentation to this method.
   */
  public function form($form, &$form_state, $facet = NULL) {
    // @todo Add an inline comment here.
    list($plugin, $theme) = explode(':', $this->getPluginId());
    $plugin_definition = $this->getPluginDefinition();
    // @todo Find out how to let the manager be injected into the class.
    $manager = drupal_container()->get($plugin_definition['manager']);
    $plugins = $manager->getDefinitions();
    $form['#theme'] = 'system_plugin_ui_form';
    $form['theme'] = array(
      '#type' => 'value',
      '#value' => $theme,
    );
    $form['manager'] = array(
      '#type' => 'value',
      '#value' => $manager,
    );
    $form['instance'] = array(
      '#type' => 'value',
      '#value' => $this,
    );
    $form['right']['block'] = array(
      '#type' => 'textfield',
      '#title' => t('Search'),
      '#autocomplete_path' => 'system/autocomplete/' . $this->getPluginId(),
    );
    $form['right']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Next'),
    );
    $rows = array();
    foreach ($plugins as $plugin_id => $display_plugin_definition) {
      if (empty($facet) || $this->facetCompare($facet, $display_plugin_definition)) {
        $rows[$plugin_id] = $this->row($plugin_id, $display_plugin_definition);
      }
      foreach ($plugin_definition['facets'] as $key => $title) {
        $facets[$key][$display_plugin_definition[$key]] = $this->facetLink($key, $plugin_id, $display_plugin_definition);
      }
      $form['right']['all_plugins'] = array(
        '#type' => 'link',
        '#title' => $plugin_definition['all_plugins'],
        '#href' => $this->allPluginsUrl($plugin_id, $display_plugin_definition),
      );
      foreach ($facets as $group => $values) {
        $form['right'][$group] = array(
          '#theme' => 'links',
          '#heading' => array(
            'text' => $plugin_definition['facets'][$group],
            'level' => 'h3',
          ),
          '#links' => $values,
        );
      }
    }
    // Sort rows alphabetically.
    asort($rows);
    $form['left']['plugin_library'] = array(
      '#theme' => 'table',
      '#header' => $this->tableHeader(),
      '#rows' => $rows,
    );
    return $form;
  }

  /**
   * Overrides \Drupal\system\Plugin\PluginUIBase::formValidate().
   */
  public function formValidate($form, &$form_state) {
    $definitions = $form_state['values']['manager']->getDefinitions();
    if (!isset($definitions[$form_state['values']['block']])) {
      form_set_error('block', t('You must select a valid block.'));
    }
  }

  /**
   * Overrides \Drupal\system\Plugin\PluginUIBase::formSubmit().
   */
  public function formSubmit($form, &$form_state) {
    $form_state['redirect'] = 'admin/structure/block/add/' . $form_state['values']['block'] . '/' . $form_state['values']['theme'];
  }

  /**
   * Overrides \Drupal\system\Plugin\PluginUIBase::access().
   */
  public function access() {
    list($plugin, $theme) = explode(':', $this->getPluginId());
    return _block_themes_access($theme);
  }

  /**
   * Overrides \Drupal\system\Plugin\PluginUIBase::tableHeader().
   */
  public function tableHeader() {
    return array(t('Subject'), t('Operations'));
  }

  /**
   * Overrides \Drupal\system\Plugin\PluginUIBase::row().
   */
  public function row($display_plugin_id, array $display_plugin_definition) {
    $plugin_definition = $this->getPluginDefinition();
    list($plugin, $theme) = explode(':', $this->getPluginId());
    $row = array();
    $row[] = check_plain($display_plugin_definition['admin_label']);
    $row[] = array('data' => array(
      '#type' => 'operations',
      '#links' => array(
        'configure' => array(
          'title' => $plugin_definition['link_title'],
          'href' => $plugin_definition['config_path'] . '/' . $display_plugin_id . '/' . $theme,
        ),
      ),
    ));
    return $row;
  }

  /**
   * Creates a facet link for a given facet of a display plugin.
   *
   * Provides individually formatted links for the faceting that happens within
   * the user interface. Since this is a faceting style procedure, each plugin
   * may be parsed multiple times in order to extract all facets and their
   * appropriate labels.
   *
   * The $display_plugin_id and $display_plugin_definition are provided for
   * convenience when overriding this method.
   *
   * @param string $facet
   *   A simple string indicating what element of the $display_plugin_definition
   *   to utilize for faceting.
   * @param string $display_plugin_id
   *   The plugin ID of the plugin we are currently parsing a facet link from.
   * @param array $display_plugin_definition
   *   The plugin definition we are parsing.
   *
   * @return array
   *   Returns a row array comaptible with theme_links().
   */
  protected function facetLink($facet, $display_plugin_id, array $display_plugin_definition) {
    $plugin_definition = $this->getPluginDefinition();
    return array(
      'title' => $display_plugin_definition[$facet],
      'href' => $plugin_definition['path'] . '/' . $this->getPluginId() . '/' . $facet . ':' . $display_plugin_definition[$facet],
    );
  }

  /**
   * Determines whether a given facet should be displayed for a plugin.
   *
   * Compares a given plugin definition with the selected facet to determine if
   * the plugin should be displayed in the user interface.
   *
   * @param string $facet
   *   A colon separated string representing the key/value paring of a selected
   *   facet.
   * @param array $display_plugin_definition
   *   The plugin definition to be compared.
   *
   * @return bool
   *   Returns TRUE if the selected facet matches this plugin.
   */
  protected function facetCompare($facet, $display_plugin_definition) {
    list($facet_type, $option) = explode(':', $facet);
    return $option == $display_plugin_definition[$facet_type];
  }

  /**
   * Provides an "all" style link to reset the facets.
   *
   * The $display_plugin_id and $display_plugin_definition are provided for
   * convenience when overriding this method.
   *
   * @param string $display_plugin_id
   *   The plugin ID of the plugin we are currently parsing a facet link from.
   * @param array $display_plugin_definition
   *   The plugin definition we are parsing.
   *
   * @return string
   *   Returns a simple URL string for use within l().
   */
  protected function allPluginsUrl($display_plugin_id, $display_plugin_definition) {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['path'] . '/' . $this->getPluginId() . '/add';
  }

}
