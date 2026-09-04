<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that 'created' field is added to user entity view display after update.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class UserCreatedFieldDisplayUpgradeTest extends UpdatePathTestBase {

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
   * Test user_post_update_add_created_to_display() and 'member_for' deprecation.
   */
  public function testMemberForDeprecationAndUserDisplayUpdate(): void {
    $this->checkUserEntityViewDisplayConfig(TRUE);

    $this->runUpdates();

    $this->checkUserEntityViewDisplayConfig(FALSE);

    $this->drupalLogin($this->drupalCreateUser(values: ['roles' => ['administrator']]));
    $this->drupalGet('user/1');
    $this->expectUserDeprecationMessage('The user entity "member_for" display extra field is deprecated in drupal:11.5.0 and removed in drupal:13.0.0. Use the "created" field in displays instead. See https://www.drupal.org/node/3611943');
    $assertSession = $this->assertSession();
    $assertSession->addressEquals('user/1');
    $assertSession->elementTextContains('css', 'h4.label', 'Member for');
  }

  /**
   * Checks the user entity view display configuration for created field.
   *
   * @param bool $beforeUpdate
   *   TRUE when confirming configuration state before running database updates;
   *   FALSE when confirming configuration state after running database updates.
   */
  protected function checkUserEntityViewDisplayConfig(bool $beforeUpdate): void {
    $configFactory = \Drupal::configFactory();
    $displayConfigNames = $configFactory->listAll('core.entity_view_display.user.user.');
    $this->assertCount(2, $displayConfigNames);
    foreach ($displayConfigNames as $configName) {
      $displayConfig = $configFactory->get($configName)->getRawData();
      if (str_ends_with($configName, 'default')) {
        $this->assertArrayHasKey('member_for', $displayConfig['content']);
      }
      else {
        // The member_for field is hidden on 'compact' display.
        $this->assertArrayHasKey('member_for', $displayConfig['hidden']);
      }
      $this->assertArrayNotHasKey('created', $displayConfig['content']);

      // Before the post update hook runs, the default user display will show the
      // 'member_for' extra field, and the 'created' field will not exist in the
      // display config. When the post update hook runs, the 'created' field is
      // added to the display configuration as hidden.
      if ($beforeUpdate) {
        $this->assertArrayNotHasKey('created', $displayConfig['hidden']);
      }
      else {
        $this->assertArrayHasKey('created', $displayConfig['hidden']);
      }
    }
  }

}
