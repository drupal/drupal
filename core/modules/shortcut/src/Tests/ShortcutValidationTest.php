<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\ShortcutValidationTest.
 */

namespace Drupal\shortcut\Tests;

use Drupal\shortcut\Entity\Shortcut;
use Drupal\system\Tests\Entity\EntityUnitTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests shortcut validation constraints.
 *
 * @group shortcut
 */
class ShortcutValidationTest extends EntityUnitTestBase {

  /**
   * The logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('shortcut', 'user', 'system');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['url_alias', 'router']);

    // Create and login a user.
    $role = Role::create(['id' => 'role_with_access']);
    $role->grantPermission('access administration pages');
    $role->save();
    $this->adminUser = User::create(['roles' => ['role_with_access']]);
    $this->adminUser->save();
    \Drupal::currentUser()->setAccount($this->adminUser);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the shortcut validation constraints.
   */
  public function testValidation() {
    // Add shortcut.
    $shortcut = Shortcut::create(array(
      'shortcut_set' => 'default',
      'title' => t('Add content'),
      'weight' => -20,
      'path' => '<front>',
    ));

    $violations = $shortcut->path->validate();
    $this->assertEqual(count($violations), 0);

    $paths = [
      'does/not/exist' => 1,
      'user/password' => 0,
      'admin' => 0,
      'user/' . $this->adminUser->id() => 0,
      'http://example.com/' => 0,
    ];
    foreach ($paths as $path => $violation_count) {
      $shortcut->set('path', $path);
      $violations = $shortcut->path->validate();

      $this->assertEqual(count($violations), $violation_count);
      if ($violation_count) {
        $this->assertEqual($violations[0]->getMessage(), t('The shortcut must correspond to a valid path on the site.'));
      }
    }
  }

}
