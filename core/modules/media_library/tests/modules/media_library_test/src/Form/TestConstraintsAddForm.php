<?php

namespace Drupal\media_library_test\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;

/**
 * Test class that extends AddFormBase.
 *
 * This class acts as a stand-in for contributed code that might extend
 * AddFormBase. The presence of this class and tests on this class will
 * help us understand what effects changes to AddFormBase may have on
 * contributed modules.
 */
class TestConstraintsAddForm extends AddFormBase {

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];
    $form['container']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add Test Media'),
      '#required' => TRUE,
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateName'],
      '#submit' => ['::addButtonSubmit'],
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Validates the media name.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateName(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    if (strpos($name, 'love Drupal') === FALSE) {
      $form_state->setErrorByName('name', 'Text is not appropriate.');
    }
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $this->processInputValues([$form_state->getValue('name')], $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_constraints_add_form';
  }

}
