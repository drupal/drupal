<?php

declare(strict_types=1);

namespace Drupal\form_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\form_test\FormTestObject;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for form_test routes.
 */
class FormTestController extends ControllerBase {

  /**
   * Returns two instances of the node form.
   *
   * @return string
   *   A HTML-formatted string with the double node form page content.
   */
  public function twoFormInstances() {
    $user = $this->currentUser();
    $values = [
      'uid' => $user->id(),
      'name' => $user->getAccountName(),
      'type' => 'page',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $node1 = $this->entityTypeManager()->getStorage('node')->create($values);
    $node2 = clone($node1);
    $return['node_form_1'] = $this->entityFormBuilder()->getForm($node1);
    $return['node_form_2'] = $this->entityFormBuilder()->getForm($node2);
    return $return;
  }

  /**
   * Emulate legacy AHAH-style ajax callback.
   *
   * Drupal 6 AHAH callbacks used to operate directly on forms retrieved using
   * \Drupal::formBuilder()->getCache() and stored using
   * \Drupal::formBuilder()->setCache() after manipulation. This callback helps
   * testing whether \Drupal::formBuilder()->setCache() prevents resaving of
   * immutable forms.
   */
  public function storageLegacyHandler($form_build_id) {
    $form_state = new FormState();
    $form = $this->formBuilder()->getCache($form_build_id, $form_state);
    $result = [
      'form' => $form,
      'form_state' => $form_state,
    ];
    $form['#poisoned'] = TRUE;
    $form_state->set('poisoned', TRUE);
    $this->formBuilder()->setCache($form_build_id, $form, $form_state);
    return new JsonResponse($result);
  }

  /**
   * Returns a form and a button that has the form attribute.
   *
   * @return array
   *   A render array containing the form and the button.
   */
  public function buttonWithFormAttribute(): array {
    $return['form'] = $this->formBuilder()->getForm(FormTestObject::class);
    $return['button'] = [
      '#type' => 'submit',
      '#value' => 'Attribute Button',
      '#attributes' => [
        'form' => 'form-test-form-test-object',
      ],
    ];
    return $return;
  }

}
