<?php

/**
 * @file
 * Definition of Drupal\Core\CoreServiceProvider.
 */

namespace Drupal\Core;

use Drupal\Core\Cache\CacheContextsPass;
use Drupal\Core\Cache\ListCacheBinsPass;
use Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\Compiler\ModifyServiceDefinitionsPass;
use Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAuthenticationPass;
use Drupal\Core\Plugin\PluginManagerPass;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * ServiceProvider class for mandatory core services.
 *
 * This is where Drupal core registers all of its compiler passes.
 * The service definitions themselves are in core/core.services.yml with a
 * few, documented exceptions (typically, install requirements).
 *
 * Modules wishing to register services to the container should use
 * modulename.services.yml in their respective directories.
 *
 * @ingroup container
 */
class CoreServiceProvider implements ServiceProviderInterface  {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $this->registerTwig($container);
    $this->registerUuid($container);
    $this->registerTest($container);

    // Add the compiler pass that lets service providers modify existing
    // service definitions. This pass must come first so that later
    // list-building passes are operating on the post-alter services list.
    $container->addCompilerPass(new ModifyServiceDefinitionsPass());

    $container->addCompilerPass(new BackendCompilerPass());

    // Collect tagged handler services as method calls on consumer services.
    $container->addCompilerPass(new TaggedHandlersPass());

    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);

    $container->addCompilerPass(new RegisterAccessChecksPass());

    // Add a compiler pass for registering services needing destruction.
    $container->addCompilerPass(new RegisterServicesForDestructionPass());

    // Add the compiler pass that will process the tagged services.
    $container->addCompilerPass(new ListCacheBinsPass());
    $container->addCompilerPass(new CacheContextsPass());

    // Add the compiler pass that will process tagged authentication services.
    $container->addCompilerPass(new RegisterAuthenticationPass());

    // Register plugin managers.
    $container->addCompilerPass(new PluginManagerPass());
  }

  /**
   * Registers Twig services.
   *
   * This method is public and static so that it can be reused in the installer.
   */
  public static function registerTwig(ContainerBuilder $container) {
    $container->register('twig.loader.filesystem', 'Twig_Loader_Filesystem')
      ->addArgument(DRUPAL_ROOT);
    $container->setAlias('twig.loader', 'twig.loader.filesystem');

     $twig_extension = new Definition('Drupal\Core\Template\TwigExtension');
     $twig_extension->addMethodCall('setGenerators', array(new Reference('url_generator')));

    $container->register('twig', 'Drupal\Core\Template\TwigEnvironment')
      ->addArgument(new Reference('twig.loader'))
      ->addArgument(array(
        // This is saved / loaded via drupal_php_storage().
        // All files can be refreshed by clearing caches.
        // @todo ensure garbage collection of expired files.
        // When in the installer, twig_cache must be FALSE until we know the
        // files folder is writable.
        'cache' => drupal_installation_attempted() ? FALSE : Settings::get('twig_cache', TRUE),
        'autoescape' => TRUE,
        'debug' => Settings::get('twig_debug', FALSE),
        'auto_reload' => Settings::get('twig_auto_reload', NULL),
      ))
      ->addArgument(new Reference('module_handler'))
      ->addArgument(new Reference('theme_handler'))
      ->addMethodCall('addExtension', array($twig_extension))
      // @todo Figure out what to do about debugging functions.
      // @see http://drupal.org/node/1804998
      ->addMethodCall('addExtension', array(new Definition('Twig_Extension_Debug')))
      ->addTag('service_collector', array(
        'tag' => 'twig.extension',
        'call' => 'addExtension',
      ));
  }

  /**
   * Determines and registers the UUID service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   *
   * @return string
   *   Class name for the UUID service.
   */
  public static function registerUuid(ContainerBuilder $container) {
    $uuid_class = 'Drupal\Component\Uuid\Php';

    // Debian/Ubuntu uses the (broken) OSSP extension as their UUID
    // implementation. The OSSP implementation is not compatible with the
    // PECL functions.
    if (function_exists('uuid_create') && !function_exists('uuid_make')) {
      $uuid_class = 'Drupal\Component\Uuid\Pecl';
    }
    // Try to use the COM implementation for Windows users.
    elseif (function_exists('com_create_guid')) {
      $uuid_class = 'Drupal\Component\Uuid\Com';
    }

    $container->register('uuid', $uuid_class);
    return $uuid_class;
  }

  /**
   * Registers services and event subscribers for a site under test.
   */
  protected function registerTest(ContainerBuilder $container) {
    // Do nothing if we are not in a test environment.
    if (!drupal_valid_test_ua()) {
      return;
    }
    // Add the HTTP request subscriber to Guzzle.
    $container
      ->register('test.http_client.request_subscriber', 'Drupal\Core\Test\EventSubscriber\HttpRequestSubscriber')
      ->addTag('http_client_subscriber');
  }

}
