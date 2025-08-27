<?php

declare(strict_types=1);

namespace Drupal\Tests\config\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests existence of config local tasks.
 */
#[Group('config')]
class ConfigLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = ['config' => 'core/modules/config'];
    parent::setUp();
  }

  /**
   * Tests config local tasks existence.
   */
  #[DataProvider('getConfigAdminRoutes')]
  public function testConfigAdminLocalTasks($route, $expected): void {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public static function getConfigAdminRoutes() {
    return [
      ['config.sync', [['config.sync', 'config.import', 'config.export']]],
      ['config.import_full',
        [
          ['config.sync', 'config.import', 'config.export'],
          ['config.import_full', 'config.import_single'],
        ],
      ],
      ['config.import_single',
        [
          ['config.sync', 'config.import', 'config.export'],
          ['config.import_full', 'config.import_single'],
        ],
      ],
      ['config.export_full',
        [
          ['config.sync', 'config.import', 'config.export'],
          ['config.export_full', 'config.export_single'],
        ],
      ],
      ['config.export_single',
        [
          ['config.sync', 'config.import', 'config.export'],
          ['config.export_full', 'config.export_single'],
        ],
      ],
    ];
  }

}
