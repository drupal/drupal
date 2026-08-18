<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests user_post_update_add_role_description().
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class UserRoleDescriptionUpgradeTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests user_post_update_add_role_description().
   *
   * @see user_post_update_add_role_description()
   */
  public function testRoleDescription(): void {
    foreach (\Drupal::configFactory()->listAll('user.role.') as $config_name) {
      $this->assertArrayNotHasKey('description', $this->config($config_name)->getRawData());
    }

    $this->runUpdates();

    foreach (\Drupal::configFactory()->listAll('user.role.') as $config_name) {
      $this->assertSame('', $this->config($config_name)->get('description'));
    }
  }

}
