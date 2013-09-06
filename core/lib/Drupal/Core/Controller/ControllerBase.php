<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\ControllerBase.
 */

namespace Drupal\Core\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Utility base class for thin controllers.
 *
 * Controllers that use this base class have access to a number of utility
 * methods and to the Container, which can greatly reduce boilerplate dependency
 * handling code.  However, it also makes the class considerably more
 * difficult to unit test. Therefore this base class should only be used by
 * controller classes that contain only trivial glue code.  Controllers that
 * contain sufficiently complex logic that it's worth testing should not use
 * this base class but use ContainerInjectionInterface instead, or even better be
 * refactored to be trivial glue code.
 *
 * The services exposed here are those that it is reasonable for a well-behaved
 * controller to leverage. A controller that needs other other services may
 * need to be refactored into a thin controller and a dependent unit-testable
 * service.
 *
 * @see \Drupal\Core\DependencyInjection\ContainerInjectionInterface
 */
abstract class ControllerBase extends ContainerAware {

  /**
   * Retrieves the entity manager service.
   *
   * @return \Drupal\Core\Entity\EntityManager
   *   The entity manager service.
   */
  protected function entityManager() {
    return $this->container->get('entity.manager');
  }

  /**
   * Returns the requested cache bin.
   *
   * @param string $bin
   *   (optional) The cache bin for which the cache object should be returned,
   *   defaults to 'cache'.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache object associated with the specified bin.
   */
  protected function cache($bin = 'cache') {
    return $this->container->get('cache.' . $bin);
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
    return $this->container->get('config.factory')->get($name);
  }

  /**
   * Returns a key/value storage collection.
   *
   * @param string $collection
   *   Name of the key/value collection to return.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected function keyValue($collection) {
    return $this->container->get('keyvalue')->get($collection);
  }

  /**
   * Returns the state storage service.
   *
   * Use this to store machine-generated data, local to a specific environment
   * that does not need deploying and does not need human editing; for example,
   * the last time cron was run. Data which needs to be edited by humans and
   * needs to be the same across development, production, etc. environments
   * (for example, the system maintenance message) should use config() instead.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected function state() {
    return $this->container->get('state');
  }

  /**
   * Returns the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected function moduleHandler() {
    return $this->container->get('module_handler');
  }

  /**
   * Returns the URL generator service.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The URL generator service.
   */
  protected function urlGenerator() {
    return $this->container->get('url_generator');
  }

  /**
   * Renders a link to a route given a route name and its parameters.
   *
   * @see  \Drupal\Core\Utility\LinkGeneratorInterface::generate() for details
   *   on the arguments, usage, and possible exceptions.
   *
   * @return string
   *   An HTML string containing a link to the given route and parameters.
   */
  public function l($text, $route_name, array $parameters = array(), array $options = array()) {
    return $this->container->get('link_generator')->generate($text, $route_name, $parameters, $options);
  }

  /**
   * Returns the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser() {
    return $this->container->get('current_user');
  }

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->container->get('string_translation')->translate($string, $args, $options);
  }

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManager
   *   The language manager.
   */
  protected function languageManager() {
    return $this->container->get('language_manager');
  }

  /**
   * Returns a redirect response object for the specified
   *
   * @param string $route_name
   *   The name of the route to which to redirect.
   * @param array $route_parameters
   *   Parameters for the route.
   * @param int $status
   *   The HTTP redirect status code for the redirect. The default is 302 Found.
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object that may be returned by the controller.
   */
  public function redirect($route_name, array $route_parameters = array(), $status = 302) {
    $url = $this->container->get('url_generator')->generate($route_name, $route_parameters, TRUE);
    return new RedirectResponse($url, $status);
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
    return $this->container->get('url_generator')->generateFromRoute($route_name, $route_parameters, $options);
  }

}
