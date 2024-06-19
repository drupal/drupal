<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Cache;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\PhpSerialize;
use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\DatabaseBackend;
use Drupal\Core\Cache\DatabaseBackendFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Cache\DatabaseBackendFactory
 * @group Cache
 */
class DatabaseBackendFactoryTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::get
   * @dataProvider getProvider
   */
  public function testGet(array $settings, $expected_max_rows_foo, $expected_max_rows_bar): void {
    $database_backend_factory = new DatabaseBackendFactory(
      $this->prophesize(Connection::class)->reveal(),
      $this->prophesize(CacheTagsChecksumInterface::class)->reveal(),
      new Settings($settings),
      new PhpSerialize(),
      $this->prophesize(TimeInterface::class)->reveal(),
    );

    $this->assertSame($expected_max_rows_foo, $database_backend_factory->get('foo')->getMaxRows());
    $this->assertSame($expected_max_rows_bar, $database_backend_factory->get('bar')->getMaxRows());
  }

  public static function getProvider() {
    return [
      'default' => [
        [],
        DatabaseBackend::DEFAULT_MAX_ROWS,
        DatabaseBackend::DEFAULT_MAX_ROWS,
      ],
      'default overridden' => [
        [
          'database_cache_max_rows' => [
            'default' => 99,
          ],
        ],
        99,
        99,
      ],
      'default + foo bin overridden' => [
        [
          'database_cache_max_rows' => [
            'bins' => [
              'foo' => 13,
            ],
          ],
        ],
        13,
        DatabaseBackend::DEFAULT_MAX_ROWS,
      ],
      'default + bar bin overridden' => [
        [
          'database_cache_max_rows' => [
            'bins' => [
              'bar' => 13,
            ],
          ],
        ],
        DatabaseBackend::DEFAULT_MAX_ROWS,
        13,
      ],
      'default overridden + bar bin overridden' => [
        [
          'database_cache_max_rows' => [
            'default' => 99,
            'bins' => [
              'bar' => 13,
            ],
          ],
        ],
        99,
        13,
      ],
      'default + both bins overridden' => [
        [
          'database_cache_max_rows' => [
            'bins' => [
              'foo' => 13,
              'bar' => 31,
            ],
          ],
        ],
        13,
        31,
      ],
      'default overridden + both bins overridden' => [
        [
          'database_cache_max_rows' => [
            'default' => 99,
            'bins' => [
              'foo' => 13,
              'bar' => 31,
            ],
          ],
        ],
        13,
        31,
      ],
    ];
  }

}
