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
   * Overrides
   * Drupal\views\Plugin\views\display\DisplayPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['test_option'] = array('default' => '');

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    $categories['display_test'] = array(
      'title' => $this->t('Display test settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -100,
      ),
    );

    $test_option = $this->getOption('test_option') ?: $this->t('Empty');

    $options['test_option'] = array(
      'category' => 'display_test',
      'title' => $this->t('Test option'),
      'value' => views_ui_truncate($test_option, 24),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    switch ($form_state->get('section')) {
      case 'test_option':
        $form['#title'] .= $this->t('Test option');
        $form['test_option'] = array(
          '#title' => $this->t('Test option'),
          '#type' => 'textfield',
          '#description' => $this->t('This is a textfield for test_option.'),
          '#default_value' => $this->getOption('test_option'),
        );
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);
    \Drupal::logger('views')->notice($form_state->getValue('test_option'));
    switch ($form_state->get('section')) {
      case 'test_option':
        if (!trim($form_state->getValue('test_option'))) {
          $form_state->setError($form['test_option'], $this->t('You cannot have an empty option.'));
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    switch ($form_state->get('section')) {
      case 'test_option':
        $this->setOption('test_option', $form_state->getValue('test_option'));
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->view->build();

    $render = $this->view->render();
    // Render the test option as the title before the view output.
    $render['#prefix'] = '<h1>' . Xss::filterAdmin($this->options['test_option']) . '</h1>';

    return $render;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return $this->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
      'content' => ['DisplayTest'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    foreach ($this->view->displayHandlers as $display_handler) {
      $errors[] = 'error';
    }
    return $errors;
  }

}
