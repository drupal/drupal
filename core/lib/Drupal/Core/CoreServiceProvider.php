<?php

namespace Drupal\Core;

use Drupal\Core\Cache\Context\CacheContextsPass;
use Drupal\Core\Cache\ListCacheBinsPass;
use Drupal\Core\DependencyInjection\Compiler\AuthenticationProviderPass;
use Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass;
use Drupal\Core\DependencyInjection\Compiler\CorsCompilerPass;
use Drupal\Core\DependencyInjection\Compiler\GuzzleMiddlewarePass;
use Drupal\Core\DependencyInjection\Compiler\ContextProvidersPass;
use Drupal\Core\DependencyInjection\Compiler\ProxyServicesPass;
use Drupal\Core\DependencyInjection\Compiler\DependencySerializationTraitPass;
use Drupal\Core\DependencyInjection\Compiler\StackedKernelPass;
use Drupal\Core\DependencyInjection\Compiler\StackedSessionHandlerPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterStreamWrappersPass;
use Drupal\Core\DependencyInjection\Compiler\TwigExtensionPass;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\Compiler\ModifyServiceDefinitionsPass;
use Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterEventSubscribersPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterAccessChecksPass;
use Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass;
use Drupal\Core\EventSubscriber\PathSubscriber;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\AliasRepository;
use Drupal\Core\Path\AliasWhitelist;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\Plugin\PluginManagerPass;
use Drupal\Core\Render\MainContent\MainContentRenderersPass;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\Reference;

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
    $this->registerTest($container);

    // Only register the private file stream wrapper if a file path has been set.
    if (Settings::get('file_private_path')) {
      $container->register('stream_wrapper.private', 'Drupal\Core\StreamWrapper\PrivateStream')
        ->addTag('stream_wrapper', ['scheme' => 'private']);
    }

    // Add the compiler pass that lets service providers modify existing
    // service definitions. This pass must come first so that later
    // list-building passes are operating on the post-alter services list.
    $container->addCompilerPass(new ModifyServiceDefinitionsPass());

    $container->addCompilerPass(new ProxyServicesPass());

    $container->addCompilerPass(new BackendCompilerPass());

    $container->addCompilerPass(new CorsCompilerPass());

    $container->addCompilerPass(new StackedKernelPass());

    $container->addCompilerPass(new StackedSessionHandlerPass());

    $container->addCompilerPass(new MainContentRenderersPass());

    // Collect tagged handler services as method calls on consumer services.
    $container->addCompilerPass(new TaggedHandlersPass());
    $container->addCompilerPass(new RegisterStreamWrappersPass());
    $container->addCompilerPass(new GuzzleMiddlewarePass());

    $container->addCompilerPass(new TwigExtensionPass());

    // Add a compiler pass for registering event subscribers.
    $container->addCompilerPass(new RegisterEventSubscribersPass(), PassConfig::TYPE_AFTER_REMOVING);

    $container->addCompilerPass(new RegisterAccessChecksPass());

    // Add a compiler pass for registering services needing destruction.
    $container->addCompilerPass(new RegisterServicesForDestructionPass());

    // Add the compiler pass that will process the tagged services.
    $container->addCompilerPass(new ListCacheBinsPass());
    $container->addCompilerPass(new CacheContextsPass());
    $container->addCompilerPass(new ContextProvidersPass());
    $container->addCompilerPass(new AuthenticationProviderPass());

    // Register plugin managers.
    $container->addCompilerPass(new PluginManagerPass());

    $container->addCompilerPass(new DependencySerializationTraitPass());
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

    // Look for missing services that are now defined by the path_alias module,
    // add them as a fallback until the module is installed.
    // @todo Remove this in Drupal 9 in https://www.drupal.org/node/3092090.
    $services = [
      'path_alias.subscriber' => PathSubscriber::class,
      'path_alias.path_processor' => PathProcessorAlias::class,
      'path_alias.manager' => AliasManager::class,
      'path_alias.whitelist' => AliasWhitelist::class,
      'path_alias.repository' => AliasRepository::class,
    ];
    foreach ($services as $id => $class) {
      if (!$container->hasDefinition($id)) {
        $definition = $container->register($id, $class);
        // Mark the fallback services as deprecated in order to allow other
        // modules to provide additional checks before relying or altering them.
        $definition->setDeprecated(TRUE, 'The "%service_id%" service is in fallback mode. See https://drupal.org/node/3092086');
        switch ($id) {
          case 'path_alias.subscriber':
            $definition->addArgument(new Reference('path.alias_manager'));
            $definition->addArgument(new Reference('path.current'));
            break;

          case 'path_alias.path_processor':
            $definition->addArgument(new Reference('path.alias_manager'));
            break;

          case 'path_alias.repository':
            $definition->addArgument(new Reference('database'));
            break;

          case 'path_alias.whitelist':
            $definition->addArgument('path_alias_whitelist');
            $definition->addArgument(new Reference('cache.bootstrap'));
            $definition->addArgument(new Reference('lock'));
            $definition->addArgument(new Reference('state'));
            $definition->addArgument(new Reference('path_alias.repository'));
            break;

          case 'path_alias.manager':
            $definition->addArgument(new Reference('path_alias.repository'));
            $definition->addArgument(new Reference('path_alias.whitelist'));
            $definition->addArgument(new Reference('language_manager'));
            $definition->addArgument(new Reference('cache.data'));
            break;
        }
      }
    }
  }

  /**
   * Registers services and event subscribers for a site under test.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The container builder.
   */
  protected function registerTest(ContainerBuilder $container) {
    // Do nothing if we are not in a test environment.
    if (!drupal_valid_test_ua()) {
      return;
    }
    // Add the HTTP request middleware to Guzzle.
    $container
      ->register('test.http_client.middleware', 'Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware')
      ->addTag('http_client_middleware');
  }

}
