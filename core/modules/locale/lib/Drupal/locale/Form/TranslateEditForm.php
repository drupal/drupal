<?php

/**
 * @file
 * Contains \Drupal\locale\Form\TranslateEditForm.
 */

namespace Drupal\locale\Form;

use Drupal\Component\Utility\String;
use Drupal\locale\SourceString;

/**
 * Defines a translation edit form.
 */
class TranslateEditForm extends TranslateFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'locale_translate_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $filter_values = $this->translateFilterValues();
    $langcode = $filter_values['langcode'];

    $this->languageManager->reset();
    $languages = language_list();

    $langname = isset($langcode) ? $languages[$langcode]->name : "- None -";

    $form['#attached']['library'][] = 'locale/drupal.locale.admin';

    $form['langcode'] = array(
      '#type' => 'value',
      '#value' => $filter_values['langcode'],
    );

    $form['strings'] = array(
      '#type' => 'item',
      '#tree' => TRUE,
      '#language' => $langname,
      '#theme' => 'locale_translate_edit_form_strings',
    );

    if (isset($langcode)) {
      $strings = $this->translateFilterLoadStrings();

      $plural_formulas = $this->state->get('locale.translation.plurals') ?: array();

      foreach ($strings as $string) {
        // Cast into source string, will do for our purposes.
        $source = new SourceString($string);
        // Split source to work with plural values.
        $source_array = $source->getPlurals();
        $translation_array = $string->getPlurals();
        if (count($source_array) == 1) {
          // Add original string value and mark as non-plural.
          $form['strings'][$string->lid]['plural'] = array(
            '#type' => 'value',
            '#value' => 0,
          );
          $form['strings'][$string->lid]['original'] = array(
            '#type' => 'item',
            '#title' => $this->t('Source string (@language)', array('@language' => $this->t('Built-in English'))),
            '#title_display' => 'invisible',
            '#markup' => '<span lang="en">' . String::checkPlain($source_array[0]) . '</span>',
          );
        }
        else {
          // Add original string value and mark as plural.
          $form['strings'][$string->lid]['plural'] = array(
            '#type' => 'value',
            '#value' => 1,
          );
          $form['strings'][$string->lid]['original_singular'] = array(
            '#type' => 'item',
            '#title' => $this->t('Singular form'),
            '#markup' => '<span lang="en">' . String::checkPlain($source_array[0]) . '</span>',
            '#prefix' => '<span class="visually-hidden">' . $this->t('Source string (@language)', array('@language' => $this->t('Built-in English'))) . '</span>'
          );
          $form['strings'][$string->lid]['original_plural'] = array(
            '#type' => 'item',
            '#title' => $this->t('Plural form'),
            '#markup' => '<span lang="en">' . String::checkPlain($source_array[1]) . '</span>',
          );
        }
        if (!empty($string->context)) {
          $form['strings'][$string->lid]['context'] = array(
            '#type' => 'value',
            '#value' => '<span lang="en">' . String::checkPlain($string->context) . '</span>',
          );
        }
        // Approximate the number of rows to use in the default textarea.
        $rows = min(ceil(str_word_count($source_array[0]) / 12), 10);
        if (empty($form['strings'][$string->lid]['plural']['#value'])) {
          $form['strings'][$string->lid]['translations'][0] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Translated string (@language)', array('@language' => $langname)),
            '#title_display' => 'invisible',
            '#rows' => $rows,
            '#default_value' => $translation_array[0],
            '#attributes' => array('lang' => $langcode),
          );
        }
        else {
          // Dealing with plural strings.
          if (isset($plural_formulas[$langcode]['plurals']) && $plural_formulas[$langcode]['plurals'] > 2) {
            // Add a textarea for each plural variant.
            for ($i = 0; $i < $plural_formulas[$langcode]['plurals']; $i++) {
              $form['strings'][$string->lid]['translations'][$i] = array(
                '#type' => 'textarea',
                '#title' => ($i == 0 ? $this->t('Singular form') : format_plural($i, 'First plural form', '@count. plural form')),
                '#rows' => $rows,
                '#default_value' => isset($translation_array[$i]) ? $translation_array[$i] : '',
                '#attributes' => array('lang' => $langcode),
                '#prefix' => $i == 0 ? ('<span class="visually-hidden">' . $this->t('Translated string (@language)',  array('@language' => $langname)) . '</span>') : '',
              );
            }
          }
          else {
            // Fallback for unknown number of plurals.
            $form['strings'][$string->lid]['translations'][0] = array(
              '#type' => 'textarea',
              '#title' => $this->t('Singular form'),
              '#rows' => $rows,
              '#default_value' => $translation_array[0],
              '#attributes' => array('lang' => $langcode),
              '#prefix' => '<span class="visually-hidden">' . $this->t('Translated string (@language)',  array('@language' => $langname)) . '</span>',
            );
            $form['strings'][$string->lid]['translations'][1] = array(
              '#type' => 'textarea',
              '#title' => $this->t('Plural form'),
              '#rows' => $rows,
              '#default_value' => isset($translation_array[1]) ? $translation_array[1] : '',
              '#attributes' => array('lang' => $langcode),
            );
          }
        }
      }
      if (count(element_children($form['strings']))) {
        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Save translations'),
        );
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $langcode = $form_state['values']['langcode'];
    foreach ($form_state['values']['strings'] as $lid => $translations) {
      foreach ($translations['translations'] as $key => $value) {
        if (!locale_string_is_safe($value)) {
          $this->setFormError("strings][$lid][translations][$key", $form_state, $this->t('The submitted string contains disallowed HTML: %string', array('%string' => $value)));
          $this->setFormError("translations][$langcode][$key", $form_state, $this->t('The submitted string contains disallowed HTML: %string', array('%string' => $value)));
          watchdog('locale', 'Attempted submission of a translation string with disallowed HTML: %string', array('%string' => $value), WATCHDOG_WARNING);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $langcode = $form_state['values']['langcode'];
    $updated = array();

    // Preload all translations for strings in the form.
    $lids = array_keys($form_state['values']['strings']);
    $existing_translation_objects = array();
    foreach ($this->localeStorage->getTranslations(array('lid' => $lids, 'language' => $langcode, 'translated' => TRUE)) as $existing_translation_object) {
      $existing_translation_objects[$existing_translation_object->lid] = $existing_translation_object;
    }

    foreach ($form_state['values']['strings'] as $lid => $new_translation) {
      $existing_translation = isset($existing_translation_objects[$lid]);

      // Plural translations are saved in a delimited string. To be able to
      // compare the new strings with the existing strings a string in the same format is created.
      $new_translation_string_delimited = implode(LOCALE_PLURAL_DELIMITER, $new_translation['translations']);

      // Generate an imploded string without delimiter, to be able to run
      // empty() on it.
      $new_translation_string = implode('', $new_translation['translations']);

      $is_changed = FALSE;

      if ($existing_translation && $existing_translation_objects[$lid]->translation != $new_translation_string_delimited) {
        // If there is an existing translation in the DB and the new translation
        // is not the same as the existing one.
        $is_changed = TRUE;
      }
      elseif (!$existing_translation && !empty($new_translation_string)) {
        // Newly entered translation.
        $is_changed = TRUE;
      }

      if ($is_changed) {
        // Only update or insert if we have a value to use.
        $target = isset($existing_translation_objects[$lid]) ? $existing_translation_objects[$lid] : $this->localeStorage->createTranslation(array('lid' => $lid, 'language' => $langcode));
        $target->setPlurals($new_translation['translations'])
          ->setCustomized()
          ->save();
        $updated[] = $target->getId();
      }
      if (empty($new_translation_string) && isset($existing_translation_objects[$lid])) {
        // Empty new translation entered: remove existing entry from database.
        $existing_translation_objects[$lid]->delete();
        $updated[] = $lid;
      }
    }

    drupal_set_message($this->t('The strings have been saved.'));

    // Keep the user on the current pager page.
    $page = $this->getRequest()->query->get('page');
    if (isset($page)) {
      $form_state['redirect_route'] = array(
        'route_name' => 'locale.translate_page',
        'options' => array(
          'page' => $page,
        ),
      );
    }

    if ($updated) {
      // Clear cache and force refresh of JavaScript translations.
      _locale_refresh_translations(array($langcode), $updated);
      _locale_refresh_configuration(array($langcode), $updated);
    }
  }

}
