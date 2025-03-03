<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Provides a general test style plugin.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "test_style",
  title: new TranslatableMarkup("Test style plugin"),
  help: new TranslatableMarkup("Provides a generic style test plugin."),
  theme: "views_view_style_test",
  register_theme: FALSE,
  display_types: ["normal", "test"],
)]
class StyleTest extends StylePluginBase {

  /**
   * A string which will be output when the view is rendered.
   *
   * @var string
   */
  public $output;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['test_option'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['test_option'] = [
      '#title' => 'Test option',
      '#type' => 'textfield',
      '#description' => 'This is a textfield for test_option.',
      '#default_value' => $this->options['test_option'],
    ];
  }

  /**
   * Sets the usesRowPlugin property.
   *
   * @param bool $status
   *   TRUE if this style plugin should use rows.
   */
  public function setUsesRowPlugin($status) {
    $this->usesRowPlugin = $status;
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
   *   The output of the plugin.
   */
  public function getOutput() {
    return $this->output;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $output = '';
    if (!$this->usesRowPlugin()) {
      $output = $this->getOutput();
    }
    else {
      foreach ($this->view->result as $index => $row) {
        $this->view->row_index = $index;
        $output .= $this->view->rowPlugin->render($row) . "\n";
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'content' => ['StyleTest'],
    ];
  }

}
