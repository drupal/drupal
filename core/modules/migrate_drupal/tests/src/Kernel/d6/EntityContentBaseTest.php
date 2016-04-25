<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\Entity\User;

/**
 * @group migrate_drupal
 */
class EntityContentBaseTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_overwrite_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a field on the user entity so that we can test nested property
    // overwrites.
    // @see static::testOverwriteSelectedNestedProperty()
    FieldStorageConfig::create([
      'field_name' => 'signature',
      'entity_type' => 'user',
      'type' => 'text_long',
    ])->save();

    FieldConfig::create([
      'field_name' => 'signature',
      'entity_type' => 'user',
      'bundle' => 'user',
    ])->save();

    User::create([
      'uid' => 2,
      'name' => 'Ford Prefect',
      'mail' => 'ford.prefect@localhost',
      'signature' => array(
        array(
          'value' => 'Bring a towel.',
          'format' => 'filtered_html',
        ),
      ),
      'init' => 'proto@zo.an',
    ])->save();

    $this->executeMigrations(['d6_filter_format', 'd6_user_role']);
  }

  /**
   * Tests overwriting all mapped properties in the destination entity (default
   * behavior).
   */
  public function testOverwriteAllMappedProperties() {
    $this->executeMigration('d6_user');
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load(2);
    $this->assertIdentical('john.doe', $account->label());
    $this->assertIdentical('john.doe@example.com', $account->getEmail());
    $this->assertIdentical('doe@example.com', $account->getInitialEmail());
  }

  /**
   * Tests overwriting selected properties in the destination entity, specified
   * in the destination configuration.
   */
  public function testOverwriteProperties() {
    // Execute the migration in migrate_overwrite_test, which documents how
    // property overwrites work.
    $this->executeMigration('users');

    /** @var \Drupal\user\UserInterface $account */
    $account = User::load(2);
    $this->assertIdentical('john.doe', $account->label());
    $this->assertIdentical('john.doe@example.com', $account->getEmail());
    $this->assertIdentical('The answer is 42.', $account->signature->value);
    // This value is not overwritten because it's not listed in
    // overwrite_properties.
    $this->assertIdentical('proto@zo.an', $account->getInitialEmail());
  }

}
