<?php

/**
 * @file
 * Contains \Drupal\contact\Tests\Migrate\d6\MigrateContactConfigsTest.
 */

namespace Drupal\contact\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to contact.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateContactConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('contact');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some id mappings for the dependent migrations.
    $id_mappings = array(
      'd6_contact_category' => array(
        array(array(1), array('website_feedback')),
        array(array(2), array('some_other_category')),
      ),
    );
    $this->prepareMigrations($id_mappings);
    $this->executeMigration('d6_contact_settings');
  }

  /**
   * Tests migration of contact variables to contact.settings.yml.
   */
  public function testContactSettings() {
    $config = $this->config('contact.settings');
    $this->assertIdentical(true, $config->get('user_default_enabled'));
    $this->assertIdentical(3, $config->get('flood.limit'));
    $this->assertIdentical('some_other_category', $config->get('default_form'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'contact.settings', $config->get());
  }

}
