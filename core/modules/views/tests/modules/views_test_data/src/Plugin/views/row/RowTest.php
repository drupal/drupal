<?php

namespace Drupal\views_test_data\Plugin\views\row;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsRow;
use Drupal\views\Plugin\views\row\RowPluginBase;

/**
 * Provides a general test row plugin.
 *
 * @ingroup views_row_plugins
 */
#[ViewsRow(
  id: "test_row",
  title: new TranslatableMarkup("Test row plugin"),
  help: new TranslatableMarkup("Provides a generic row test plugin."),
  theme: "views_view_row_test",
  display_types: ["normal", "test"]
)]
class RowTest extends RowPluginBase {

  /**
   * A string which will be output when the view is rendered.
   *
   * @var string
   */
  public $output;

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
      '#title' => $this->t('Test option'),
      '#type' => 'textfield',
      '#description' => $this->t('This is a textfield for test_option.'),
      '#default_value' => $this->options['test_option'],
    ];
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
   * {@inheritdoc}
   */
  public function render($row) {
    return $this->getOutput();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'content' => ['RowTest'],
    ];
  }

}
