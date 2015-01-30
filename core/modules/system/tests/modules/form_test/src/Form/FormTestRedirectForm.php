<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestRedirectForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form builder to detect form redirect.
 */
class FormTestRedirectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_redirect';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['redirection'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use redirection'),
    );
    $form['destination'] = array(
      '#type' => 'textfield',
      '#title' => t('Redirect destination'),
      '#states' => array(
        'visible' => array(
          ':input[name="redirection"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('redirection')) {
      if (!$form_state->isValueEmpty('destination')) {
        // @todo Use Url::fromPath() once https://www.drupal.org/node/2351379 is
        //   resolved.
        $form_state->setRedirectUrl(Url::fromUri('base:' . $form_state->getValue('destination')));
      }
    }
    else {
      $form_state->disableRedirect();
    }
  }

}
