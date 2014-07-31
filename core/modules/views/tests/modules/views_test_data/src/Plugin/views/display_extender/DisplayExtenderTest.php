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
   * Overrides Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase::defineOptionsAlter().
   */
  public function defineOptionsAlter(&$options) {
    $options['test_extender_test_option'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['display_extender_test'] = array(
      'title' => t('Display extender test settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -100,
      ),
    );

    $test_option = $this->displayHandler->getOption('test_extender_test_option') ?: t('Empty');

    $options['test_extender_test_option'] = array(
      'category' => 'display_extender_test',
      'title' => t('Test option'),
      'value' => views_ui_truncate($test_option, 24),
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    switch ($form_state['section']) {
      case 'test_extender_test_option':
        $form['#title'] .= t('Test option');
        $form['test_extender_test_option'] = array(
          '#title' => t('Test option'),
          '#type' => 'textfield',
          '#description' => t('This is a textfield for test_option.'),
          '#default_value' => $this->displayHandler->getOption('test_extender_test_option'),
        );
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayExtenderPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state['section']) {
      case 'test_extender_test_option':
        $this->displayHandler->setOption('test_extender_test_option', $form_state['values']['test_extender_test_option']);
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
