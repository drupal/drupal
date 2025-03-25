<?php

namespace Drupal\Core;

use Drupal\Core\Cache\Context\CacheContextsPass;
use Drupal\Core\Cache\ListCacheBinsPass;
use Drupal\Core\DependencyInjection\Compiler\AuthenticationProviderPass;
use Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass;
use Drupal\Core\DependencyInjection\Compiler\BackwardsCompatibilityClassLoaderPass;
use Drupal\Core\DependencyInjection\Compiler\CorsCompilerPass;
use Drupal\Core\DependencyInjection\Compiler\DeprecatedServicePass;
use Drupal\Core\DependencyInjection\Compiler\DevelopmentSettingsPass;
use Drupal\Core\Hook\HookCollectorPass;
use Drupal\Core\DependencyInjection\Compiler\LoggerAwarePass;
use Drupal\Core\DependencyInjection\Compiler\ModifyServiceDefinitionsPass;
use Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterEventSubscribersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterStreamWrappersPass;
use Drupal\Core\DependencyInjection\Compiler\StackedKernelPass;
use Drupal\Core\DependencyInjection\Compiler\StackedSessionHandlerPass;
use Drupal\Core\DependencyInjection\Compiler\SuperUserAccessPolicyPass;
use Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass;
use Drupal\Core\DependencyInjection\Compiler\TwigExtensionPass;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Plugin\PluginManagerPass;
use Drupal\Core\PreWarm\PreWarmableInterface;
use Drupal\Core\Queue\QueueFactoryInterface;
use Drupal\Core\Render\MainContent\MainContentRenderersPass;
use Drupal\Core\Site\Settings;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
class CoreServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Only register the private file stream wrapper if a file path has been
    // set.
    if (Settings::get('file_private_path')) {
      $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
        ->addTag('stream_wrapper', ['scheme' => 'private']);
    }

    $container->addCompilerPass(new HookCollectorPass());
    // Add the compiler pass that lets service providers modify existing
    // service definitions. This pass must come before all passes operating on
    // services so that later list-building passes are operating on the
    // post-alter services list.
    $container->addCompilerPass(new ModifyServiceDefinitionsPass());

    $container->addCompilerPass(new DevelopmentSettingsPass());

    $container->addCompilerPass(new SuperUserAccessPolicyPass());

    $container->addCompilerPass(new ProxyServicesPass());

    $container->addCompilerPass(new BackendCompilerPass());

    $container->addCompilerPass(new CorsCompilerPass());

    $container->addCompilerPass(new StackedKernelPass());

    $container->addCompilerPass(new StackedSessionHandlerPass());

    $container->addCompilerPass(new MainContentRenderersPass());

    // Collect tagged handler services as method calls on consumer services.
    $container->addCompilerPass(new TaggedHandlersPass());
    $container->addCompilerPass(new RegisterStreamWrappersPass());
    $container->addCompilerPass(new TwigExtensionPass());

    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterEventSubscribersPass(new RegisterListenersPass()), PassConfig::TYPE_AFTER_REMOVING);
    $container->addCompilerPass(new LoggerAwarePass(), PassConfig::TYPE_AFTER_REMOVING);

    $container->addCompilerPass(new RegisterAccessChecksPass());

    // Add a compiler pass for registering services needing destruction.
    $container->addCompilerPass(new RegisterServicesForDestructionPass());

    // Add the compiler pass that will process the tagged services.
    $container->addCompilerPass(new ListCacheBinsPass());
    $container->addCompilerPass(new CacheContextsPass());
    $container->addCompilerPass(new AuthenticationProviderPass());

    // Register plugin managers.
    $container->addCompilerPass(new PluginManagerPass());

    $container->addCompilerPass(new DeprecatedServicePass());

    // Collect moved classes for the backwards compatibility class loader.
    $container->addCompilerPass(new BackwardsCompatibilityClassLoaderPass());

    $container->registerForAutoconfiguration(EventSubscriberInterface::class)
      ->addTag('event_subscriber');

    $container->registerForAutoconfiguration(LoggerAwareInterface::class)
      ->addTag('logger_aware');

    $container->registerForAutoconfiguration(QueueFactoryInterface::class)
      ->addTag('queue_factory');

    $container->registerForAutoconfiguration(PreWarmableInterface::class)
      ->addTag('cache_prewarmable');

    $container->registerForAutoconfiguration(ModuleUninstallValidatorInterface::class)
      ->addTag('module_install.uninstall_validator');

    // Deprecated parameters.
    if ($container->hasParameter('session.storage.options')) {
      $session_storage_options = $container->getParameter('session.storage.options');
      if (array_key_exists('sid_length', $session_storage_options)) {
        @trigger_error('The "sid_length" parameter is deprecated in drupal:11.1.0 and will be removed in drupal:12.0.0. This setting should be removed from the settings file, since its usage has been removed. See https://www.drupal.org/node/3469305', E_USER_DEPRECATED);
      }
      if (array_key_exists('sid_bits_per_character', $session_storage_options)) {
        @trigger_error('The "sid_bits_per_character" parameter is deprecated in drupal:11.1.0 and will be removed in drupal:12.0.0. This setting should be removed from the settings file, since its usage has been removed. See https://www.drupal.org/node/3469305', E_USER_DEPRECATED);
      }
    }
  }

  /**
   * Alters the UUID service to use the most efficient method available.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   */
  public function alter(ContainerBuilder $container) {
    $uuid_service = $container->getDefinition('uuid');
    // Debian/Ubuntu uses the (broken) OSSP extension as their UUID
    // implementation. The OSSP implementation is not compatible with the
    // PECL functions.
    if (function_exists('uuid_create') && !function_exists('uuid_make')) {
      $uuid_service->setClass('Drupal\Component\Uuid\Pecl');
    }
    // Try to use the COM implementation for Windows users.
    elseif (function_exists('com_create_guid')) {
      $uuid_service->setClass('Drupal\Component\Uuid\Com');
    }
  }

}
