<?php

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the block administration page for a non-default theme.
 *
 * @group block
 */
class NonDefaultBlockAdminTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('block');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test non-default theme admin.
   */
  function testNonDefaultBlockAdmin() {
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer themes'));
    $this->drupalLogin($admin_user);
    $new_theme = 'bartik';
    \Drupal::service('theme_handler')->install(array($new_theme));
    $this->drupalGet('admin/structure/block/list/' . $new_theme);
    $this->assertText('Bartik(' . t('active tab') . ')', 'Tab for non-default theme found.');
  }
}
