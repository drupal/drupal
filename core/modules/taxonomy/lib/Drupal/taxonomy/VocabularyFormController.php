<?php

/**
 * @file
 * Definition of Drupal\taxonomy\VocabularyFormController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Language\Language;

/**
 * Base form controller for vocabulary edit forms.
 */
class VocabularyFormController extends EntityFormController {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $vocabulary = $this->entity;

    // Check whether we need a deletion confirmation form.
    if (isset($form_state['confirm_delete']) && isset($form_state['values']['vid'])) {
      return taxonomy_vocabulary_confirm_delete($form, $form_state, $form_state['values']['vid']);
    }
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#default_value' => $vocabulary->name,
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['vid'] = array(
      '#type' => 'machine_name',
      '#default_value' => $vocabulary->id(),
      '#maxlength' => 255,
      '#machine_name' => array(
        'exists' => 'taxonomy_vocabulary_load',
        'source' => array('name'),
      ),
    );
    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => t('Description'),
      '#default_value' => $vocabulary->description,
    );

    // $form['langcode'] is not wrapped in an if (module_exists('language'))
    // check because the language_select form element works also without the
    // language module being installed.
    // http://drupal.org/node/1749954 documents the new element.
    $form['langcode'] = array(
      '#type' => 'language_select',
      '#title' => t('Vocabulary language'),
      '#languages' => Language::STATE_ALL,
      '#default_value' => $vocabulary->langcode,
    );
    if (module_exists('language')) {
      $form['default_terms_language'] = array(
        '#type' => 'details',
        '#title' => t('Terms language'),
      );
      $form['default_terms_language']['default_language'] = array(
        '#type' => 'language_configuration',
        '#entity_information' => array(
          'entity_type' => 'taxonomy_term',
          'bundle' => $vocabulary->id(),
        ),
        '#default_value' => language_get_default_configuration('taxonomy_term', $vocabulary->id()),
      );
    }
    // Set the hierarchy to "multiple parents" by default. This simplifies the
    // vocabulary form and standardizes the term form.
    $form['hierarchy'] = array(
      '#type' => 'value',
      '#value' => '0',
    );

    return parent::form($form, $form_state, $vocabulary);
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, array &$form_state) {
    // If we are displaying the delete confirmation skip the regular actions.
    if (empty($form_state['confirm_delete'])) {
      $actions = parent::actions($form, $form_state);
      array_unshift($actions['delete']['#submit'], array($this, 'submit'));
      // Add the language configuration submit handler. This is needed because
      // the submit button has custom submit handlers.
      if (module_exists('language')) {
        array_unshift($actions['submit']['#submit'],'language_configuration_element_submit');
        array_unshift($actions['submit']['#submit'], array($this, 'languageConfigurationSubmit'));
      }
      // We cannot leverage the regular submit handler definition because we
      // have button-specific ones here. Hence we need to explicitly set it for
      // the submit action, otherwise it would be ignored.
      if (module_exists('content_translation')) {
        array_unshift($actions['submit']['#submit'], 'content_translation_language_configuration_element_submit');
      }
      return $actions;
    }
    else {
      return array();
    }
  }

  /**
   * Submit handler to update the bundle for the default language configuration.
   */
  public function languageConfigurationSubmit(array &$form, array &$form_state) {
    $vocabulary = $this->entity;
    // Delete the old language settings for the vocabulary, if the machine name
    // is changed.
    if ($vocabulary && $vocabulary->id() && $vocabulary->id() != $form_state['values']['vid']) {
      language_clear_default_configuration('taxonomy_term', $vocabulary->id());
    }
    // Since the machine name is not known yet, and it can be changed anytime,
    // we have to also update the bundle property for the default language
    // configuration in order to have the correct bundle value.
    $form_state['language']['default_language']['bundle'] = $form_state['values']['vid'];
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, array &$form_state) {
    // @todo We should not be calling taxonomy_vocabulary_confirm_delete() from
    // within the form builder.
    if ($form_state['triggering_element']['#value'] == t('Delete')) {
      // Rebuild the form to confirm vocabulary deletion.
      $form_state['rebuild'] = TRUE;
      $form_state['confirm_delete'] = TRUE;
      return NULL;
    }
    else {
      return parent::submit($form, $form_state);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $vocabulary = $this->entity;

    // Prevent leading and trailing spaces in vocabulary names.
    $vocabulary->name = trim($vocabulary->name);

    switch ($vocabulary->save()) {
      case SAVED_NEW:
        drupal_set_message(t('Created new vocabulary %name.', array('%name' => $vocabulary->name)));
        watchdog('taxonomy', 'Created new vocabulary %name.', array('%name' => $vocabulary->name), WATCHDOG_NOTICE, l(t('edit'), 'admin/structure/taxonomy/manage/' . $vocabulary->id() . '/edit'));
        $form_state['redirect'] = 'admin/structure/taxonomy/manage/' . $vocabulary->id();
        break;

      case SAVED_UPDATED:
        drupal_set_message(t('Updated vocabulary %name.', array('%name' => $vocabulary->name)));
        watchdog('taxonomy', 'Updated vocabulary %name.', array('%name' => $vocabulary->name), WATCHDOG_NOTICE, l(t('edit'), 'admin/structure/taxonomy/manage/' . $vocabulary->id() . '/edit'));
        $form_state['redirect'] = 'admin/structure/taxonomy';
        break;
    }

    $form_state['values']['vid'] = $vocabulary->id();
    $form_state['vid'] = $vocabulary->id();
  }
}
