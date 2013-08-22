<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormBase.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for forms.
 */
abstract class FormBase implements FormInterface, ControllerInterface {

  /**
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Validation is optional.
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * @param string $string
   *   A string containing the English string to translate.
   * @param array $args
   *   An associative array of replacements to make after translation. Based
   *   on the first character of the key, the value is escaped and/or themed.
   *   See \Drupal\Core\Utility\String::format() for details.
   * @param array $options
   *   An associative array of additional options, with the following elements:
   *   - 'langcode': The language code to translate to a language other than
   *      what is used to display the page.
   *   - 'context': The context the source string belongs to.
   *
   * @return string
   *   The translated string.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->getTranslationManager()->translate($string, $args, $options);
  }

  /**
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function getTranslationManager() {
    if (!$this->translationManager) {
      $this->translationManager = \Drupal::translation();
    }
    return $this->translationManager;
  }

  /**
   * Sets the translation manager for this form.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The translation manager.
   *
   * @return self
   *   The entity form.
   */
  public function setTranslationManager(TranslationInterface $translation_manager) {
    $this->translationManager = $translation_manager;
    return $this;
  }

  /**
   * Gets the request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  protected function getRequest() {
    if (!$this->request) {
      $this->request = \Drupal::request();
    }
    return $this->request;
  }

  /**
   * Sets the request object to use.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function setRequest(Request $request) {
    $this->request = $request;
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function getCurrentUser() {
    return $this->getRequest()->attributes->get('_account');
  }

}
