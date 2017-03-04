<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests user_install().
 *
 * @group user
 */
class UserInstallTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->container->get('module_handler')->loadInclude('user', 'install');
    $this->installEntitySchema('user');
    user_install();
  }


  /**
   * Test that the initial users have correct values.
   */
  public function testUserInstall() {
    $result = db_query('SELECT u.uid, u.uuid, u.langcode, uf.status FROM {users} u INNER JOIN {users_field_data} uf ON u.uid=uf.uid ORDER BY u.uid')
      ->fetchAllAssoc('uid');
    $anon = $result[0];
    $admin = $result[1];
    $this->assertFalse(empty($anon->uuid), 'Anon user has a UUID');
    $this->assertFalse(empty($admin->uuid), 'Admin user has a UUID');

    // Test that the anonymous and administrators languages are equal to the
    // site's default language.
    $this->assertEqual($anon->langcode, \Drupal::languageManager()->getDefaultLanguage()->getId());
    $this->assertEqual($admin->langcode, \Drupal::languageManager()->getDefaultLanguage()->getId());

    // Test that the administrator is active.
    $this->assertEqual($admin->status, 1);
    // Test that the anonymous user is blocked.
    $this->assertEqual($anon->status, 0);
  }

}
