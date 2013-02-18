<?php

/**
 * @file
 * Contains \Drupal\rest\Plugin\views\style\Serializer.
 */

namespace Drupal\rest\Plugin\views\style;

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @Plugin(
 *   id = "serializer",
 *   module = "rest",
 *   title = @Translation("Serializer"),
 *   help = @Translation("Serializes views row data using the Serializer component."),
 *   type = "data"
 * )
 */
class Serializer extends StylePluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::$usesRowPlugin.
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Overrides Drupal\views\Plugin\views\style\StylePluginBase::$usesFields.
   */
  protected $usesGrouping = FALSE;

  /**
   * The serializer which serializes the views result.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Get the serializer service.
    $this->serializer = drupal_container()->get('serializer');
  }

  /**
   * Overrides \Drupal\views\Plugin\views\style\StylePluginBase::render().
   */
  public function render() {
    $rows = array();
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.
    foreach ($this->view->result as $row) {
      $rows[] = $this->row_plugin->render($row);
    }

    return $this->serializer->serialize($rows, $this->displayHandler->getContentType());
  }

}
