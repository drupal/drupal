<?php

/**
 * @file
 * Definition of Drupal\Core\CoreBundle.
 */

namespace Drupal\Core;

use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterMatchersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterNestedMatchersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterSerializationClassesPass;
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

    // Register active configuration storage.
    $container
      ->register('config.cachedstorage.storage', 'Drupal\Core\Config\FileStorage')
      ->addArgument(config_get_config_directory(CONFIG_ACTIVE_DIRECTORY));
    // @todo Replace this with a cache.factory service plus 'config' argument.
    $container
      ->register('cache.config', 'Drupal\Core\Cache\CacheBackendInterface')
      ->setFactoryClass('Drupal\Core\Cache\CacheFactory')
      ->setFactoryMethod('get')
      ->addArgument('config');

    $container
      ->register('config.storage', 'Drupal\Core\Config\CachedStorage')
      ->addArgument(new Reference('config.cachedstorage.storage'))
      ->addArgument(new Reference('cache.config'));

    $container->register('config.factory', 'Drupal\Core\Config\ConfigFactory')
      ->addArgument(new Reference('config.storage'))
      ->addArgument(new Reference('event_dispatcher'))
      ->addTag('persist');

    // Register staging configuration storage.
    $container
      ->register('config.storage.staging', 'Drupal\Core\Config\FileStorage')
      ->addArgument(config_get_config_directory(CONFIG_STAGING_DIRECTORY));

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

    $container->register('path.alias_manager', 'Drupal\Core\Path\AliasManager')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('keyvalue'));

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
    $container->register('plugin.manager.entity', 'Drupal\Core\Entity\EntityManager');

    // The 'request' scope and service enable services to depend on the Request
    // object and get reconstructed when the request object changes (e.g.,
    // during a subrequest).
    $container->addScope(new Scope('request'));
    $container->register('request', 'Symfony\Component\HttpFoundation\Request')
      ->setSynthetic(TRUE);

    $container->register('event_dispatcher', 'Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher')
      ->addArgument(new Reference('service_container'));
    $container->register('resolver', 'Drupal\Core\ControllerResolver')
      ->addArgument(new Reference('service_container'));
    $container->register('http_kernel', 'Drupal\Core\HttpKernel')
      ->addArgument(new Reference('event_dispatcher'))
      ->addArgument(new Reference('service_container'))
      ->addArgument(new Reference('resolver'));
    $container->register('language_manager', 'Drupal\Core\Language\LanguageManager')
      ->addArgument(new Reference('request'))
      ->setScope('request');
    $container->register('database.slave', 'Drupal\Core\Database\Connection')
      ->setFactoryClass('Drupal\Core\Database\Database')
      ->setFactoryMethod('getConnection')
      ->addArgument('slave');
    $container->register('typed_data', 'Drupal\Core\TypedData\TypedDataManager');
    // Add the user's storage for temporary, non-cache data.
    $container->register('lock', 'Drupal\Core\Lock\DatabaseLockBackend');
    $container->register('user.tempstore', 'Drupal\user\TempStoreFactory')
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('lock'));

    $this->registerTwig($container);

    // Add the entity query factory.
    $container->register('entity.query', 'Drupal\Core\Entity\Query\QueryFactory')
      ->addArgument(new Reference('service_container'));

    $container->register('router.dumper', 'Drupal\Core\Routing\MatcherDumper')
      ->addArgument(new Reference('database'));
    $container->register('router.builder', 'Drupal\Core\Routing\RouteBuilder')
      ->addArgument(new Reference('router.dumper'))
      ->addArgument(new Reference('lock'))
      ->addArgument(new Reference('event_dispatcher'));


    $container->register('matcher', 'Drupal\Core\Routing\ChainMatcher');
    $container->register('legacy_url_matcher', 'Drupal\Core\LegacyUrlMatcher')
      ->addTag('chained_matcher');
    $container->register('nested_matcher', 'Drupal\Core\Routing\NestedMatcher')
      ->addTag('chained_matcher', array('priority' => 5));

    $container
      ->register('cache.path', 'Drupal\Core\Cache\CacheBackendInterface')
      ->setFactoryClass('Drupal\Core\Cache\CacheFactory')
      ->setFactoryMethod('get')
      ->addArgument('path');

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

    // The following services are tagged as 'nested_matcher' services and are
    // processed in the RegisterNestedMatchersPass compiler pass. Each one
    // needs to be set on the matcher using a different method, so we use a
    // tag attribute, 'method', which can be retrieved and passed to the
    // addMethodCall() method that gets called on the matcher service in the
    // compiler pass.
    $container->register('path_matcher', 'Drupal\Core\Routing\PathMatcher')
      ->addArgument(new Reference('database'))
      ->addTag('nested_matcher', array('method' => 'setInitialMatcher'));
    $container->register('http_method_matcher', 'Drupal\Core\Routing\HttpMethodMatcher')
      ->addTag('nested_matcher', array('method' => 'addPartialMatcher'));
    $container->register('mime_type_matcher', 'Drupal\Core\Routing\MimeTypeMatcher')
      ->addTag('nested_matcher', array('method' => 'addPartialMatcher'));
    $container->register('first_entry_final_matcher', 'Drupal\Core\Routing\FirstEntryFinalMatcher')
      ->addTag('nested_matcher', array('method' => 'setFinalMatcher'));

    $container->register('router_processor_subscriber', 'Drupal\Core\EventSubscriber\RouteProcessorSubscriber')
      ->addTag('event_subscriber');
    $container->register('router_listener', 'Symfony\Component\HttpKernel\EventListener\RouterListener')
      ->addArgument(new Reference('matcher'))
      ->addTag('event_subscriber');
    $container->register('content_negotiation', 'Drupal\Core\ContentNegotiation');
    $container->register('view_subscriber', 'Drupal\Core\EventSubscriber\ViewSubscriber')
      ->addArgument(new Reference('content_negotiation'))
      ->addTag('event_subscriber');
    $container->register('legacy_access_subscriber', 'Drupal\Core\EventSubscriber\LegacyAccessSubscriber')
      ->addTag('event_subscriber');
    $container->register('access_manager', 'Drupal\Core\Access\AccessManager')
      ->addArgument(new Reference('request'))
      ->addMethodCall('setContainer', array(new Reference('service_container')));
    $container->register('access_subscriber', 'Drupal\Core\EventSubscriber\AccessSubscriber')
      ->addArgument(new Reference('access_manager'))
      ->addTag('event_subscriber');
    $container->register('access_check.default', 'Drupal\Core\Access\DefaultAccessCheck')
      ->addTag('access_check');
    $container->register('access_check.permission', 'Drupal\Core\Access\PermissionAccessCheck')
      ->addTag('access_check');
    $container->register('maintenance_mode_subscriber', 'Drupal\Core\EventSubscriber\MaintenanceModeSubscriber')
      ->addTag('event_subscriber');
    $container->register('path_subscriber', 'Drupal\Core\EventSubscriber\PathSubscriber')
      ->addArgument(new Reference('path.alias_manager.cached'))
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
      ->addTag('event_subscriber');
    $container->register('config_global_override_subscriber', 'Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber')
      ->addTag('event_subscriber');
    $container->register('exception_listener', 'Drupal\Core\EventSubscriber\ExceptionListener')
      ->addTag('event_subscriber')
      ->addArgument(new Reference('service_container'))
      ->setFactoryClass('Drupal\Core\ExceptionController')
      ->setFactoryMethod('getExceptionListener');

    $container
      ->register('transliteration', 'Drupal\Core\Transliteration\PHPTransliteration');

    // Add Serializer with arguments to be replaced in the compiler pass.
    $container->register('serializer', 'Symfony\Component\Serializer\Serializer')
      ->addArgument(array())
      ->addArgument(array());

    $container->register('serializer.normalizer.complex_data', 'Drupal\Core\Serialization\ComplexDataNormalizer')->addTag('normalizer');
    $container->register('serializer.normalizer.list', 'Drupal\Core\Serialization\ListNormalizer')->addTag('normalizer');
    $container->register('serializer.normalizer.typed_data', 'Drupal\Core\Serialization\TypedDataNormalizer')->addTag('normalizer');
    $container->register('serializer.encoder.json', 'Drupal\Core\Serialization\JsonEncoder')->addTag('encoder');

    $container->register('flood', 'Drupal\Core\Flood\DatabaseBackend')
      ->addArgument(new Reference('database'));

    $container->addCompilerPass(new RegisterMatchersPass());
    $container->addCompilerPass(new RegisterNestedMatchersPass());
    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    // Add a compiler pass for adding Normalizers and Encoders to Serializer.
    $container->addCompilerPass(new RegisterSerializationClassesPass());
    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    $container->addCompilerPass(new RegisterAccessChecksPass());
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
        'cache' => TRUE,
        'base_template_class' => 'Drupal\Core\Template\TwigTemplate',
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1712444.
        'autoescape' => FALSE,
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1806538.
        'strict_variables' => FALSE,
        // @todo Maybe make debug mode dependent on "production mode" setting.
        'debug' => TRUE,
        // @todo Make auto reload mode dependent on "production mode" setting.
        'auto_reload' => FALSE,
      ))
      ->addMethodCall('addExtension', array(new Definition('Drupal\Core\Template\TwigExtension')))
      // @todo Figure out what to do about debugging functions.
      // @see http://drupal.org/node/1804998
      ->addMethodCall('addExtension', array(new Definition('Twig_Extension_Debug')));
  }
}
