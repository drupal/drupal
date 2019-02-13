<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates from 8.6.0 with warm caches.
 *
 * @group Update
 * @group legacy
 */
class WarmCacheUpdateFrom8dot6Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    // Created by installing minimal on 8.6.0, logging on as user 1 and doing:
    // php ./core/scripts/db-tools.php dump-database-d8-mysql --schema-only=sessions,watchdog
    $this->databaseDumpFiles[0] = __DIR__ . '/../../../../tests/fixtures/update/drupal-8.6.0-minimal-with-warm-caches.sql.gz';
    $this->databaseDumpFiles[1] = __DIR__ . '/../../../../tests/fixtures/update/drupal-8.test-config-init.php';
  }

  /**
   * Tests that the content and configuration were properly updated.
   */
  public function testUpdatedSite() {
    $this->runUpdates();
    $this->drupalGet('');

    $this->resetAll();
    // Ensure that drupal-8.test-config-init.php has run correctly.
    $this->assertSame('test_mail_collector', $this->config('system.mail')->get('interface.default'));
    $this->assertSame('verbose', $this->config('system.logging')->get('error_level'));
    $this->assertSame(FALSE, $this->config('system.performance')->get('css.preprocess'));
    $this->assertSame(FALSE, $this->config('system.performance')->get('js.preprocess'));
    $this->assertSame('Australia/Sydney', $this->config('system.date')->get('timezone.default'));
  }

  /**
   * Tests system_update_8601().
   */
  public function testWithMissingProfile() {
    // Remove the install profile from the module list to simulate how Drush 8
    // and update_fix_compatibility() worked together to remove the install
    // profile. See https://www.drupal.org/project/drupal/issues/3031740.
    $connection = Database::getConnection();
    $config = $connection->select('config')
      ->fields('config', ['data'])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute()
      ->fetchField();
    $config = unserialize($config);
    unset($config['module']['minimal']);
    $connection->update('config')
      ->fields([
        'data' => serialize($config),
        'collection' => '',
        'name' => 'core.extension',
      ])
      ->condition('collection', '')
      ->condition('name', 'core.extension')
      ->execute();

    $this->runUpdates();
    $this->assertSession()->pageTextContains('The minimal install profile has been added to the installed module list.');

    // Login and check that the status report is working correctly.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains("Installation Profile Minimal");
  }

  /**
   * {@inheritdoc}
   */
  protected function initConfig(ContainerInterface $container) {
    // Don't touch configuration before running the updates as this invokes
    // \Drupal\system\EventSubscriber\ConfigCacheTag::onSave() which lists
    // themes. This functionality is replicated in
    // core/modules/system/tests/fixtures/update/drupal-8.test-config-init.php.
  }

}
