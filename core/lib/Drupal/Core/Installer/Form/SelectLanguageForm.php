<?php

/**
 * @file
 * Contains \Drupal\Core\Installer\Form\SelectLanguageForm.
 */

namespace Drupal\Core\Installer\Form;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the language selection form.
 */
class SelectLanguageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'install_select_language_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $install_state = NULL) {
    if (count($install_state['translations']) > 1) {
      $files = $install_state['translations'];
    }
    else {
      $files = array();
    }
    $standard_languages = LanguageManager::getStandardLanguageList();
    $select_options = array();
    $browser_options = array();

    $form['#title'] = $this->t('Choose language');

    // Build a select list with language names in native language for the user
    // to choose from. And build a list of available languages for the browser
    // to select the language default from.
    if (count($files)) {
      // Select lists based on available language files.
      foreach ($files as $langcode => $uri) {
        $select_options[$langcode] = isset($standard_languages[$langcode]) ? $standard_languages[$langcode][1] : $langcode;
        $browser_options[] = $langcode;
      }
    }
    else {
      // Select lists based on all standard languages.
      foreach ($standard_languages as $langcode => $language_names) {
        $select_options[$langcode] = $language_names[1];
        $browser_options[] = $langcode;
      }
    }

    $request = Request::createFromGlobals();
    $browser_langcode = UserAgent::getBestMatchingLangcode($request->server->get('HTTP_ACCEPT_LANGUAGE'), $browser_options);
    $form['langcode'] = array(
      '#type' => 'select',
      '#title' => $this->t('Choose language'),
      '#title_display' => 'invisible',
      '#options' => $select_options,
      // Use the browser detected language as default or English if nothing found.
      '#default_value' => !empty($browser_langcode) ? $browser_langcode : 'en',
    );

    if (empty($files)) {
      $form['help'] = array(
        '#type' => 'item',
        '#markup' => String::format('<p>Translations will be downloaded from the <a href="http://localize.drupal.org">Drupal Translation website</a>.
        If you do not want this, select <a href="!english">English</a>.</p>', array(
            '!english' => install_full_redirect_url(array('parameters' => array('langcode' => 'en'))),
          )),
        '#states' => array(
          'invisible' => array(
            'select[name="langcode"]' => array('value' => 'en'),
          ),
        ),
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] =  array(
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $install_state = &$form_state['build_info']['args'][0];
    $install_state['parameters']['langcode'] = $form_state['values']['langcode'];
  }

}
