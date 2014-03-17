<?php

/**
 * @file
 * Definition of Drupal\Core\CoreServiceProvider.
 */

namespace Drupal\Core;

use Drupal\Core\Cache\ListCacheBinsPass;
use Drupal\Core\Config\ConfigFactoryOverridePass;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\Compiler\ModifyServiceDefinitionsPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterKernelListenersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterPathProcessorsPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterRouteProcessorsPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterRouteFiltersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterRouteEnhancersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterParamConvertersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterStringTranslatorsPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterBreadcrumbBuilderPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAuthenticationPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterTwigExtensionsPass;
use Drupal\Core\Plugin\PluginManagerPass;
use Drupal\Core\Theme\ThemeNegotiatorPass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Scope;
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
 */
class CoreServiceProvider implements ServiceProviderInterface  {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // The 'request' scope and service enable services to depend on the Request
    // object and get reconstructed when the request object changes (e.g.,
    // during a subrequest).
    $container->addScope(new Scope('request'));
    $this->registerTwig($container);
    $this->registerUuid($container);

    // Add the compiler pass that lets service providers modify existing
    // service definitions. This pass must come first so that later
    // list-building passes are operating on the post-alter services list.
    $container->addCompilerPass(new ModifyServiceDefinitionsPass());
    $container->addCompilerPass(new RegisterRouteFiltersPass());
    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterKernelListenersPass(), PassConfig::TYPE_AFTER_REMOVING);
    $container->addCompilerPass(new RegisterAccessChecksPass());
    // Add a compiler pass for upcasting route parameters.
    $container->addCompilerPass(new RegisterParamConvertersPass());
    $container->addCompilerPass(new RegisterRouteEnhancersPass());
    // Add a compiler pass for registering services needing destruction.
    $container->addCompilerPass(new RegisterServicesForDestructionPass());
    // Add the compiler pass that will process the tagged services.
    $container->addCompilerPass(new RegisterPathProcessorsPass());
    $container->addCompilerPass(new RegisterRouteProcessorsPass());
    $container->addCompilerPass(new ListCacheBinsPass());
    // Add the compiler pass for appending string translators.
    $container->addCompilerPass(new RegisterStringTranslatorsPass());
    // Add the compiler pass that will process the tagged breadcrumb builder
    // services.
    $container->addCompilerPass(new RegisterBreadcrumbBuilderPass());
    // Add the compiler pass that will process the tagged theme negotiator
    // service.
    $container->addCompilerPass(new ThemeNegotiatorPass());
    // Add the compiler pass that will process the tagged config factory
    // override services.
    $container->addCompilerPass(new ConfigFactoryOverridePass());
    // Add the compiler pass that will process tagged authentication services.
    $container->addCompilerPass(new RegisterAuthenticationPass());
    // Register Twig extensions.
    $container->addCompilerPass(new RegisterTwigExtensionsPass());
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

    $container->register('twig', 'Drupal\Core\Template\TwigEnvironment')
      ->addArgument(new Reference('twig.loader'))
      ->addArgument(array(
        // This is saved / loaded via drupal_php_storage().
        // All files can be refreshed by clearing caches.
        // @todo ensure garbage collection of expired files.
        // When in the installer, twig_cache must be FALSE until we know the
        // files folder is writable.
        'cache' => drupal_installation_attempted() ? FALSE : settings()->get('twig_cache', TRUE),
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1712444.
        'autoescape' => FALSE,
        // @todo Remove in followup issue
        // @see http://drupal.org/node/1806538.
        'strict_variables' => FALSE,
        'debug' => settings()->get('twig_debug', FALSE),
        'auto_reload' => settings()->get('twig_auto_reload', NULL),
      ))
      ->addArgument(new Reference('module_handler'))
      ->addArgument(new Reference('theme_handler'))
      ->addMethodCall('addExtension', array(new Definition('Drupal\Core\Template\TwigExtension')))
      // @todo Figure out what to do about debugging functions.
      // @see http://drupal.org/node/1804998
      ->addMethodCall('addExtension', array(new Definition('Twig_Extension_Debug')));
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

}
