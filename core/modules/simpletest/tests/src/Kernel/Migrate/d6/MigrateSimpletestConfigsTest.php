<?php

namespace Drupal\Tests\simpletest\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to simpletest.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSimpletestConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['simpletest'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['simpletest']);
    $this->executeMigration('d6_simpletest_settings');
  }

  /**
   * Tests migration of simpletest variables to simpletest.settings.yml.
   */
  public function testSimpletestSettings() {
    $config = $this->config('simpletest.settings');
    $this->assertIdentical(TRUE, $config->get('clear_results'));
    $this->assertIdentical(CURLAUTH_BASIC, $config->get('httpauth.method'));
    // NULL in the dump means defaults which is empty string. Same as omitting
    // them.
    $this->assertIdentical('', $config->get('httpauth.password'));
    $this->assertIdentical('', $config->get('httpauth.username'));
    $this->assertIdentical(TRUE, $config->get('verbose'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'simpletest.settings', $config->get());
  }

}
