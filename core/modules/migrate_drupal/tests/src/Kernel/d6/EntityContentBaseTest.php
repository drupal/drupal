<?php

namespace Drupal\Tests\migrate_drupal\Kernel\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\user\Entity\User;
use Prophecy\Argument;

/**
 * @group migrate_drupal
 */
class EntityContentBaseTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_overwrite_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
      'signature' => [
        [
          'value' => 'Bring a towel.',
          'format' => 'filtered_html',
        ],
      ],
      'init' => 'proto@zo.an',
    ])->save();

    $this->executeMigrations(['d6_filter_format', 'd6_user_role']);
  }

  /**
   * Tests overwriting all mapped properties in the destination entity.
   *
   * This is the default behavior.
   */
  public function testOverwriteAllMappedProperties() {
    $this->executeMigration('d6_user');
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load(2);
    $this->assertSame('john.doe', $account->label());
    $this->assertSame('john.doe@example.com', $account->getEmail());
    $this->assertSame('doe@example.com', $account->getInitialEmail());
  }

  /**
   * Tests overwriting selected properties in the destination entity.
   *
   * The selected properties are specified in the destination configuration.
   */
  public function testOverwriteProperties() {
    // Execute the migration in migrate_overwrite_test, which documents how
    // property overwrites work.
    $this->executeMigration('users');

    /** @var \Drupal\user\UserInterface $account */
    $account = User::load(2);
    $this->assertSame('john.doe', $account->label());
    $this->assertSame('john.doe@example.com', $account->getEmail());
    $this->assertSame('The answer is 42.', $account->signature->value);
    // This value is not overwritten because it's not listed in
    // overwrite_properties.
    $this->assertSame('proto@zo.an', $account->getInitialEmail());
  }

  /**
   * Tests that translation destination fails for untranslatable entities.
   */
  public function testUntranslatable() {
    $this->enableModules(['language_test']);
    $this->installEntitySchema('no_language_entity_test');

    /** @var MigrationInterface $migration */
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([
      'source' => [
        'plugin' => 'embedded_data',
        'ids' => ['id' => ['type' => 'integer']],
        'data_rows' => [['id' => 1]],
      ],
      'process' => [
        'id' => 'id',
      ],
      'destination' => [
        'plugin' => 'entity:no_language_entity_test',
        'translations' => TRUE,
      ],
    ]);

    $message = $this->prophesize(MigrateMessageInterface::class);
    // Match the expected message. Can't use default argument types, because
    // we need to convert to string from TranslatableMarkup.
    $argument = Argument::that(function ($msg) {
      return str_contains((string) $msg, htmlentities('The "no_language_entity_test" entity type does not support translations.'));
    });
    $message->display($argument, Argument::any())
      ->shouldBeCalled();

    $executable = new MigrateExecutable($migration, $message->reveal());
    $executable->import();
  }

}
