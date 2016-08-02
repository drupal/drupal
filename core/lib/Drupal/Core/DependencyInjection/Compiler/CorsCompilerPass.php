<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides a compiler pass which disables the CORS middleware in case disabled.
 *
 * @see core.services.yml
 */
class CorsCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $enabled = FALSE;

    if ($cors_config = $container->getParameter('cors.config')) {
      $enabled = !empty($cors_config['enabled']);
    }

    // Remove the CORS middleware completly in case it was not enabled.
    if (!$enabled) {
      $container->removeDefinition('http_middleware.cors');
    }
  }

}
