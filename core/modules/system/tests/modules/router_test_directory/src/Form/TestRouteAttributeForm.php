<?php

declare(strict_types=1);

namespace Drupal\router_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Test form object for Route attribute.
 */
#[Route(
  path: '/test-form-route',
  name: 'router_test.form_route',
  requirements: ['_access' => 'TRUE'],
)]
class TestRouteAttributeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'router_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['button'] = [
      '#type' => 'submit',
      '#value' => 'Click here',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
