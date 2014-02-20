<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageFormBase.
 */

namespace Drupal\language\Form;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Language\Language;

/**
 * Base form controller for language add and edit forms.
 */
abstract class LanguageFormBase extends EntityFormController {

  /**
   * Common elements of the language addition and editing form.
   */
  public function commonForm(array &$form) {
    $language = $this->entity;
    if (isset($language->id)) {
      $form['langcode_view'] = array(
        '#type' => 'item',
        '#title' => $this->t('Language code'),
        '#markup' => $language->id
      );
      $form['langcode'] = array(
        '#type' => 'value',
        '#value' => $language->id
      );
    }
    else {
      $form['langcode'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Language code'),
        '#maxlength' => 12,
        '#required' => TRUE,
        '#default_value' => '',
        '#disabled' => FALSE,
        '#description' => $this->t('Use language codes as <a href="@w3ctags">defined by the W3C</a> for interoperability. <em>Examples: "en", "en-gb" and "zh-hant".</em>', array('@w3ctags' => 'http://www.w3.org/International/articles/language-tags/')),
      );
    }
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Language name in English'),
      '#maxlength' => 64,
      '#default_value' => $language->label,
      '#required' => TRUE,
    );
    $form['direction'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Direction'),
      '#required' => TRUE,
      '#description' => $this->t('Direction that text in this language is presented.'),
      '#default_value' => $language->direction,
      '#options' => array(
        Language::DIRECTION_LTR => $this->t('Left to right'),
        Language::DIRECTION_RTL => $this->t('Right to left'),
      ),
    );

    return $form;
  }

  /**
   * Validates the language editing element.
   */
  public function validateCommon(array $form, array &$form_state) {
    // Ensure sane field values for langcode and name.
    if (!isset($form['langcode_view']) && preg_match('@[^a-zA-Z_-]@', $form_state['values']['langcode'])) {
      $this->setFormError('langcode', $form_state, $this->t('%field may only contain characters a-z, underscores, or hyphens.', array('%field' => $form['langcode']['#title'])));
    }
    if ($form_state['values']['name'] != String::checkPlain($form_state['values']['name'])) {
      $this->setFormError('name', $form_state, $this->t('%field cannot contain any markup.', array('%field' => $form['name']['#title'])));
    }
  }

}
