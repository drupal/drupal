<?php

namespace Drupal\Core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Provides a base class for forms.
 *
 * This class exists as a mid-point between dependency injection through
 * ContainerInjectionInterface, and a less-structured use of traits which
 * default to using the \Drupal accessor for service discovery.
 *
 * To properly inject services, override create() and use the setters provided
 * by the traits to inject the needed services.
 *
 * @code
 * public static function create($container) {
 *   $form = new static();
 *   // In this example we only need string translation so we use the
 *   // setStringTranslation() method provided by StringTranslationTrait.
 *   $form->setStringTranslation($container->get('string_translation'));
 *   return $form;
 * }
 * @endcode
 *
 * Alternately, do not use FormBase. A class can implement FormInterface, use
 * the traits it needs, and inject services from the container as required.
 *
 * @ingroup form_api
 *
 * @see \Drupal\Core\DependencyInjection\ContainerInjectionInterface
 */
abstract class FormBase implements FormInterface, ContainerInjectionInterface {

  use DependencySerializationTrait;
  use LinkGeneratorTrait;
  use LoggerChannelTrait;
  use MessengerTrait;
  use RedirectDestinationTrait;
  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
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
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  protected function config($name) {
    return $this->configFactory()->get($name);
  }

  /**
   * Gets the config factory for this form.
   *
   * When accessing configuration values, use $this->config(). Only use this
   * when the config factory needs to be manipulated directly.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected function configFactory() {
    if (!$this->configFactory) {
      $this->configFactory = $this->container()->get('config.factory');
    }
    return $this->configFactory;
  }

  /**
   * Sets the config factory for this form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @return $this
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    return $this;
  }

  /**
   * Resets the configuration factory.
   */
  public function resetConfigFactory() {
    $this->configFactory = NULL;
  }

  /**
   * Gets the request object.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function getRequest() {
    if (!$this->requestStack) {
      $this->requestStack = \Drupal::service('request_stack');
    }
    return $this->requestStack->getCurrentRequest();
  }

  /**
   * Gets the route match.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  protected function getRouteMatch() {
    if (!$this->routeMatch) {
      $this->routeMatch = \Drupal::routeMatch();
    }
    return $this->routeMatch;
  }

  /**
   * Sets the request stack object to use.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   *
   * @return $this
   */
  public function setRequestStack(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    return $this;
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
   * Returns the service container.
   *
   * This method is marked private to prevent sub-classes from retrieving
   * services from the container through it. Instead,
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface should be used
   * for injecting services.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   *   The service container.
   */
  private function container() {
    return \Drupal::getContainer();
  }

  /**
   * Gets the logger for a specific channel.
   *
   * This method exists for backward-compatibility between FormBase and
   * LoggerChannelTrait. Use LoggerChannelTrait::getLogger() instead.
   *
   * @param string $channel
   *   The name of the channel. Can be any string, but the general practice is
   *   to use the name of the subsystem calling this.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for the given channel.
   */
  protected function logger($channel) {
    return $this->getLogger($channel);
  }

}
