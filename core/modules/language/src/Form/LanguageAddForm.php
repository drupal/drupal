<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageAddForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Form\LanguageFormBase;
use Drupal\Core\Language\Language;

/**
 * Controller for language addition forms.
 */
class LanguageAddForm extends LanguageFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // @todo Remove in favour of base method.
    return 'language_admin_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Add language');

    $predefined_languages = $this->languageManager->getStandardLanguageListWithoutConfigured();

    $predefined_languages['custom'] = $this->t('Custom language...');
    $predefined_default = $form_state->getValue('predefined_langcode', key($predefined_languages));
    $form['predefined_langcode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language name'),
      '#default_value' => $predefined_default,
      '#options' => $predefined_languages,
    );
    $form['predefined_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add language'),
      '#limit_validation_errors' => array(array('predefined_langcode'), array('predefined_submit')),
      '#states' => array(
        'invisible' => array(
          'select#edit-predefined-langcode' => array('value' => 'custom'),
        ),
      ),
      '#validate' => array('::validatePredefined'),
      '#submit' => array('::submitForm', '::save'),
      '#button_type' => 'primary',
    );

    $custom_language_states_conditions = array(
      'select#edit-predefined-langcode' => array('value' => 'custom'),
    );
    $form['custom_language'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => $custom_language_states_conditions,
      ),
    );
    $this->commonForm($form['custom_language']);
    $form['custom_language']['langcode']['#states'] = array(
      'required' => $custom_language_states_conditions,
    );
    $form['custom_language']['label']['#states'] = array(
      'required' => $custom_language_states_conditions,
    );
    $form['custom_language']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add custom language'),
      '#validate' => array('::validateCustom'),
      '#submit' => array('::submitForm', '::save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $t_args = array('%language' => $this->entity->label(), '%langcode' => $this->entity->id());
    $this->logger('language')->notice('The %language (%langcode) language has been created.', $t_args);
    drupal_set_message($this->t('The language %language has been created and can now be used.', $t_args));

    if ($this->moduleHandler->moduleExists('block')) {
      // Tell the user they have the option to add a language switcher block
      // to their theme so they can switch between the languages.
      drupal_set_message($this->t('Use one of the language switcher blocks to allow site visitors to switch between languages. You can enable these blocks on the <a href="@block-admin">block administration page</a>.', array('@block-admin' => $this->url('block.admin_display'))));
    }
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    // No actions needed.
    return array();
  }

  /**
   * Validates the language addition form on custom language button.
   */
  public function validateCustom(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('predefined_langcode') == 'custom') {
      $langcode = $form_state->getValue('langcode');
      // Reuse the editing form validation routine if we add a custom language.
      $this->validateCommon($form['custom_language'], $form_state);

      if ($language = $this->languageManager->getLanguage($langcode)) {
        $form_state->setErrorByName('langcode', $this->t('The language %language (%langcode) already exists.', array('%language' => $language->getName(), '%langcode' => $langcode)));
      }
    }
    else {
      $form_state->setErrorByName('predefined_langcode', $this->t('Use the <em>Add language</em> button to save a predefined language.'));
    }
  }

  /**
   * Element specific validator for the Add language button.
   */
  public function validatePredefined($form, FormStateInterface $form_state) {
    $langcode = $form_state->getValue('predefined_langcode');
    if ($langcode == 'custom') {
      $form_state->setErrorByName('predefined_langcode', $this->t('Fill in the language details and save the language with <em>Add custom language</em>.'));
    }
    else {
      if ($language = $this->languageManager->getLanguage($langcode)) {
        $form_state->setErrorByName('predefined_langcode', $this->t('The language %language (%langcode) already exists.', array('%language' => $language->getName(), '%langcode' => $langcode)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $langcode = $form_state->getValue('predefined_langcode');
    if ($langcode == 'custom') {
      $langcode = $form_state->getValue('langcode');
      $label = $form_state->getValue('label');
      $direction = $form_state->getValue('direction');
    }
    else {
      $standard_languages = LanguageManager::getStandardLanguageList();
      $label = $standard_languages[$langcode][0];
      $direction = isset($standard_languages[$langcode][2]) ? $standard_languages[$langcode][2] : ConfigurableLanguage::DIRECTION_LTR;
    }
    $entity->set('id', $langcode);
    $entity->set('label', $label);
    $entity->set('direction', $direction);
    // There is no weight on the edit form. Fetch all configurable languages
    // ordered by weight and set the new language to be placed after them.
    $languages = \Drupal::languageManager()->getLanguages(ConfigurableLanguage::STATE_CONFIGURABLE);
    $last_language = end($languages);
    $entity->setWeight($last_language->getWeight() + 1);
  }

}
