<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form to test input forgery.
 *
 * @internal
 */
class FormTestInputForgeryForm extends FormBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_input_forgery';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // For testing that a user can't submit a value not matching one of the
    // allowed options.
    $form['checkboxes'] = [
      '#title' => t('Checkboxes'),
      '#type' => 'checkboxes',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['#post_render'][] = [static::class, 'postRender'];

    return $form;
  }

  /**
   * Alters the rendered form to simulate input forgery.
   *
   * It's necessary to alter the rendered form here because Mink does not
   * support manipulating the DOM tree.
   *
   * @param string $rendered_form
   *   The rendered form.
   *
   * @return string
   *   The modified rendered form.
   *
   * @see \Drupal\Tests\system\Functional\Form\FormTest::testInputForgery()
   */
  public static function postRender($rendered_form) {
    return str_replace('value="two"', 'value="FORGERY"', (string) $rendered_form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return new JsonResponse($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['postRender'];
  }

}
