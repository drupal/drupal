<?php

namespace Drupal\jsonapi;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\StackMiddleware\NegotiationMiddleware;
use Drupal\jsonapi\DependencyInjection\Compiler\RegisterSerializationClassesCompilerPass;
use Drupal\jsonapi\EventSubscriber\ResourceResponseValidator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds 'api_json' as known format and prevents its use in the REST module.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class JsonapiServiceProvider implements ServiceModifierInterface, ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('http_middleware.negotiation') && is_a($container->getDefinition('http_middleware.negotiation')->getClass(), NegotiationMiddleware::class, TRUE)) {
      // @see http://www.iana.org/assignments/media-types/application/vnd.api+json
      $container->getDefinition('http_middleware.negotiation')
        ->addMethodCall('registerFormat', [
          'api_json',
          ['application/vnd.api+json'],
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // Avoid registering the validator in production.
    \assert($this->registerResourceResponseValidator($container));
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass());
  }

  /**
   * Registers the resource response validator in the container.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The DI container.
   *
   * @return bool
   *   The state of registering the validator.
   */
  protected function registerResourceResponseValidator(ContainerBuilder $container): bool {
    $definition = new Definition(ResourceResponseValidator::class);
    $definition->addTag('event_subscriber');
    $definition->addMethodCall('setValidator', []);
    $definition->setArguments([
      new Reference('logger.channel.jsonapi'),
      new Reference('module_handler'),
      new Reference('app.root'),
    ]);

    $container->setDefinition('jsonapi.resource_response_validator.subscriber', $definition);

    return TRUE;
  }

}
