<?php

/**
 * @file
 * Contains \Drupal.
 */

use Drupal\Core\DependencyInjection\ContainerNotInitializedException;
use Drupal\Core\Messenger\LegacyMessenger;
use Drupal\Core\Url;
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
 *     public function __construct(LockBackendInterface $lock_backend) {
 *       $this->lockBackend = $lock_backend;
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
  const VERSION = '8.6.12';

  /**
   * Core API compatibility.
   */
  const CORE_COMPATIBILITY = '8.x';

  /**
   * Core minimum schema version.
   */
  const CORE_MINIMUM_SCHEMA_VERSION = 8000;

  /**
   * The currently active container object, or NULL if not initialized yet.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|null
   */
  protected static $container;

  /**
   * Sets a new global container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   A new container instance to replace the current.
   */
  public static function setContainer(ContainerInterface $container) {
    static::$container = $container;
  }

  /**
   * Unsets the global container.
   */
  public static function unsetContainer() {
    static::$container = NULL;
  }

  /**
   * Returns the currently active global container.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerInterface|null
   *
   * @throws \Drupal\Core\DependencyInjection\ContainerNotInitializedException
   */
  public static function getContainer() {
    if (static::$container === NULL) {
      throw new ContainerNotInitializedException('\Drupal::$container is not initialized yet. \Drupal::setContainer() must be called with a real container.');
    }
    return static::$container;
  }

  /**
   * Returns TRUE if the container has been initialized, FALSE otherwise.
   *
   * @return bool
   */
  public static function hasContainer() {
    return static::$container !== NULL;
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
   *
   * @return mixed
   *   The specified service.
   */
  public static function service($id) {
    return static::getContainer()->get($id);
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
    // Check hasContainer() first in order to always return a Boolean.
    return static::hasContainer() && static::getContainer()->has($id);
  }

  /**
   * Gets the app root.
   *
   * @return string
   */
  public static function root() {
    return static::getContainer()->get('app.root');
  }

  /**
   * Gets the active install profile.
   *
   * @return string|null
   *   The name of the active install profile.
   */
  public static function installProfile() {
    return static::getContainer()->getParameter('install_profile');
  }

  /**
   * Indicates if there is a currently active request object.
   *
   * @return bool
   *   TRUE if there is a currently active request object, FALSE otherwise.
   */
  public static function hasRequest() {
    // Check hasContainer() first in order to always return a Boolean.
    return static::hasContainer() && static::getContainer()->has('request_stack') && static::getContainer()->get('request_stack')->getCurrentRequest() !== NULL;
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
    return static::getContainer()->get('request_stack')->getCurrentRequest();
  }

  /**
   * Retrieves the request stack.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack
   */
  public static function requestStack() {
    return static::getContainer()->get('request_stack');
  }

  /**
   * Retrieves the currently active route match object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The currently active route match object.
   */
  public static function routeMatch() {
    return static::getContainer()->get('current_route_match');
  }

  /**
   * Gets the current active user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   */
  public static function currentUser() {
    return static::getContainer()->get('current_user');
  }

  /**
   * Retrieves the entity manager service.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager service.
   *
   * @deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0.
   *   Use \Drupal::entityTypeManager() instead in most cases. If the needed
   *   method is not on \Drupal\Core\Entity\EntityTypeManagerInterface, see the
   *   deprecated \Drupal\Core\Entity\EntityManager to find the
   *   correct interface or service.
   */
  public static function entityManager() {
    return static::getContainer()->get('entity.manager');
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public static function entityTypeManager() {
    return static::getContainer()->get('entity_type.manager');
  }

  /**
   * Returns the current primary database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The current active database's master connection.
   */
  public static function database() {
    return static::getContainer()->get('database');
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
    return static::getContainer()->get('cache.' . $bin);
  }

  /**
   * Retrieves the class resolver.
   *
   * This is to be used in procedural code such as module files to instantiate
   * an object of a class that implements
   * \Drupal\Core\DependencyInjection\ContainerInjectionInterface.
   *
   * One common usecase is to provide a class which contains the actual code
   * of a hook implementation, without having to create a service.
   *
   * @param string $class
   *   (optional) A class name to instantiate.
   *
   * @return \Drupal\Core\DependencyInjection\ClassResolverInterface|object
   *   The class resolver or if $class is provided, a class instance with a
   *   given class definition.
   *
   * @throws \InvalidArgumentException
   *   If $class does not exist.
   */
  public static function classResolver($class = NULL) {
    if ($class) {
      return static::getContainer()->get('class_resolver')->getInstanceFromDefinition($class);
    }
    return static::getContainer()->get('class_resolver');
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
    return static::getContainer()->get('keyvalue.expirable')->get($collection);
  }

  /**
   * Returns the locking layer instance.
   *
   * @return \Drupal\Core\Lock\LockBackendInterface
   *
   * @ingroup lock
   */
  public static function lock() {
    return static::getContainer()->get('lock');
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
   * @return \Drupal\Core\Config\ImmutableConfig
   *   An immutable configuration object.
   */
  public static function config($name) {
    return static::getContainer()->get('config.factory')->get($name);
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
    return static::getContainer()->get('config.factory');
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
    return static::getContainer()->get('queue')->get($name, $reliable);
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
    return static::getContainer()->get('keyvalue')->get($collection);
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
    return static::getContainer()->get('state');
  }

  /**
   * Returns the default http client.
   *
   * @return \GuzzleHttp\Client
   *   A guzzle http client instance.
   */
  public static function httpClient() {
    return static::getContainer()->get('http_client');
  }

  /**
   * Returns the entity query object for this entity type.
   *
   * @param string $entity_type
   *   The entity type (for example, node) for which the query object should be
   *   returned.
   * @param string $conjunction
   *   (optional) Either 'AND' if all conditions in the query need to apply, or
   *   'OR' if any of them is sufficient. Defaults to 'AND'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query object that can query the given entity type.
   */
  public static function entityQuery($entity_type, $conjunction = 'AND') {
    return static::entityTypeManager()->getStorage($entity_type)->getQuery($conjunction);
  }

  /**
   * Returns the entity query aggregate object for this entity type.
   *
   * @param string $entity_type
   *   The entity type (for example, node) for which the query object should be
   *   returned.
   * @param string $conjunction
   *   (optional) Either 'AND' if all conditions in the query need to apply, or
   *   'OR' if any of them is sufficient. Defaults to 'AND'.
   *
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The query object that can query the given entity type.
   */
  public static function entityQueryAggregate($entity_type, $conjunction = 'AND') {
    return static::entityTypeManager()->getStorage($entity_type)->getAggregateQuery($conjunction);
  }

  /**
   * Returns the flood instance.
   *
   * @return \Drupal\Core\Flood\FloodInterface
   */
  public static function flood() {
    return static::getContainer()->get('flood');
  }

  /**
   * Returns the module handler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public static function moduleHandler() {
    return static::getContainer()->get('module_handler');
  }

  /**
   * Returns the typed data manager service.
   *
   * Use the typed data manager service for creating typed data objects.
   *
   * @return \Drupal\Core\TypedData\TypedDataManagerInterface
   *   The typed data manager.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public static function typedDataManager() {
    return static::getContainer()->get('typed_data_manager');
  }

  /**
   * Returns the token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token service.
   */
  public static function token() {
    return static::getContainer()->get('token');
  }

  /**
   * Returns the url generator service.
   *
   * @return \Drupal\Core\Routing\UrlGeneratorInterface
   *   The url generator service.
   */
  public static function urlGenerator() {
    return static::getContainer()->get('url_generator');
  }

  /**
   * Generates a URL string for a specific route based on the given parameters.
   *
   * This method is a convenience wrapper for generating URL strings for URLs
   * that have Drupal routes (that is, most pages generated by Drupal) using
   * the \Drupal\Core\Url object. See \Drupal\Core\Url::fromRoute() for
   * detailed documentation. For non-routed local URIs relative to
   * the base path (like robots.txt) use Url::fromUri()->toString() with the
   * base: scheme.
   *
   * @param string $route_name
   *   The name of the route.
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options.
   * @param bool $collect_bubbleable_metadata
   *   (optional) Defaults to FALSE. When TRUE, both the generated URL and its
   *   associated bubbleable metadata are returned.
   *
   * @return string|\Drupal\Core\GeneratedUrl
   *   A string containing a URL to the given path.
   *   When $collect_bubbleable_metadata is TRUE, a GeneratedUrl object is
   *   returned, containing the generated URL plus bubbleable metadata.
   *
   * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
   * @see \Drupal\Core\Url
   * @see \Drupal\Core\Url::fromRoute()
   * @see \Drupal\Core\Url::fromUri()
   *
   * @deprecated as of Drupal 8.0.x, will be removed before Drupal 9.0.0.
   *   Instead create a \Drupal\Core\Url object directly, for example using
   *   Url::fromRoute().
   */
  public static function url($route_name, $route_parameters = [], $options = [], $collect_bubbleable_metadata = FALSE) {
    return static::getContainer()->get('url_generator')->generateFromRoute($route_name, $route_parameters, $options, $collect_bubbleable_metadata);
  }

  /**
   * Returns the link generator service.
   *
   * @return \Drupal\Core\Utility\LinkGeneratorInterface
   */
  public static function linkGenerator() {
    return static::getContainer()->get('link_generator');
  }

  /**
   * Renders a link with a given link text and Url object.
   *
   * This method is a convenience wrapper for the link generator service's
   * generate() method.
   *
   * @param string $text
   *   The link text for the anchor tag.
   * @param \Drupal\Core\Url $url
   *   The URL object used for the link.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A GeneratedLink object containing a link to the given route and
   *   parameters and bubbleable metadata.
   *
   * @see \Drupal\Core\Utility\LinkGeneratorInterface::generate()
   * @see \Drupal\Core\Url
   *
   * @deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0.
   *   Use \Drupal\Core\Link instead.
   *   Example:
   *   @code
   *     $link = Link::fromTextAndUrl($text, $url);
   *   @endcode
   */
  public static function l($text, Url $url) {
    return static::getContainer()->get('link_generator')->generate($text, $url);
  }

  /**
   * Returns the string translation service.
   *
   * @return \Drupal\Core\StringTranslation\TranslationManager
   *   The string translation manager.
   */
  public static function translation() {
    return static::getContainer()->get('string_translation');
  }

  /**
   * Returns the language manager service.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager.
   */
  public static function languageManager() {
    return static::getContainer()->get('language_manager');
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
    return static::getContainer()->get('csrf_token');
  }

  /**
   * Returns the transliteration service.
   *
   * @return \Drupal\Core\Transliteration\PhpTransliteration
   *   The transliteration manager.
   */
  public static function transliteration() {
    return static::getContainer()->get('transliteration');
  }

  /**
   * Returns the form builder service.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  public static function formBuilder() {
    return static::getContainer()->get('form_builder');
  }

  /**
   * Gets the theme service.
   *
   * @return \Drupal\Core\Theme\ThemeManagerInterface
   */
  public static function theme() {
    return static::getContainer()->get('theme.manager');
  }

  /**
   * Gets the syncing state.
   *
   * @return bool
   *   Returns TRUE is syncing flag set.
   */
  public static function isConfigSyncing() {
    return static::getContainer()->get('config.installer')->isSyncing();
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
    return static::getContainer()->get('logger.factory')->get($channel);
  }

  /**
   * Returns the menu tree.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeInterface
   *   The menu tree.
   */
  public static function menuTree() {
    return static::getContainer()->get('menu.link_tree');
  }

  /**
   * Returns the path validator.
   *
   * @return \Drupal\Core\Path\PathValidatorInterface
   */
  public static function pathValidator() {
    return static::getContainer()->get('path.validator');
  }

  /**
   * Returns the access manager service.
   *
   * @return \Drupal\Core\Access\AccessManagerInterface
   *   The access manager service.
   */
  public static function accessManager() {
    return static::getContainer()->get('access_manager');
  }

  /**
   * Returns the redirect destination helper.
   *
   * @return \Drupal\Core\Routing\RedirectDestinationInterface
   *   The redirect destination helper.
   */
  public static function destination() {
    return static::getContainer()->get('redirect.destination');
  }

  /**
   * Returns the entity definition update manager.
   *
   * @return \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   *   The entity definition update manager.
   */
  public static function entityDefinitionUpdateManager() {
    return static::getContainer()->get('entity.definition_update_manager');
  }

  /**
   * Returns the time service.
   *
   * @return \Drupal\Component\Datetime\TimeInterface
   *   The time service.
   */
  public static function time() {
    return static::getContainer()->get('datetime.time');
  }

  /**
   * Returns the messenger.
   *
   * @return \Drupal\Core\Messenger\MessengerInterface
   *   The messenger.
   */
  public static function messenger() {
    // @todo Replace with service once LegacyMessenger is removed in 9.0.0.
    // @see https://www.drupal.org/node/2928994
    return new LegacyMessenger();
  }

}
