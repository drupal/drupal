<?php

/**
 * @file
 * Definition of Drupal\Core\CoreBundle.
 */

namespace Drupal\Core;

use Drupal\Core\Cache\CacheFactory;
use Drupal\Core\Cache\ListCacheBinsPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterMatchersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterPathProcessorsPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterRouteFiltersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterParamConvertersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Bundle class for mandatory core services.
 *
 * This is where Drupal core registers all of its services to the Dependency
 * Injection Container. Modules wishing to register services to the container
 * should extend Symfony's Bundle class directly, not this class.
 */
class CoreBundle extends Bundle {

  /**
   * Implements \Symfony\Component\HttpKernel\Bundle\BundleInterface::build().
   */
  public function build(ContainerBuilder $container) {
    $this->registerCache($container);
    // Register active configuration storage.
    $container
      ->register('config.cachedstorage.storage', 'Drupal\Core\Config\FileStorage')
      ->addArgument(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
    $container
      ->register('config.storage', 'Drupal\Core\Config\CachedStorage')
      ->addArgument(new Reference('config.cachedstorage.storage'))
      ->addArgument(new Reference('cache.config'));

    $container->register('config.context.factory', 'Drupal\Core\Config\Context\ConfigContextFactory')
      ->addArgument(new Reference('event_dispatcher'));

    $container->register('config.context', 'Drupal\Core\Config\Context\ContextInterface')
      ->setFactoryService(new Reference('config.context.factory'))
      ->setFactoryMethod('get')
      ->addTag('persist');

    // Register a config context with no overrides for use in administration
    // forms, enabling modules and importing configuration.
    $container->register('config.context.free', 'Drupal\Core\Config\Context\ContextInterface')
      ->setFactoryService(new Reference('config.context.factory'))
      ->setFactoryMethod('get')
      ->addArgument('Drupal\Core\Config\Context\FreeConfigContext');

    $container->register('config.factory', 'Drupal\Core\Config\ConfigFactory')
      ->addArgument(new Reference('config.storage'))
      ->addArgument(new Reference('config.context'))
      ->addTag('persist');

    // Register staging configuration storage.
    $container
      ->register('config.storage.staging', 'Drupal\Core\Config\FileStorage')
      ->addArgument(config_get_config_directory(CONFIG_STAGING_DIRECTORY));

    // Register import snapshot configuration storage.
    $container
      ->register('config.storage.snapshot', 'Drupal\Core\Config\DatabaseStorage')
      ->addArgument(new Reference('database'))
      ->addArgument('config_snapshot');

    // Register schema configuration storage.
    $container
      ->register('config.storage.schema', 'Drupal\Core\Config\Schema\SchemaStorage');

    // Register the typed configuration data manager.
    $container->register('config.typed', 'Drupal\Core\Config\TypedConfigManager')
      ->addArgument(new Reference('config.storage'))
      ->addArgument(new Reference('config.storage.schema'));

    // Register the service for the default database connection.
    $container->register('database', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('default');
    // Register the KeyValueStore factory.
    $container
      ->register('keyvalue', 'Drupal\Core\KeyValueStore\KeyValueFactory')
      ->addArgument(new Reference('service_container'));
    $container
      ->register('keyvalue.database', 'Drupal\Core\KeyValueStore\KeyValueDatabaseFactory')
      ->addArgument(new Reference('database'));
    // Register the KeyValueStoreExpirable factory.
    $container
      ->register('keyvalue.expirable', 'Drupal\Core\KeyValueStore\KeyValueExpirableFactory')
      ->addArgument(new Reference('service_container'));
    $container
      ->register('keyvalue.expirable.database', 'Drupal\Core\KeyValueStore\KeyValueDatabaseExpirableFactory')
      ->addArgument(new Reference('database'))
      ->addTag('needs_destruction');

    $container->register('settings', 'Drupal\Component\Utility\Settings')
      ->setFactoryClass('Drupal\Component\Utility\Settings')
      ->setFactoryMethod('getSingleton');

    // Register the State k/v store as a service.
    $container->register('state', 'Drupal\Core\KeyValueStore\KeyValueStoreInterface')
      ->setFactoryService(new Reference('keyvalue'))
      ->setFactoryMethod('get')
      ->addArgument('state');

    // Register the Queue factory.
    $container
      ->register('queue', 'Drupal\Core\Queue\QueueFactory')
      ->addArgument(new Reference('settings'))
      ->addMethodCall('setContainer', array(new Reference('service_container')));
    $container
      ->register('queue.database', 'Drupal\Core\Queue\QueueDatabaseFactory')
      ->addArgument(new Reference('database'));

    $container->register('path.alias_whitelist', 'Drupal\Core\Path\AliasWhitelist')
      ->addArgument('path_alias_whitelist')
      ->addArgument('cache')
      ->addArgument(new Reference('keyvalue'))
      ->addArgument(new Reference('database'))
      ->addTag('needs_destruction');

     $container->register('path.alias_manager', 'Drupal\Core\Path\AliasManager')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('path.alias_whitelist'))
      ->addArgument(new Reference('language_manager'));

    $container->register('http_client_simpletest_subscriber', 'Drupal\Core\Http\Plugin\SimpletestHttpRequestSubscriber');
    $container->register('http_default_client', 'Guzzle\Http\Client')
      ->addArgument(NULL)
      ->addArgument(array(
        'curl.CURLOPT_TIMEOUT' => 30.0,
        'curl.CURLOPT_MAXREDIRS' => 3,
      ))
      ->addMethodCall('addSubscriber', array(new Reference('http_client_simpletest_subscriber')))
      ->addMethodCall('setUserAgent', array('Drupal (+http://drupal.org/)'));

    // Register the EntityManager.
    $container->register('plugin.manager.entity', 'Drupal\Core\Entity\EntityManager')
      ->addArgument('%container.namespaces%');

    // The 'request' scope and service enable services to depend on the Request
    // object and get reconstructed when the request object changes (e.g.,
    // during a subrequest).
    $container->addScope(new Scope('request'));
    $container->register('request', 'Symfony\Component\HttpFoundation\Request');

    $container->register('event_dispatcher', 'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher')
      ->addArgument(new Reference('service_container'));
    $container->register('controller_resolver', 'Drupal\Core\ControllerResolver')
      ->addArgument(new Reference('service_container'));

    $this->registerModuleHandler($container);

    $container->register('http_kernel', 'Drupal\Core\HttpKernel')
      ->addArgument(new Reference('event_dispatcher'))
      ->addArgument(new Reference('service_container'))
      ->addArgument(new Reference('controller_resolver'));

    // Register the 'language_manager' service.
    $container->register('language_manager', 'Drupal\Core\Language\LanguageManager');

    $container->register('database.slave', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('slave');
    $container->register('typed_data', 'Drupal\Core\TypedData\TypedDataManager')
      ->addMethodCall('setValidationConstraintManager', array(new Reference('validation.constraint')));
    $container->register('validation.constraint', 'Drupal\Core\Validation\ConstraintManager')
      ->addArgument('%container.namespaces%');

    // Add the user's storage for temporary, non-cache data.
    $container->register('lock', 'Drupal\Core\Lock\DatabaseLockBackend');
    $container->register('user.tempstore', 'Drupal\user\TempStoreFactory')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('lock'));

    $this->registerTwig($container);
    $this->registerRouting($container);

    // Add the entity query factories.
    $container->register('entity.query', 'Drupal\Core\Entity\Query\QueryFactory')
      ->addArgument(new Reference('plugin.manager.entity'))
      ->addMethodCall('setContainer', array(new Reference('service_container')));
    $container->register('entity.query.config', 'Drupal\Core\Config\Entity\Query\QueryFactory')
      ->addArgument(new Reference('config.storage'));

    $container->register('router.dumper', 'Drupal\Core\Routing\MatcherDumper')
      ->addArgument(new Reference('database'));
    $container->register('router.builder', 'Drupal\Core\Routing\RouteBuilder')
      ->addArgument(new Reference('router.dumper'))
      ->addArgument(new Reference('lock'))
      ->addArgument(new Reference('event_dispatcher'))
      ->addArgument(new Reference('module_handler'));

    $container->register('path.alias_manager.cached', 'Drupal\Core\CacheDecorator\AliasManagerCacheDecorator')
      ->addArgument(new Reference('path.alias_manager'))
      ->addArgument(new Reference('cache.path'));

    $container->register('path.crud', 'Drupal\Core\Path\Path')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('path.alias_manager'));

    // Add password hashing service. The argument to PhpassHashedPassword
    // constructor is the log2 number of iterations for password stretching.
    // This should increase by 1 every Drupal version in order to counteract
    // increases in the speed and power of computers available to crack the
    // hashes. The current password hashing method was introduced in Drupal 7
    // with a log2 count of 15.
    $container->register('password', 'Drupal\Core\Password\PhpassHashedPassword')
      ->addArgument(16);

    // The following services are tagged as 'route_filter' services and are
    // processed in the RegisterRouteFiltersPass compiler pass.
    $container->register('mime_type_matcher', 'Drupal\Core\Routing\MimeTypeMatcher')
      ->addTag('route_filter');

    $container->register('paramconverter_manager', 'Drupal\Core\ParamConverter\ParamConverterManager')
      ->addTag('route_enhancer');
    $container->register('paramconverter.entity', 'Drupal\Core\ParamConverter\EntityConverter')
      ->addArgument(new Reference('plugin.manager.entity'))
      ->addTag('paramconverter');

    $container->register('router_processor_subscriber', 'Drupal\Core\EventSubscriber\RouteProcessorSubscriber')
      ->addArgument(new Reference('content_negotiation'))
      ->addTag('event_subscriber');
    $container->register('router_listener', 'Symfony\Component\HttpKernel\EventListener\RouterListener')
      ->addArgument(new Reference('router'))
      ->addTag('event_subscriber');
    $container->register('content_negotiation', 'Drupal\Core\ContentNegotiation');
    $container->register('view_subscriber', 'Drupal\Core\EventSubscriber\ViewSubscriber')
      ->addArgument(new Reference('content_negotiation'))
      ->addTag('event_subscriber');
    $container->register('legacy_access_subscriber', 'Drupal\Core\EventSubscriber\LegacyAccessSubscriber')
      ->addTag('event_subscriber');
    $container->register('access_manager', 'Drupal\Core\Access\AccessManager')
      ->addMethodCall('setContainer', array(new Reference('service_container')));
    $container->register('access_subscriber', 'Drupal\Core\EventSubscriber\AccessSubscriber')
      ->addArgument(new Reference('access_manager'))
      ->addTag('event_subscriber');
    $container->register('access_check.default', 'Drupal\Core\Access\DefaultAccessCheck')
      ->addTag('access_check');
    $container->register('maintenance_mode_subscriber', 'Drupal\Core\EventSubscriber\MaintenanceModeSubscriber')
      ->addTag('event_subscriber');
    $container->register('path_subscriber', 'Drupal\Core\EventSubscriber\PathSubscriber')
      ->addArgument(new Reference('path.alias_manager.cached'))
      ->addArgument(new Reference('path_processor_manager'))
      ->addTag('event_subscriber');
    $container->register('legacy_request_subscriber', 'Drupal\Core\EventSubscriber\LegacyRequestSubscriber')
      ->addTag('event_subscriber');
    $container->register('legacy_controller_subscriber', 'Drupal\Core\EventSubscriber\LegacyControllerSubscriber')
      ->addTag('event_subscriber');
    $container->register('finish_response_subscriber', 'Drupal\Core\EventSubscriber\FinishResponseSubscriber')
      ->addArgument(new Reference('language_manager'))
      ->setScope('request')
      ->addTag('event_subscriber');
    $container->register('request_close_subscriber', 'Drupal\Core\EventSubscriber\RequestCloseSubscriber')
      ->addArgument(new Reference('module_handler'))
      ->addTag('event_subscriber');
    $container->register('config_global_override_subscriber', 'Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber')
      ->addTag('event_subscriber');
    $container->register('language_request_subscriber', 'Drupal\Core\EventSubscriber\LanguageRequestSubscriber')
      ->addArgument(new Reference('language_manager'))
      ->addTag('event_subscriber');

    $container->register('exception_controller', 'Drupal\Core\ExceptionController')
      ->addArgument(new Reference('content_negotiation'))
      ->addMethodCall('setContainer', array(new Reference('service_container')));
    $container->register('exception_listener', 'Symfony\Component\HttpKernel\EventListener\ExceptionListener')
      ->addTag('event_subscriber')
      ->addArgument(array(new Reference('exception_controller'), 'execute'));

    $this->registerPathProcessors($container);

    $container
      ->register('transliteration', 'Drupal\Core\Transliteration\PHPTransliteration');

    $container->register('flood', 'Drupal\Core\Flood\DatabaseBackend')
      ->addArgument(new Reference('database'));

    $container->register('plugin.manager.condition', 'Drupal\Core\Condition\ConditionManager');

    $container->register('kernel_destruct_subscriber', 'Drupal\Core\EventSubscriber\KernelDestructionSubscriber')
      ->addMethodCall('setContainer', array(new Reference('service_container')))
      ->addTag('event_subscriber');

    // Register Ajax event subscriber.
    $container->register('ajax.subscriber', 'Drupal\Core\Ajax\AjaxSubscriber')
      ->addTag('event_subscriber');

    // Register image toolkit manager.
    $container
      ->register('image.toolkit.manager', 'Drupal\system\Plugin\ImageToolkitManager')
      ->addArgument('%container.namespaces%');
    // Register image toolkit.
    $container
      ->register('image.toolkit', 'Drupal\system\Plugin\ImageToolkitInterface')
      ->setFactoryService('image.toolkit.manager')
      ->setFactoryMethod('getDefaultToolkit');

    $container->addCompilerPass(new RegisterMatchersPass());
    $container->addCompilerPass(new RegisterRouteFiltersPass());
    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    $container->addCompilerPass(new RegisterAccessChecksPass());
    // Add a compiler pass for upcasting of entity route parameters.
    $container->addCompilerPass(new RegisterParamConvertersPass());
    $container->addCompilerPass(new RegisterRouteEnhancersPass());
    // Add a compiler pass for registering services needing destruction.
    $container->addCompilerPass(new RegisterServicesForDestructionPass());
  }

  /**
   * Registers the module handler.
   */
  protected function registerModuleHandler(ContainerBuilder $container) {
    // The ModuleHandler manages enabled modules and provides the ability to
    // invoke hooks in all enabled modules.
    if ($container->getParameter('kernel.environment') == 'install') {
      // During installation we use the non-cached version.
      $container->register('module_handler', 'Drupal\Core\Extension\ModuleHandler')
        ->addArgument('%container.modules%');
    }
    else {
      $container->register('module_handler', 'Drupal\Core\Extension\CachedModuleHandler')
        ->addArgument('%container.modules%')
        ->addArgument(new Reference('state'))
        ->addArgument(new Reference('cache.bootstrap'));
    }
  }

  /**
   * Registers the various services for the routing system.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  protected function registerRouting(ContainerBuilder $container) {
    $container->register('router.request_context', 'Symfony\Component\Routing\RequestContext')
      ->addMethodCall('fromRequest', array(new Reference('request')));

    $container->register('router.route_provider', 'Drupal\Core\Routing\RouteProvider')
      ->addArgument(new Reference('database'));
    $container->register('router.matcher.final_matcher', 'Drupal\Core\Routing\UrlMatcher');
    $container->register('router.matcher', 'Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher')
      ->addArgument(new Reference('router.route_provider'))
      ->addMethodCall('setFinalMatcher', array(new Reference('router.matcher.final_matcher')));
    $container->register('router.generator', 'Drupal\Core\Routing\UrlGenerator')
      ->addArgument(new Reference('router.route_provider'))
      ->addArgument(new Reference('path.alias_manager.cached'));
    $container->register('router.dynamic', 'Symfony\Cmf\Component\Routing\DynamicRouter')
      ->addArgument(new Reference('router.request_context'))
      ->addArgument(new Reference('router.matcher'))
      ->addArgument(new Reference('router.generator'));

    $container->register('legacy_generator', 'Drupal\Core\Routing\NullGenerator');
    $container->register('legacy_url_matcher', 'Drupal\Core\LegacyUrlMatcher');
    $container->register('legacy_router', 'Symfony\Cmf\Component\Routing\DynamicRouter')
            ->addArgument(new Reference('router.request_context'))
            ->addArgument(new Reference('legacy_url_matcher'))
            ->addArgument(new Reference('legacy_generator'));

    $container->register('router', 'Symfony\Cmf\Component\Routing\ChainRouter')
      ->addMethodCall('setContext', array(new Reference('router.request_context')))
      ->addMethodCall('add', array(new Reference('router.dynamic')))
      ->addMethodCall('add', array(new Reference('legacy_router')));
  }

  /**
   * Registers Twig services.
   */
  protected function registerTwig(ContainerBuilder $container) {
    $container->register('twig.loader.filesystem', 'Twig_Loader_Filesystem')
      ->addArgument(DRUPAL_ROOT);
    $container->setAlias('twig.loader', 'twig.loader.filesystem');

    $container->register('twig', 'Drupal\Core\Template\TwigEnvironment')
      ->addArgument(new Reference('twig.loader'))
      ->addArgument(array(
        // This is saved / loaded via drupal_php_storage().
        // All files can be refreshed by clearing caches.
        // @todo ensure garbage collection of expired files.
        'cache' => settings()->get('twig_cache', TRUE),
        'base_template_class' => 'Drupal\Core\Template\TwigTemplate',
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1712444.
        'autoescape' => FALSE,
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1806538.
        'strict_variables' => FALSE,
        'debug' => settings()->get('twig_debug', FALSE),
        'auto_reload' => settings()->get('twig_auto_reload', NULL),
      ))
      ->addMethodCall('addExtension', array(new Definition('Drupal\Core\Template\TwigExtension')))
      // @todo Figure out what to do about debugging functions.
      // @see http://drupal.org/node/1804998
      ->addMethodCall('addExtension', array(new Definition('Twig_Extension_Debug')));
  }

  /**
   * Register services related to path processing.
   */
  protected function registerPathProcessors(ContainerBuilder $container) {
    // Register the path processor manager service.
    $container->register('path_processor_manager', 'Drupal\Core\PathProcessor\PathProcessorManager');
    // Register the processor that urldecodes the path.
    $container->register('path_processor_decode', 'Drupal\Core\PathProcessor\PathProcessorDecode')
      ->addTag('path_processor_inbound', array('priority' => 1000));
    // Register the processor that resolves the front page.
    $container->register('path_processor_front', 'Drupal\Core\PathProcessor\PathProcessorFront')
      ->addArgument(new Reference('config.factory'))
      ->addTag('path_processor_inbound', array('priority' => 200));
    // Register the alias path processor.
    $container->register('path_processor_alias', 'Drupal\Core\PathProcessor\PathProcessorAlias')
      ->addArgument(new Reference('path.alias_manager'))
      ->addTag('path_processor_inbound', array('priority' => 100));

    // Add the compiler pass that will process the tagged services.
    $container->addCompilerPass(new RegisterPathProcessorsPass());
  }

  /**
   * Register services related to cache.
   */
  protected function registerCache(ContainerBuilder $container) {
    // This factory chooses the backend service for a given bin.
    $container
      ->register('cache_factory', 'Drupal\Core\Cache\CacheFactory')
      ->addArgument(new Reference('settings'))
      ->addMethodCall('setContainer', array(new Reference('service_container')));
    // These are the core provided backend services.
    $container
      ->register('cache.backend.database', 'Drupal\Core\Cache\DatabaseBackendFactory')
      ->addArgument(new Reference('database'));
    $container
      ->register('cache.backend.memory', 'Drupal\Core\Cache\MemoryBackendFactory');
    // Register a service for each bin for injecting purposes.
    foreach (array('bootstrap', 'config', 'cache', 'menu', 'page', 'path') as $bin) {
      CacheFactory::registerBin($container, $bin);
    }

    $container->addCompilerPass(new ListCacheBinsPass());
  }

}
