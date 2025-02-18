<?php

declare(strict_types=1);

namespace Drupal\migrate_plugin_config_test\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Simple source for testing changing configuration.
 */
#[MigrateSource('simple_source')]
class SimpleSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    return $this->select('source_table', 's')->fields('s', ['id']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    return [
      'foo' => $this->t('Test field.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return ['foo' => ['type' => 'string']];
  }

}
