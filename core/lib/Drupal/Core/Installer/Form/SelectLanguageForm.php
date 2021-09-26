<?php

namespace Drupal\Core\Installer\Form;

use Drupal\Component\Utility\UserAgent;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the language selection form.
 *
 * Note that hardcoded text provided by this form is not translated. This is
 * because translations are downloaded as a result of submitting this form.
 *
 * @internal
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
  public function buildForm(array $form, FormStateInterface $form_state, $install_state = NULL) {
    if (count($install_state['translations']) > 1) {
      $files = $install_state['translations'];
    }
    else {
      $files = [];
    }
    $standard_languages = LanguageManager::getStandardLanguageList();
    $select_options = [];
    $browser_options = [];

    $form['#title'] = 'Choose language';

    // Build a select list with language names in native language for the user
    // to choose from. And build a list of available languages for the browser
    // to select the language default from.
    // Select lists based on all standard languages.
    foreach ($standard_languages as $langcode => $language_names) {
      $select_options[$langcode] = $language_names[1];
      $browser_options[$langcode] = $langcode;
    }
    // Add languages based on language files in the translations directory.
    if (count($files)) {
      foreach ($files as $langcode => $uri) {
        $select_options[$langcode] = isset($standard_languages[$langcode]) ? $standard_languages[$langcode][1] : $langcode;
        $browser_options[$langcode] = $langcode;
      }
    }
    asort($select_options);
    $request = Request::createFromGlobals();
    $browser_langcode = UserAgent::getBestMatchingLangcode($request->server->get('HTTP_ACCEPT_LANGUAGE', ''), $browser_options);
    $form['langcode'] = [
      '#type' => 'select',
      '#title' => 'Choose language',
      '#title_display' => 'invisible',
      '#options' => $select_options,
      // Use the browser detected language as default or English if nothing found.
      '#default_value' => !empty($browser_langcode) ? $browser_langcode : 'en',
    ];
    $link_to_english = install_full_redirect_url(['parameters' => ['langcode' => 'en']]);
    $form['help'] = [
      '#type' => 'item',
      // #markup is XSS admin filtered which ensures unsafe protocols will be
      // removed from the url.
      '#markup' => '<p>Translations will be downloaded from the <a href="https://localize.drupal.org/download">Drupal Translation website</a>. If you do not want this, select <a href="' . $link_to_english . '">English</a>.</p>',
      '#states' => [
        'invisible' => [
          'select[name="langcode"]' => ['value' => 'en'],
        ],
      ],
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save and continue',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    $build_info['args'][0]['parameters']['langcode'] = $form_state->getValue('langcode');
    $form_state->setBuildInfo($build_info);
  }

}
