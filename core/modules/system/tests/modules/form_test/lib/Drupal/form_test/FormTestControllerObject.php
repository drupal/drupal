<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestControllerObject.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\ControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a test form object.
 */
class FormTestControllerObject implements FormInterface, ControllerInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'form_test_form_test_controller_object';
  }

  /**
   * Implements \Drupal\Core\ControllerInterface::create().
   */
  public static function create(ContainerInterface $container) {
    drupal_set_message(t('The FormTestControllerObject::create() method was used for this form.'));
    return new static();
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $custom_attributes = NULL, Request $request = NULL) {
    $form['element'] = array('#markup' => 'The FormTestControllerObject::buildForm() method was used for this form.');

    $form['custom_attribute']['#markup'] = $custom_attributes;
    $form['request_attribute']['#markup'] = $request->attributes->get('request_attribute');

    $form['bananas'] = array(
      '#type' => 'textfield',
      '#title' => t('Bananas'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    drupal_set_message(t('The FormTestControllerObject::validateForm() method was used for this form.'));
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('The FormTestControllerObject::submitForm() method was used for this form.'));
    config('form_test.object')
      ->set('bananas', $form_state['values']['bananas'])
      ->save();
  }

}
