<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node rebuild permission update.
 */
#[Group('node')]
#[CoversFunction('node_post_update_add_rebuild_permission_to_roles')]
#[RunTestsInSeparateProcesses]
class NodeRebuildPermissionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests an update path for 'rebuild node access permissions' permission.
   */
  public function testRunUpdates(): void {
    // Grant auth user with 'administer nodes' permission. And check
    // if the new permission is added after the post_update hook is executed.
    $this->grantPermissions(
      Role::load(Role::AUTHENTICATED_ID),
      ['administer nodes']
    );
    $this->runUpdates();
    $this->assertTrue(Role::load(Role::AUTHENTICATED_ID)->hasPermission('rebuild node access permissions'));
  }

}
