<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormStateInterface;

class FormTestTableSelectColspanForm extends FormTestTableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_tableselect_colspan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    list($header, $options) = _form_test_tableselect_get_data();

    // Change the data so that the third column has colspan=2.
    $header['three'] = array('data' => 'Three', 'colspan' => 2);
    unset($header['four']);
    // Set the each row so that column 3 is an array.
    foreach ($options as $name => $row) {
      $options[$name]['three'] = array($row['three'], $row['four']);
      unset($options[$name]['four']);
    }
    // Combine cells in row 3.
    $options['row3']['one'] = array('data' => $options['row3']['one'], 'colspan' => 2);
    unset($options['row3']['two']);
    $options['row3']['three'] = array('data' => $options['row3']['three'][0], 'colspan' => 2);
    unset($options['row3']['four']);

    return $this->tableselectFormBuilder($form, $form_state, array('#header' => $header, '#options' => $options));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
