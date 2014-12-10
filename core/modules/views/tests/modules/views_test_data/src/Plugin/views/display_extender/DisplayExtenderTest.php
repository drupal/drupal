<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\display_extender\DisplayExtenderTest.
 */

namespace Drupal\views_test_data\Plugin\views\display_extender;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Defines a display extender test plugin.
 *
 * @ViewsDisplayExtender(
 *   id = "display_extender_test",
 *   title = @Translation("Display extender test")
 * )
 */
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
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['display_extender_test'] = array(
      'title' => $this->t('Display extender test settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -100,
      ),
    );

    $options['test_extender_test_option'] = array(
      'category' => 'display_extender_test',
      'title' => $this->t('Test option'),
      'value' => views_ui_truncate($this->options['test_extender_test_option'], 24),
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    switch ($form_state->get('section')) {
      case 'test_extender_test_option':
        $form['#title'] .= $this->t('Test option');
        $form['test_extender_test_option'] = array(
          '#title' => $this->t('Test option'),
          '#type' => 'textfield',
          '#description' => $this->t('This is a textfield for test_option.'),
          '#default_value' => $this->options['test_extender_test_option'],
        );
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayExtenderPluginBase::submitOptionsForm().
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
   * Overrides Drupal\views\Plugin\views\display\DisplayExtenderPluginBase::defaultableSections().
   */
  public function defaultableSections(&$sections, $section = NULL) {
    $sections['test_extender_test_option'] = array('test_extender_test_option');
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayExtenderPluginBase::query().
   */
  public function query() {
    $this->testState['query'] = TRUE;
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayExtenderPluginBase::preExecute().
   */
  public function preExecute() {
    $this->testState['preExecute'] = TRUE;
  }

}
