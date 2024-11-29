<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\Theme\Icon\IconDefinition;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;

/**
 * Provides methods to generate icons for tests.
 */
trait IconTestTrait {

  /**
   * Create an icon.
   *
   * @param array $data
   *   The icon data to create.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface
   *   The icon mocked.
   */
  protected function createTestIcon(array $data = []): IconDefinitionInterface {
    $filtered_data = $data;
    $keys = ['pack_id', 'icon_id', 'template', 'source', 'group'];
    foreach ($keys as $key) {
      unset($filtered_data[$key]);
    }
    return IconDefinition::create(
      $data['pack_id'] ?? 'foo',
      $data['icon_id'] ?? 'bar',
      $data['template'] ?? 'baz',
      $data['source'] ?? NULL,
      $data['group'] ?? NULL,
      $filtered_data,
    );
  }

}
