<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\row\RowTest.
 */

namespace Drupal\views_test_data\Plugin\views\row;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Provides a general test row plugin.
 *
 * @ingroup views_row_plugins
 *
 * @Plugin(
 *   id = "test_row",
 *   title = @Translation("Test row plugin"),
 *   help = @Translation("Provides a generic row test plugin."),
 *   theme = "views_view_row_test",
 *   module = "views_test_data",
 *   display_types = {"normal", "test"}
 * )
 */
class RowTest extends RowPluginBase {

  /**
   * A string which will be output when the view is rendered.
   *
   * @var string
   */
  public $output;

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['test_option'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['test_option'] = array(
      '#type' => 'textfield',
      '#description' => t('This is a textfield for test_option.'),
      '#default_value' => $this->options['test_option'],
    );
  }

  /**
   * Sets the output property.
   *
   * @param string $output
   *   The string to output by this plugin.
   */
  public function setOutput($output) {
    $this->output = $output;
  }

  /**
   * Returns the output property.
   *
   * @return string
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * Overrides Drupal\views\Plugin\views\row\RowPluginBase::render()
   */
  public function render($row) {
    return $this->getOutput();
  }

}
