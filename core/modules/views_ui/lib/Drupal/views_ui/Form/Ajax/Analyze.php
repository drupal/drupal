<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\Analyze.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views\Views;
use Drupal\views_ui\ViewUI;
use Drupal\views\Analyzer;

/**
 * Displays analysis information for a view.
 */
class Analyze extends ViewsFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'analyze';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_analyze_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $view = $form_state['view'];

    $form['#title'] = $this->t('View analysis');
    $form['#section'] = 'analyze';

    $analyzer = Views::analyzer();
    $messages = $analyzer->getMessages($view->getExecutable());

    $form['analysis'] = array(
      '#prefix' => '<div class="form-item">',
      '#suffix' => '</div>',
      '#markup' => $analyzer->formatMessages($messages),
    );

    // Inform the standard button function that we want an OK button.
    $form_state['ok_button'] = TRUE;
    $view->getStandardButtons($form, $form_state, 'views_ui_analyze_view_form');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    /** @var $view \Drupal\views_ui\ViewUI */
    $view = $form_state['view'];
    $form_state['redirect_route'] = $view->urlInfo('edit-form');
  }

}
