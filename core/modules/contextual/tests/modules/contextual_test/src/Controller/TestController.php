<?php

declare(strict_types=1);

namespace Drupal\contextual_test\Controller;

use Drupal\contextual\ContextualLinksSerializer;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Test controller to provide a callback for the contextual link.
 */
class TestController implements ContainerInjectionInterface {

  use AutowireTrait;

  public function __construct(
    protected readonly ContextualLinksSerializer $serializer,
  ) {}

  /**
   * Callback for the contextual link.
   *
   * @return array
   *   Render array.
   */
  public function render() {
    return [
      '#type' => 'markup',
      '#markup' => 'Everything is contextual!',
    ];
  }

  /**
   * Renders two regions with the same contextual links.
   */
  public function duplicateContextualLinks(): array {
    $contextual_id = $this->serializer->linksToId([
      'contextual_test' => ['route_parameters' => []],
    ]);
    $build = [];
    foreach (['first', 'second'] as $id) {
      $build[$id] = [
        '#type' => 'inline_template',
        '#template' => '<div id="region-{{ id }}" class="contextual-region">{{ placeholder }}Region {{ id }}</div>',
        '#context' => [
          'id' => $id,
          'placeholder' => [
            '#type' => 'contextual_links_placeholder',
            '#id' => $contextual_id,
          ],
        ],
      ];
    }
    return $build;
  }

}
