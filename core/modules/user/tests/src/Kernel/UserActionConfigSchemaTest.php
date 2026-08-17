<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\user\Entity\Role;
use Drupal\system\Entity\Action;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the config schema is valid when roles are added or removed.
 */
#[Group('user')]
#[RunTestsInSeparateProcesses]
class UserActionConfigSchemaTest extends KernelTestBase {

  use SchemaCheckTestTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user'];

  /**
   * Tests whether the user action config schema are valid.
   */
  public function testValidUserActionConfigSchema(): void {
    $rid = $this->randomMachineName(8);
    Role::create(['id' => $rid, 'label' => $rid])->save();

    // Create system actions for adding or removing the new user role. These
    // actions are only created automatically when roles are created using the
    // user role form in the admin UI.
    $action = Action::create([
      'id' => 'user_add_role_action.' . $rid,
      'type' => 'user',
      'label' => $this->t('Add the @label role to the selected users', ['@label' => $rid]),
      'configuration' => [
        'rid' => $rid,
      ],
      'plugin' => 'user_add_role_action',
    ]);
    $action->save();
    $action = Action::create([
      'id' => 'user_remove_role_action.' . $rid,
      'type' => 'user',
      'label' => $this->t('Remove the @label role from the selected users', ['@label' => $rid]),
      'configuration' => [
        'rid' => $rid,
      ],
      'plugin' => 'user_remove_role_action',
    ]);
    $action->save();

    // Test user_add_role_action configuration.
    $config = $this->config('system.action.user_add_role_action.' . $rid);
    $this->assertEquals('user_add_role_action.' . $rid, $config->get('id'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());

    // Test user_remove_role_action configuration.
    $config = $this->config('system.action.user_remove_role_action.' . $rid);
    $this->assertEquals('user_remove_role_action.' . $rid, $config->get('id'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}
