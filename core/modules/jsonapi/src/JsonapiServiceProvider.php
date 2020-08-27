<?php

namespace Drupal\jsonapi;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\StackMiddleware\NegotiationMiddleware;
use Drupal\jsonapi\DependencyInjection\Compiler\RegisterSerializationClassesCompilerPass;

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
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new RegisterSerializationClassesCompilerPass());
  }

}
