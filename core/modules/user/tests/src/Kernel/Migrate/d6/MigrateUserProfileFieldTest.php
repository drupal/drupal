<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the user profile field migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileFieldTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('user_profile_field');
  }

  /**
   * Tests migration of user profile fields.
   */
  public function testUserProfileFields(): void {
    // Migrated a text field.
    $field_storage = FieldStorageConfig::load('user.profile_color');
    $this->assertSame('text', $field_storage->getType(), 'Field type is text.');
    $this->assertSame(1, $field_storage->getCardinality(), 'Text field has correct cardinality');

    // Migrated a textarea.
    $field_storage = FieldStorageConfig::load('user.profile_biography');
    $this->assertSame('text_long', $field_storage->getType(), 'Field type is text_long.');

    // Migrated checkbox field.
    $field_storage = FieldStorageConfig::load('user.profile_sell_address');
    $this->assertSame('boolean', $field_storage->getType(), 'Field type is boolean.');

    // Migrated selection field.
    $field_storage = FieldStorageConfig::load('user.profile_sold_to');
    $this->assertSame('list_string', $field_storage->getType(), 'Field type is list_string.');
    $settings = $field_storage->getSettings();
    $this->assertEquals(['Pill spammers' => 'Pill spammers', 'Fitness spammers' => 'Fitness spammers', 'Back\\slash' => 'Back\\slash', 'Forward/slash' => 'Forward/slash', 'Dot.in.the.middle' => 'Dot.in.the.middle', 'Faithful servant' => 'Faithful servant', 'Anonymous donor' => 'Anonymous donor'], $settings['allowed_values']);
    $this->assertSame('list_string', $field_storage->getType(), 'Field type is list_string.');

    // Migrated list field.
    $field_storage = FieldStorageConfig::load('user.profile_bands');
    $this->assertSame('text', $field_storage->getType(), 'Field type is text.');
    $this->assertSame(-1, $field_storage->getCardinality(), 'List field has correct cardinality');

    // Migrated URL field.
    $field_storage = FieldStorageConfig::load('user.profile_blog');
    $this->assertSame('link', $field_storage->getType(), 'Field type is link.');

    // Migrated date field.
    $field_storage = FieldStorageConfig::load('user.profile_birthdate');
    $this->assertSame('datetime', $field_storage->getType(), 'Field type is datetime.');
    $this->assertSame('date', $field_storage->getSettings()['datetime_type']);
  }

}
