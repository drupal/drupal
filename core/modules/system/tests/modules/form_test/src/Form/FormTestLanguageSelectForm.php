<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds a form to test the language select form element.
 */
class FormTestLanguageSelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_language_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['languages_all'] = [
      '#title' => t('Languages: All'),
      '#type' => 'language_select',
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => 'xx',
    ];
    $form['languages_configurable'] = [
      '#title' => t('Languages: Configurable'),
      '#type' => 'language_select',
      '#languages' => LanguageInterface::STATE_CONFIGURABLE,
      '#default_value' => 'en',
    ];
    $form['languages_locked'] = [
      '#title' => t('Languages: Locked'),
      '#type' => 'language_select',
      '#languages' => LanguageInterface::STATE_LOCKED,
    ];
    $form['languages_config_and_locked'] = [
      '#title' => t('Languages: Configurable and locked'),
      '#type' => 'language_select',
      '#languages' => LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_LOCKED,
      '#default_value' => 'dummy_value',
    ];
    $form['language_custom_options'] = [
      '#title' => t('Languages: Custom'),
      '#type' => 'language_select',
      '#languages' => LanguageInterface::STATE_CONFIGURABLE | LanguageInterface::STATE_LOCKED,
      '#options' => ['opt1' => 'First option', 'opt2' => 'Second option', 'opt3' => 'Third option'],
      '#default_value' => 'opt2',
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => 'Submit'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
