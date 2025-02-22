<?php

declare(strict_types=1);

namespace Drupal\views_test_data\Plugin\views\display_extender;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsDisplayExtender;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Defines a display extender test plugin.
 */
#[ViewsDisplayExtender(
  id: 'display_extender_test',
  title: new TranslatableMarkup('Display extender test'),
)]
class DisplayExtenderTest extends DisplayExtenderPluginBase {

  /**
   * Stores some state booleans to be sure a certain method got called.
   *
   * @var array
   */
  public $testState;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['test_extender_test_option'] = ['default' => 'Empty'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['display_extender_test'] = [
      'title' => 'Display extender test settings',
      'column' => 'second',
      'build' => [
        '#weight' => -100,
      ],
    ];

    $options['test_extender_test_option'] = [
      'category' => 'display_extender_test',
      'title' => 'Test option',
      'value' => Unicode::truncate($this->options['test_extender_test_option'], 24, FALSE, TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    switch ($form_state->get('section')) {
      case 'test_extender_test_option':
        $form['#title'] .= 'Test option';
        $form['test_extender_test_option'] = [
          '#title' => 'Test option',
          '#type' => 'textfield',
          '#description' => 'This is a textfield for test_option.',
          '#default_value' => $this->options['test_extender_test_option'],
        ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'test_extender_test_option':
        $this->options['test_extender_test_option'] = $form_state->getValue('test_extender_test_option');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultableSections(&$sections, $section = NULL) {
    $sections['test_extender_test_option'] = ['test_extender_test_option'];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->testState['query'] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preExecute() {
    $this->testState['preExecute'] = TRUE;
  }

}
