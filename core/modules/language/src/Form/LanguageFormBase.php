<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageFormBase.
 */

namespace Drupal\language\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for language add and edit forms.
 */
abstract class LanguageFormBase extends EntityForm {

  /**
   * The configurable language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param  \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The configurable language manager.
   */
  public function __construct(ConfigurableLanguageManagerInterface $language_manager) {
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager')
    );
  }

  /**
   * Common elements of the language addition and editing form.
   */
  public function commonForm(array &$form) {
    /* @var $language \Drupal\language\ConfigurableLanguageInterface */
    $language = $this->entity;
    if ($language->getId()) {
      $form['langcode_view'] = array(
        '#type' => 'item',
        '#title' => $this->t('Language code'),
        '#markup' => $language->id()
      );
      $form['langcode'] = array(
        '#type' => 'value',
        '#value' => $language->id()
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
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Language name'),
      '#maxlength' => 64,
      '#default_value' => $language->label(),
      '#required' => TRUE,
    );
    $form['direction'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Direction'),
      '#required' => TRUE,
      '#description' => $this->t('Direction that text in this language is presented.'),
      '#default_value' => $language->getDirection(),
      '#options' => array(
        LanguageInterface::DIRECTION_LTR => $this->t('Left to right'),
        LanguageInterface::DIRECTION_RTL => $this->t('Right to left'),
      ),
    );

    return $form;
  }

  /**
   * Validates the language editing element.
   */
  public function validateCommon(array $form, FormStateInterface $form_state) {
    // Ensure sane field values for langcode and name.
    if (!isset($form['langcode_view']) && !preg_match('@^[a-zA-Z]{1,8}(-[a-zA-Z0-9]{1,8})*$@', $form_state->getValue('langcode'))) {
      $form_state->setErrorByName('langcode', $this->t('%field must be a valid language tag as <a href="@url">defined by the W3C</a>.', array(
        '%field' => $form['langcode']['#title'],
        '@url' => 'http://www.w3.org/International/articles/language-tags/',
      )));
    }
    if ($form_state->getValue('label') != SafeMarkup::checkPlain($form_state->getValue('label'))) {
      $form_state->setErrorByName('label', $this->t('%field cannot contain any markup.', array('%field' => $form['label']['#title'])));
    }
  }

}
