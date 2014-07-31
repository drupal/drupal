<?php

/**
 * @file
 * Definition of Drupal\views_test_data\Plugin\views\display\DisplayTest.
 */

namespace Drupal\views_test_data\Plugin\views\display;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;

/**
 * Defines a Display test plugin.
 *
 * @ViewsDisplay(
 *   id = "display_test",
 *   title = @Translation("Display test"),
 *   help = @Translation("Defines a display test plugin."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   contextual_links_locations = {"view"}
 * )
 */
class DisplayTest extends DisplayPluginBase {

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   */
  protected $usesAttachments = TRUE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getType().
   */
  protected function getType() {
    return 'test';
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['test_option'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummaryv().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['display_test'] = array(
      'title' => t('Display test settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -100,
      ),
    );

    $test_option = $this->getOption('test_option') ?: t('Empty');

    $options['test_option'] = array(
      'category' => 'display_test',
      'title' => t('Test option'),
      'value' => views_ui_truncate($test_option, 24),
    );
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state['section']) {
      case 'test_option':
        $form['#title'] .= t('Test option');
        $form['test_option'] = array(
          '#title' => t('Test option'),
          '#type' => 'textfield',
          '#description' => t('This is a textfield for test_option.'),
          '#default_value' => $this->getOption('test_option'),
        );
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::validateOptionsForm().
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    watchdog('views', $form_state['values']['test_option']);
    switch ($form_state['section']) {
      case 'test_option':
        if (!trim($form_state['values']['test_option'])) {
          form_error($form['test_option'], $form_state, t('You cannot have an empty option.'));
        }
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::submitOptionsForm().
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state['section']) {
      case 'test_option':
        $this->setOption('test_option', $form_state['values']['test_option']);
        break;
    }
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::execute().
   */
  public function execute() {
    $this->view->build();

    $render = $this->view->render();
    // Render the test option as the title before the view output.
    $render['#prefix'] = '<h1>' . Xss::filterAdmin($this->options['test_option']) . '</h1>';

    return $render;
  }

  /**
   * Overrides Drupal\views\Plugin\views\display\DisplayPluginBase::preview().
   *
   * Override so preview and execute are the same output.
   */
  public function preview() {
    return $this->execute();
  }

}
