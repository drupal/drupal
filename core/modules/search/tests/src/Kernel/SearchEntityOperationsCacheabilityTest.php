<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel;

use Drupal\Tests\system\Kernel\Entity\EntityOperationsCacheabilityTest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests cacheability added by entity operations.
 */
#[Group('search')]
#[RunTestsInSeparateProcesses]
class SearchEntityOperationsCacheabilityTest extends EntityOperationsCacheabilityTest {

  /**
   * Data provider for testEntityOperationsCacheability().
   */
  public static function providerEntityOperationsCacheability(): iterable {
    yield [
      ['user', 'search'],
      'search_page',
      ['plugin' => 'user_search', 'path' => '/test_user_search'],
      ['config:search.page.test'],
      ['user.permissions'],
      FALSE,
      TRUE,
    ];
  }

}
