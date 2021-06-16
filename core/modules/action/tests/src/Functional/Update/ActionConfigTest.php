<?php

namespace Drupal\Tests\action\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests removing action module's configuration.
 *
 * @group Update
 */
class ActionConfigTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.action-3022401.php',
    ];
  }

  /**
   * Tests upgrading action settings.
   *
   * @see \action_post_update_remove_settings()
   */
  public function testUpdateActionPlugins() {
    $config = $this->config('action.settings');
    $this->assertSame(35, $config->get('recursion_limit'));

    // Run updates.
    $this->runUpdates();

    $config = $this->config('action.settings');
    $this->assertTrue($config->isNew());
  }

}
