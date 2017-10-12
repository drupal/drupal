<?php

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;

/**
 * Displays analysis information for a view.
 *
 * @internal
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');

    $form['#title'] = $this->t('View analysis');
    $form['#section'] = 'analyze';

    $analyzer = Views::analyzer();
    $messages = $analyzer->getMessages($view->getExecutable());

    $form['analysis'] = [
      '#prefix' => '<div class="js-form-item form-item">',
      '#suffix' => '</div>',
      '#markup' => $analyzer->formatMessages($messages),
    ];

    // Inform the standard button function that we want an OK button.
    $form_state->set('ok_button', TRUE);
    $view->getStandardButtons($form, $form_state, 'views_ui_analyze_view_form');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var $view \Drupal\views_ui\ViewUI */
    $view = $form_state->get('view');
    $form_state->setRedirectUrl($view->urlInfo('edit-form'));
  }

}
