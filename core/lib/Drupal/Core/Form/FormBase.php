<?php

/**
 * @file
 * Contains \Drupal\Core\Form\FormBase.
 */

namespace Drupal\Core\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerialization;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for forms.
 */
abstract class FormBase extends DependencySerialization implements FormInterface, ContainerInjectionInterface {

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
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The form error handler.
   *
   * @var \Drupal\Core\Form\FormErrorInterface
   */
  protected $errorHandler;

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
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager()->translate($string, $args, $options);
  }

  /**
   * Generates a URL or path for a specific route based on the given parameters.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute() for
   *   details on the arguments, usage, and possible exceptions.
   *
   * @return string
   *   The generated URL for the given route.
   */
  public function url($route_name, $route_parameters = array(), $options = array()) {
    return $this->urlGenerator()->generateFromRoute($route_name, $route_parameters, $options);
  }

  /**
   * Gets the translation manager.
   *
   * @return \Drupal\Core\StringTranslation\TranslationInterface
   *   The translation manager.
   */
  protected function translationManager() {
    if (!$this->translationManager) {
      $this->translationManager = $this->container()->get('string_translation');
    }
    return $this->translationManager;
  }

  /**
   * Retrieves a configuration object.
   *
   * This is the main entry point to the configuration API. Calling
   * @code $this->config('book.admin') @endcode will return a configuration
   * object in which the book module can store its administrative settings.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode,
   *   the config object returned will contain the contents of book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  protected function config($name) {
    if (!$this->configFactory) {
      $this->configFactory = $this->container()->get('config.factory');
    }
    return $this->configFactory->get($name);
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
   * Sets the config factory for this form.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   *
   * @return self
   *   The form.
   */
  public function setConfigFactory(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
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
      $this->request = $this->container()->get('request');
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
  protected function currentUser() {
    return \Drupal::currentUser();
  }

  /**
   * Gets the URL generator.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   */
  protected function urlGenerator() {
    if (!$this->urlGenerator) {
      $this->urlGenerator = \Drupal::urlGenerator();
    }
    return $this->urlGenerator;
  }

  /**
   * Sets the URL generator.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator.
   */
  public function setUrlGenerator(UrlGeneratorInterface $url_generator) {
    $this->urlGenerator = $url_generator;
  }

  /**
   * Returns the service container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  protected function container() {
    return \Drupal::getContainer();
  }

  /**
   * Returns the form error handler.
   *
   * @return \Drupal\Core\Form\FormErrorInterface
   *   The form error handler.
   */
  protected function errorHandler() {
    if (!$this->errorHandler) {
      $this->errorHandler = \Drupal::service('form_builder');
    }
    return $this->errorHandler;
  }

  /**
   * Files an error against a form element.
   *
   * @param string $name
   *   The name of the form element.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param string $message
   *   (optional) The error message to present to the user.
   *
   * @see \Drupal\Core\Form\FormErrorInterface::setErrorByName()
   */
  protected function setFormError($name, array &$form_state, $message = '') {
    $this->errorHandler()->setErrorByName($name, $form_state, $message);
  }

}
