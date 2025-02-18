<?php

declare(strict_types=1);

namespace Drupal\migrate_sql_count_cache_test\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for Sql count cache test.
 */
#[MigrateSource('sql_count_cache')]
class SqlCountCache extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('source_table', 's')->fields('s', ['id']);
  }

}
