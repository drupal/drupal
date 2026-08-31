<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * A non-static #value_callback target, resolved via the class resolver.
 *
 * @see \Drupal\KernelTests\Core\Form\FormDefaultHandlersTest
 */
class ValueCallbackTestHelper {

  /**
   * Converts the submitted value to upper case.
   *
   * @param array $element
   *   The form element.
   * @param mixed $input
   *   The submitted input, or FALSE if there is none.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return string|null
   *   The input converted to upper case, or NULL.
   */
  public function upperCaseValue(array &$element, mixed $input, FormStateInterface $form_state): ?string {
    return is_string($input) ? strtoupper($input) : NULL;
  }

}
