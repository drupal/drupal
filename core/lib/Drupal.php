<?php

/**
 * @file
 * Contains Drupal.
 */

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Static Service Container wrapper.
 *
 * Generally, code in Drupal should accept its dependencies via either
 * constructor injection or setter method injection. However, there are cases,
 * particularly in legacy procedural code, where that is infeasible. This
 * class acts as a unified global accessor to arbitrary services within the
 * system in order to ease the transition from procedural code to injected OO
 * code.
 *
 * The container is built by the kernel and passed in to this class which stores
 * it statically. The container always contains the services from
 * \Drupal\Core\CoreServiceProvider, the service providers of enabled modules and any other
 * service providers defined in $GLOBALS['conf']['container_service_providers'].
 *
 * This class exists only to support legacy code that cannot be dependency
 * injected. If your code needs it, consider refactoring it to be object
 * oriented, if possible. When this is not possible, for instance in the case of
 * hook implementations, and your code is more than a few non-reusable lines, it
 * is recommended to instantiate an object implementing the actual logic.
 *
 * @code
 *   // Legacy procedural code.
 *   function hook_do_stuff() {
 *     $lock = lock()->acquire('stuff_lock');
 *     // ...
 *   }
 *
 *   // Correct procedural code.
 *   function hook_do_stuff() {
 *     $lock = \Drupal::lock()->acquire('stuff_lock');
 *     // ...
 *   }
 *
 *   // The preferred way: dependency injected code.
 *   function hook_do_stuff() {
 *     // Move the actual implementation to a class and instantiate it.
 *     $instance = new StuffDoingClass(\Drupal::lock());
 *     $instance->doStuff();
 *
 *     // Or, even better, rely on the service container to avoid hard coding a
 *     // specific interface implementation, so that the actual logic can be
 *     // swapped. This might not always make sense, but in general it is a good
 *     // practice.
 *     \Drupal::service('stuff.doing')->doStuff();
 *   }
 *
 *   interface StuffDoingInterface {
 *     public function doStuff();
 *   }
 *
 *   class StuffDoingClass implements StuffDoingInterface {
 *     protected $lockBackend;
 *
 *     public function __construct(LockBackendInterface $lockBackend) {
 *       $this->lockBackend = $lockBackend;
 *     }
 *
 *     public function doStuff() {
 *       $lock = $this->lockBackend->acquire('stuff_lock');
 *       // ...
 *     }
 *   }
 * @endcode
 *
 * @see \Drupal\Core\DrupalKernel
 */
class Drupal {

  /**
   * The current system version.
   */
  const VERSION = '8.0.0-dev';

  /**
   * Core API compatibility.
   */
  const CORE_COMPATIBILITY = '8.x';

  /**
   * Core minimum schema version.
   */
  const CORE_MINIMUM_SCHEMA_VERSION = 8000;

  /**
   * The currently active container object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected static $container;

  /**
   * Sets a new global container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A new container instance to replace the current. NULL may be passed by
   *   testing frameworks to ensure that the global state of a previous
   *   environment does not leak into a test.
   */
  public static function setContainer(ContainerInterface $container = NULL) {
    static::$container = $container;
  }

  /**
   * Returns the currently active global container.
   *
   * @deprecated This method is only useful for the testing environment. It
   * should not be used otherwise.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public static function getContainer() {
    return static::$container;
  }

  /**
   * Retrieves a service from the container.
   *
   * Use this method if the desired service is not one of those with a dedicated
   * accessor method below. If it is listed below, those methods are preferred
   * as they can return useful type hints.
   *
   * @param string $id
   *   The ID of the service to retrieve.
   * @return mixed
   *   The specified service.
   */
  public static function service($id) {
    return static::$container->get($id);
  }

  /**
   * Indicates if a service is defined in the container.
   *
   * @param string $id
   *   The ID of the service to check.
   *
   * @return bool
   *   TRUE if the specified service exists, FALSE otherwise.
   */
  public static function hasService($id) {
    return static::$container && static::$container->has($id);
  }

  /**
   * Indicates if there is a currently active request object.
   *
   * @return bool
   *   TRUE if there is a currently active request object, FALSE otherwise.
   */
  public static function hasRequest() {
    return static::$container && static::$container->has('request_stack') && static::$container->get('request_stack')->getCurrentRequest() !== NULL;
  }

  /**
   * Retrieves the currently active request object.
   *
   * Note: The use of this wrapper in particular is especially discouraged. Most
   * code should not need to access the request directly.  Doing so means it
   * will only function when handling an HTTP request, and will require special
   * modification or wrapping when run from a command line tool, from certain
   * queue processors, or from automated tests.
   *
   * If code must access the request, it is considerably better to register
   * an object with the Service Container and give it a setRequest() method
   * that is configured to run when the service is created.  That way, the
   * correct request object can always be provided by the container and the
   * service can still be unit tested.
   *
   * If this method must be used, never save the request object that is
   * returned.  Doing so may lead to inconsistencies as the request object is
   * volatile and may change at various times, such as during a subrequest.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The currently active request object.
   */
  public static function request() {
    return static::$container->get('request_stack')->getCurrentRequest();
  }

  /**
   * Retrives the request stack.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack
   */
  public static function requestStack() {
    return static::$container->get('request_stack');
  }

  /**
   * Retrieves the currently active route match object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The currently active route match object.
   */
  public static function routeMatch() {
    return static::$container->get('current_route_match');
  }

  /**
   * Gets the current active user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   */
  public static function currentUser() {
    return static::$container->get('current_user');
  }

  /**
   * Retrieves the entity manager service.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager service.
   */
  public static function entityManager() {
    return static::$container->get('entity.manager');
  }

  /**
   * Returns the current primary database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The current active database's master connection.
   */
  public static function database() {
    return static::$container->get('database');
  }

  /**
   * Returns the requested cache bin.
   *
   * @param string $bin
   *   (optional) The cache bin for which the cache object should be returned,
   *   defaults to 'default'.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The cache object associated with the specified bin.
   *
   * @ingroup cache
   */
  public static function cache($bin = 'default') {
    return static::$container->get('cache.' . $bin);
  }

  /**
   * Returns an expirable key value store collection.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   An expirable key value store collection.
   */
  public static function keyValueExpirable($collection) {
    return static::$container->get('keyvalue.expirable')->get($collection);
  }

  /**
   * Returns the locking layer instance.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   *
   * @ingroup lock
   */
  public static function lock() {
    return static::$container->get('lock');
  }

  /**
   * Retrieves a configuration object.
   *
   * This is the main entry point to the configuration API. Calling
   * @code \Drupal::config('book.admin') @endcode will return a configuration
   * object in which the book module can store its administrative settings.
   *
   * @param string $name
   *   The name of the configuration object to retrieve. The name corresponds to
   *   a configuration file. For @code \Drupal::config('book.admin') @endcode, the config
   *   object returned will contain the contents of book.admin configuration file.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  public static function config($name) {
    return static::$container->get('config.factory')->get($name);
  }

  /**
   * Retrieves the configuration factory.
   *
   * This is mostly used to change the override settings on the configuration
   * factory. For example, changing the language, or turning all overrides on
   * or off.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The configuration factory service.
   */
  public static function configFactory() {
    return static::$container->get('config.factory');
  }

  /**
   * Returns a queue for the given queue name.
   *
   * The following values can be set in your settings.php file's $settings
   * array to define which services are used for queues:
   * - queue_reliable_service_$name: The container service to use for the
   *   reliable queue $name.
   * - queue_service_$name: The container service to use for the
   *   queue $name.
   * - queue_default: The container service to use by default for queues
   *   without overrides. This defaults to 'queue.database'.
   *
   * @param string $name
   *   The name of the queue to work with.
   * @param bool $reliable
   *   (optional) TRUE if the ordering of items and guaranteeing every item
   *   executes at least once is important, FALSE if scalability is the main
   *   concern. Defaults to FALSE.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue object for a given name.
   */
  public static function queue($name, $reliable = FALSE) {
    return static::$container->get('queue')->get($name, $reliable);
  }

  /**
   * Returns a key/value storage collection.
   *
   * @param string $collection
   *   Name of the key/value collection to return.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  public static function keyValue($collection) {
    return static::$container->get('keyvalue')->get($collection);
  }

  /**
   * Returns the state storage service.
   *
   * Use this to store machine-generated data, local to a specific environment
   * that does not need deploying and does not need human editing; for example,
   * the last time cron was run. Data which needs to be edited by humans and
   * needs to be the same across development, production, etc. environments
   * (for example, the system maintenance message) should use \Drupal::config() instead.
   *
   * @return \Drupal\Core\State\StateInterface
   */
  public static function state() {
    return static::$container->get('state');
  }

  /**
   * Returns the default http client.
   *
   * @return \GuzzleHttp\ClientInterface
   *   A guzzle http client instance.
   */
  public static function httpClient() {
    return static::$container->get('http_client');
  }

  /**
   * Returns the entity query object for this entity type.
   *
   * @param string $entity_type
   *   The entity type, e.g. node, for which the query object should be
   *   returned.
   * @param string $conjunction
   *   AND if all conditions in the query need to apply, OR if any of them is
   *   enough. Optional, defaults to AND.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query object that can query the given entity type.
   */
  public static function entityQuery($entity_type, $conjunction = 'AND') {
    return static::$container->get('entity.query')->get($entity_type, $conjunction);
  }

  /**
   * Returns the entity query aggregate object for this entity type.
   *
   * @param string $entity_type
   *   The entity type, e.g. node, for which the query object should be
   *   returned.
   * @param string $conjunction
   *   AND if all conditions in the query need to apply, OR if any of them is
   *   enough. Optional, defaults to AND.
   *
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The query object that can query the given entity type.
   */
  public static function entityQueryAggregate($entity_type, $conjunction = 'AND') {
    return static::$container->get('entity.query')->getAggregate($entity_type, $conjunction);
  }

  /**
   * Returns the flood instance.
   *
   * @return \Drupal\Core\Flood\FloodInterface
   */
  public static function flood() {
    return static::$container->get('flood');
  }

  /**
   * Returns the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public static function moduleHandler() {
    return static::$container->get('module_handler');
  }

  /**
   * Returns the typed data manager service.
   *
   * Use the typed data manager service for creating typed data objects.
   *
   * @return \Drupal\Core\TypedData\TypedDataManager
   *   The typed data manager.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public static function typedDataManager() {
    return static::$container->get('typed_data_manager');
  }

  /**
   * Returns the token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token service.
   */
  public static function token() {
    return static::$container->get('token');
  }

  /**
   * Returns the url generator service.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The url generator service.
   */
  public static function urlGenerator() {
    return static::$container->get('url_generator');
  }

  /**
   * Generates a URL or path for a specific route based on the given parameters.
   *
   * Parameters that reference placeholders in the route pattern will be
   * substituted for them in the pattern. Extra params are added as query
   * strings to the URL.
   *
   * @param string $route_name
   *   The name of the route
   * @param array $route_parameters
   *   An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, the language will be
   *     obtained from
   *     \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_URL).
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. if mixed mode sessions are permitted, TRUE enforces HTTPS
   *     and FALSE enforces HTTP.
   *
   * @return string
   *   The generated URL for the given route.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown when the named route doesn't exist.
   * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
   *   Thrown when some parameters are missing that are mandatory for the route.
   * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
   *   Thrown when a parameter value for a placeholder is not correct because it
   *   does not match the requirement.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
   */
  public static function url($route_name, $route_parameters = array(), $options = array()) {
    return static::$container->get('url_generator')->generateFromRoute($route_name, $route_parameters, $options);
  }

  /**
   * Returns the link generator service.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   */
  public static function linkGenerator() {
    return static::$container->get('link_generator');
  }

  /**
   * Renders a link to a route given a route name and its parameters.
   *
   * This function correctly handles aliased paths and sanitizing text, so all
   * internal links output by modules should be generated by this function if
   * possible.
   *
   * However, for links enclosed in translatable text you should use t() and
   * embed the HTML anchor tag directly in the translated string. For example:
   * @code
   * t('Visit the <a href="@url">content types</a> page', array('@url' => \Drupal::url('node.overview_types')));
   * @endcode
   * This keeps the context of the link title ('settings' in the example) for
   * translators.
   *
   * @param string|array $text
   *   The link text for the anchor tag as a translated string or render array.
   * @param string $route_name
   *   The name of the route to use to generate the link.
   * @param array $parameters
   *   (optional) Any parameters needed to render the route path pattern.
   * @param array $options
   *   (optional) An associative array of additional options. Defaults to an
   *   empty array. It may contain the following elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding) to
   *     append to the URL.
   *   - absolute: Whether to force the output to be an absolute link (beginning
   *     with http:). Useful for links that will be displayed outside the site,
   *     such as in an RSS feed. Defaults to FALSE.
   *   - attributes: An associative array of HTML attributes to apply to the
   *     anchor tag. If element 'class' is included, it must be an array; 'title'
   *     must be a string; other elements are more flexible, as they just need
   *     to work as an argument for the constructor of the class
   *     Drupal\Core\Template\Attribute($options['attributes']).
   *   - html: Whether $text is HTML or just plain-text. For
   *     example, to make an image tag into a link, this must be set to TRUE, or
   *     you will see the escaped HTML image tag. $text is not sanitized if
   *     'html' is TRUE. The calling function must ensure that $text is already
   *     safe. Defaults to FALSE.
   *   - language: An optional language object. If the path being linked to is
   *     internal to the site, $options['language'] is used to determine whether
   *     the link is "active", or pointing to the current page (the language as
   *     well as the path must match).
   *
   * @return string
   *   An HTML string containing a link to the given route and parameters.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown when the named route doesn't exist.
   * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
   *   Thrown when some parameters are missing that are mandatory for the route.
   * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
   *   Thrown when a parameter value for a placeholder is not correct because it
   *   does not match the requirement.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   */
  public static function l($text, $route_name, array $parameters = array(), array $options = array()) {
    return static::$container->get('link_generator')->generate($text, $route_name, $parameters, $options);
  }

  /**
   * Returns the string translation service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationManager
   *   The string translation manager.
   */
  public static function translation() {
    return static::$container->get('string_translation');
  }

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public static function languageManager() {
    return static::$container->get('language_manager');
  }

  /**
   * Returns the CSRF token manager service.
   *
   * The generated token is based on the session ID of the current user. Normally,
   * anonymous users do not have a session, so the generated token will be
   * different on every page request. To generate a token for users without a
   * session, manually start a session prior to calling this function.
   *
   * @return \Drupal\Core\Access\CsrfTokenGenerator
   *   The CSRF token manager.
   *
   * @see \Drupal\Core\Session\SessionManager::start()
   */
  public static function csrfToken() {
    return static::$container->get('csrf_token');
  }

  /**
   * Returns the transliteration service.
   *
   * @return \Drupal\Core\Transliteration\PHPTransliteration
   *   The transliteration manager.
   */
  public static function transliteration() {
    return static::$container->get('transliteration');
  }

  /**
   * Returns the form builder service.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  public static function formBuilder() {
    return static::$container->get('form_builder');
  }

  /**
   * Gets the theme service.
   *
   * @return \Drupal\Core\Theme\ThemeManagerInterface
   */
  public static function theme() {
    return static::$container->get('theme.manager');
  }

  /**
   * Gets the syncing state.
   *
   * @return bool
   *   Returns TRUE is syncing flag set.
   */
  public static function isConfigSyncing() {
    return static::$container->get('config.installer')->isSyncing();
  }

  /**
   * Returns a channel logger object.
   *
   * @param string $channel
   *   The name of the channel. Can be any string, but the general practice is
   *   to use the name of the subsystem calling this.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for this channel.
   */
  public static function logger($channel) {
    return static::$container->get('logger.factory')->get($channel);
  }

  /**
   * Returns the menu tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeInterface
   *   The menu tree.
   */
  public static function menuTree() {
    return static::$container->get('menu.link_tree');
  }

  /**
   * Returns the path validator.
   *
   * @return \Drupal\Core\Path\PathValidatorInterface
   */
  public static function pathValidator() {
    return static::$container->get('path.validator');
  }

  /**
   * Returns the access manager service.
   *
   * @return \Drupal\Core\Access\AccessManagerInterface
   *   The access manager service.
   */
  public static function accessManager() {
    return static::$container->get('access_manager');
  }

}
