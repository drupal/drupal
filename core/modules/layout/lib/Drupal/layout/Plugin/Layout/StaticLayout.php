<?php

/**
 * @file
 * Contains \Drupal\layout\Plugin\Layout\StaticLayout.
 */

namespace Drupal\layout\Plugin\Layout;

use Drupal\layout\Plugin\LayoutInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Annotation\Plugin;

/**
 * @Plugin(
 *  id = "static_layout",
 *  derivative = "Drupal\layout\Plugin\Derivative\Layout"
 * )
 */
class StaticLayout extends PluginBase implements LayoutInterface {

  /**
   * Overrides Drupal\Component\Plugin\PluginBase::__construct().
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    foreach ($plugin_definition['regions'] as $region => $title) {
      if (!isset($configuration['regions'][$region])) {
        $configuration['regions'][$region] = array();
      }
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Implements Drupal\layout\Plugin\LayoutInterface::getRegions().
   */
  public function getRegions() {
    $definition = $this->getPluginDefinition();
    return $definition['regions'];
  }

  /**
   * Returns the list of CSS files associated with this layout.
   */
  public function getStylesheetFiles() {
    $definition = $this->getPluginDefinition();
    return isset($definition['stylesheets']) ? $definition['stylesheets'] : array();
  }

  /**
   * Returns the list of administrative CSS files associated with this layout.
   */
  public function getAdminStylesheetFiles() {
    $definition = $this->getPluginDefinition();
    // Fall back on regular CSS for the admin page if admin CSS not provided.
    return isset($definition['admin stylesheets']) ? $definition['admin stylesheets'] : $this->getStylesheetFiles();
  }

  /**
   * Returns the list of JS files associated with this layout.
   */
  public function getScriptFiles() {
    $definition = $this->getPluginDefinition();
    return isset($definition['scripts']) ? $definition['scripts'] : array();
  }

  /**
   * Returns the list of administrative JS files associated with this layout.
   */
  public function getAdminScriptFiles() {
    $definition = $this->getPluginDefinition();
    return isset($definition['admin scripts']) ? $definition['admin scripts'] : $this->getScriptFiles();
  }

  /**
   * Implements Drupal\layout\Plugin\LayoutInterface::renderLayout().
   */
  public function renderLayout($admin = FALSE, $regions = array()) {
    $definition = $this->getPluginDefinition();

    // Assemble a render array with the regions and attached CSS/JS.
    $build = array(
      '#theme' => $definition['theme'],
      '#content' => array(),
      '#attributes' => array(
        'class' => drupal_html_class($definition['theme']),
      ),
    );

    // Render all regions needed for this layout.
    foreach ($this->getRegions() as $region => $info) {
      // Initialize regions which were not provided as empty.
      $build['#content'][$region] = empty($regions[$region]) ? '' : $regions[$region];
    }

    // Fill in attached CSS and JS files based on metadata.
    if (!$admin) {
      $build['#attached'] = array(
        'css' => $this->getStylesheetFiles(),
        'js' => $this->getScriptFiles(),
      );
    }
    else {
      $build['#attached'] = array(
        'css' => $this->getAdminStylesheetFiles(),
        'js' => $this->getAdminScriptFiles(),
      );
    }

    // Include the path of the definition in all CSS and JS files.
    foreach (array('css', 'js') as $type) {
      foreach ($build['#attached'][$type] as &$filename) {
        $filename = $definition['path'] . '/' . $filename;
      }
    }

    return drupal_render($build);
  }
}
