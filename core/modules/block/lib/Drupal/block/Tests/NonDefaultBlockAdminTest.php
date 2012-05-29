<?php

/**
 * @file
 * Definition of Drupal\block\Tests\NonDefaultBlockAdminTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

class NonDefaultBlockAdminTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Non default theme admin',
      'description' => 'Check the administer page for non default theme.',
      'group' => 'Block',
    );
  }

  function setUp() {
    parent::setUp(array('block'));
  }

  /**
   * Test non-default theme admin.
   */
  function testNonDefaultBlockAdmin() {
    $admin_user = $this->drupalCreateUser(array('administer blocks', 'administer themes'));
    $this->drupalLogin($admin_user);
    $new_theme = 'bartik';
    theme_enable(array($new_theme));
    $this->drupalGet('admin/structure/block/list/' . $new_theme);
  }
}
