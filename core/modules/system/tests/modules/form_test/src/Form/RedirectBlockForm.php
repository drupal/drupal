<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form that redirects on submit.
 *
 * @see \Drupal\form_test\Plugin\Block\RedirectFormBlock
 */
class RedirectBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Submit')];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('form_test.route1', [], ['query' => ['test1' => 'test2']]);
  }

}
