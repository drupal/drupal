<?php

namespace Drupal\locale\Form;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\locale\SourceString;

/**
 * Defines a translation edit form.
 *
 * @internal
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $filter_values = $this->translateFilterValues();
    $langcode = $filter_values['langcode'];

    $this->languageManager->reset();
    $languages = $this->languageManager->getLanguages();

    $language_name = isset($langcode) ? $languages[$langcode]->getName() : "- None -";

    $form['#attached']['library'][] = 'locale/drupal.locale.admin';

    $form['langcode'] = [
      '#type' => 'value',
      '#value' => $filter_values['langcode'],
    ];

    $form['strings'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#language' => $language_name,
      '#header' => [
        $this->t('Source string'),
        $this->t('Translation for @language', ['@language' => $language_name]),
      ],
      '#empty' => $this->t('No strings available.'),
      '#attributes' => ['class' => ['locale-translate-edit-table']],
    ];

    if (isset($langcode)) {
      $strings = $this->translateFilterLoadStrings();

      $plurals = $this->getNumberOfPlurals($langcode);

      foreach ($strings as $string) {
        // Cast into source string, will do for our purposes.
        $source = new SourceString($string);
        // Split source to work with plural values.
        $source_array = $source->getPlurals();
        $translation_array = $string->getPlurals();
        if (count($source_array) == 1) {
          // Add original string value and mark as non-plural.
          $plural = FALSE;
          $form['strings'][$string->lid]['original'] = [
            '#type' => 'item',
            '#title' => $this->t('Source string (@language)', ['@language' => $this->t('Built-in English')]),
            '#title_display' => 'invisible',
            '#plain_text' => $source_array[0],
            '#prefix' => '<span lang="en">',
            '#suffix' => '</span>',
          ];
        }
        else {
          // Add original string value and mark as plural.
          $plural = TRUE;
          $original_singular = [
            '#type' => 'item',
            '#title' => $this->t('Singular form'),
            '#plain_text' => $source_array[0],
            '#prefix' => '<span class="visually-hidden">' . $this->t('Source string (@language)', ['@language' => $this->t('Built-in English')]) . '</span><span lang="en">',
            '#suffix' => '</span>',
          ];
          $original_plural = [
            '#type' => 'item',
            '#title' => $this->t('Plural form'),
            '#plain_text' => $source_array[1],
            '#prefix' => '<span lang="en">',
            '#suffix' => '</span>',
          ];
          $form['strings'][$string->lid]['original'] = [
            $original_singular,
            ['#markup' => '<br>'],
            $original_plural,
          ];
        }
        if (!empty($string->context)) {
          $form['strings'][$string->lid]['original'][] = [
            '#type' => 'inline_template',
            '#template' => '<br><small>{{ context_title }}: <span lang="en">{{ context }}</span></small>',
            '#context' => [
              'context_title' => $this->t('In Context'),
              'context' => $string->context,
            ],
          ];
        }
        // Approximate the number of rows to use in the default textarea.
        $rows = min(ceil(str_word_count($source_array[0]) / 12), 10);
        if (!$plural) {
          $form['strings'][$string->lid]['translations'][0] = [
            '#type' => 'textarea',
            '#title' => $this->t('Translated string (@language)', ['@language' => $language_name]),
            '#title_display' => 'invisible',
            '#rows' => $rows,
            '#default_value' => $translation_array[0],
            '#attributes' => ['lang' => $langcode],
          ];
        }
        else {
          // Add a textarea for each plural variant.
          for ($i = 0; $i < $plurals; $i++) {
            $form['strings'][$string->lid]['translations'][$i] = [
              '#type' => 'textarea',
              // @todo Should use better labels https://www.drupal.org/node/2499639
              '#title' => ($i == 0 ? $this->t('Singular form') : $this->formatPlural($i, 'First plural form', '@count. plural form')),
              '#rows' => $rows,
              '#default_value' => $translation_array[$i] ?? '',
              '#attributes' => ['lang' => $langcode],
              '#prefix' => $i == 0 ? ('<span class="visually-hidden">' . $this->t('Translated string (@language)', ['@language' => $language_name]) . '</span>') : '',
            ];
          }
          if ($plurals == 2) {
            // Simplify interface text for the most common case.
            $form['strings'][$string->lid]['translations'][1]['#title'] = $this->t('Plural form');
          }
        }
      }
      if (count(Element::children($form['strings']))) {
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save translations'),
        ];
      }
    }
    $form['pager']['#type'] = 'pager';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $langcode = $form_state->getValue('langcode');
    foreach ($form_state->getValue('strings') as $lid => $translations) {
      foreach ($translations['translations'] as $key => $value) {
        if (!locale_string_is_safe($value)) {
          $form_state->setErrorByName("strings][$lid][translations][$key", $this->t('The submitted string contains disallowed HTML: %string', ['%string' => $value]));
          $form_state->setErrorByName("translations][$langcode][$key", $this->t('The submitted string contains disallowed HTML: %string', ['%string' => $value]));
          $this->logger('locale')->warning('Attempted submission of a translation string with disallowed HTML: %string', ['%string' => $value]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $langcode = $form_state->getValue('langcode');
    $updated = [];

    // Preload all translations for strings in the form.
    $lids = array_keys($form_state->getValue('strings'));
    $existing_translation_objects = [];
    foreach ($this->localeStorage->getTranslations(['lid' => $lids, 'language' => $langcode, 'translated' => TRUE]) as $existing_translation_object) {
      $existing_translation_objects[$existing_translation_object->lid] = $existing_translation_object;
    }

    foreach ($form_state->getValue('strings') as $lid => $new_translation) {
      $existing_translation = isset($existing_translation_objects[$lid]);

      // Plural translations are saved in a delimited string. To be able to
      // compare the new strings with the existing strings a string in the same
      // format is created.
      $new_translation_string_delimited = implode(PoItem::DELIMITER, $new_translation['translations']);

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
        $target = $existing_translation_objects[$lid] ?? $this->localeStorage->createTranslation(['lid' => $lid, 'language' => $langcode]);
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

    $this->messenger()->addStatus($this->t('The strings have been saved.'));

    // Keep the user on the current pager page.
    $page = $this->getRequest()->query->get('page');
    if (isset($page)) {
      $form_state->setRedirect(
        'locale.translate_page',
        [],
        ['page' => $page]
      );
    }

    if ($updated) {
      // Clear cache and force refresh of JavaScript translations.
      _locale_refresh_translations([$langcode], $updated);
      _locale_refresh_configuration([$langcode], $updated);
    }
  }

}
