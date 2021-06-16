<?php

namespace Drupal\media_library_form_overwrite_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\media_library\Form\AddFormBase;

/**
 * Test add form.
 */
class TestAddForm extends AddFormBase {

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_add_form';
  }

}
