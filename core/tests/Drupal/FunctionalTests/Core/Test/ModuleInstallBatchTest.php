<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Test;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests batch operations during tests execution.
 *
 * This demonstrates that a batch will be successfully executed during module
 * installation when running tests.
 */
#[Group('Test')]
#[Group('FunctionalTestSetupTrait')]
#[RunTestsInSeparateProcesses]
class ModuleInstallBatchTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['test_batch_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests loading entities created in a batch in test_batch_test_install().
   */
  public function testLoadingEntitiesCreatedInBatch(): void {
    foreach ([1, 2] as $id) {
      $this->assertNotNull(EntityTest::load($id), 'Successfully loaded entity ' . $id);
    }
  }

}
