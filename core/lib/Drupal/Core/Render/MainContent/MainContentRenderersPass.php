<?php

namespace Drupal\Core\Render\MainContent;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds main_content_renderers parameter to the container.
 */
class MainContentRenderersPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   *
   * Collects the available main content renderer service IDs into the
   * main_content_renderers parameter, keyed by format.
   */
  public function process(ContainerBuilder $container) {
    $main_content_renderers = [];
    foreach ($container->findTaggedServiceIds('render.main_content_renderer') as $id => $attributes_list) {
      foreach ($attributes_list as $attributes) {
        $format = $attributes['format'];
        $main_content_renderers[$format] = $id;
      }
    }
    $container->setParameter('main_content_renderers', $main_content_renderers);
  }

}
