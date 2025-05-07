<?php

declare(strict_types=1);

namespace Drupal\media_library_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Form\NodeForm;

/**
 * Override NodeForm to test media library form submission semantics.
 */
class TestNodeFormOverride extends NodeForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (in_array('open_button', $triggering_element['#parents'], TRUE)) {
      throw new \Exception('The media library widget open_button element should not trigger form submit.');
    }
    parent::submitForm($form, $form_state);
  }

}
