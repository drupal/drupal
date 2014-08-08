<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestControllerObject.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a test form object.
 */
class FormTestControllerObject extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_test_controller_object';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    drupal_set_message(t('The FormTestControllerObject::create() method was used for this form.'));
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $custom_attributes = NULL) {
    $form['element'] = array('#markup' => 'The FormTestControllerObject::buildForm() method was used for this form.');

    $form['custom_attribute']['#markup'] = $custom_attributes;
    $form['request_attribute']['#markup'] = $this->getRequest()->attributes->get('request_attribute');

    $form['bananas'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Bananas'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The FormTestControllerObject::validateForm() method was used for this form.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The FormTestControllerObject::submitForm() method was used for this form.'));
    $this->config('form_test.object')
      ->set('bananas', $form_state->getValue('bananas'))
      ->save();
  }

}
