<?php

namespace Drupal\Tests\user\Kernel\Migrate\d7;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\user\AccountSettingsForm;
use Drupal\Core\Database\Database;
use Drupal\user\UserInterface;

/**
 * Tests migration of user settings.
 *
 * @group migrate_drupal_7
 */
class MigrateUserSettingsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations(['d7_user_settings']);
  }

  /**
   * Tests the migration.
   */
  public function testMigration() {
    $config = $this->config('user.settings');
    $this->assertTrue($config->get('notify.status_blocked'));
    $this->assertTrue($config->get('notify.status_activated'));
    $this->assertTrue($config->get('verify_mail'));
    $this->assertSame(UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL, $config->get('register'));
    $this->assertSame('Anonymous', $config->get('anonymous'));

    // Tests migration of user_register using the AccountSettingsForm.
    // Map source values to destination values.
    $user_register_map = [
      [0, UserInterface::REGISTER_ADMINISTRATORS_ONLY],
      [1, UserInterface::REGISTER_VISITORS],
      [2, UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL],
    ];

    foreach ($user_register_map as $map) {
      // Tests migration of user_register = 1.
      Database::getConnection('default', 'migrate')
        ->update('variable')
        ->fields(['value' => serialize($map[0])])
        ->condition('name', 'user_register')
        ->execute();

      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->getMigration('d7_user_settings');
      // Indicate we're rerunning a migration that's already run.
      $migration->getIdMap()->prepareUpdate();
      $this->executeMigration($migration);
      $form = $this->container->get('form_builder')->getForm(AccountSettingsForm::create($this->container));
      $this->assertSame($map[1], $form['registration_cancellation']['user_register']['#value']);
    }
  }

}
