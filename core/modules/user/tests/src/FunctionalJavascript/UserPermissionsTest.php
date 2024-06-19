<?php

declare(strict_types=1);

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the JS components added to the user permissions page.
 *
 * @group user
 */
class UserPermissionsTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User's role ID.
   *
   * @var string
   */
  protected $rid;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer permissions',
    ]);

    // Find the new role ID.
    $all_rids = $this->adminUser->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    $this->rid = reset($all_rids);
  }

  /**
   * Tests the dummy checkboxes added to the permissions page.
   */
  public function testPermissionCheckboxes(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/permissions');

    $page = $this->getSession()->getPage();
    $wrapper = $page->find('css', '.form-item-' . $this->rid . '-administer-modules');
    $real_checkbox = $wrapper->find('css', '.real-checkbox');
    $dummy_checkbox = $wrapper->find('css', '.dummy-checkbox');

    // The real per-role checkbox is visible and unchecked, the dummy copy is
    // invisible.
    $this->assertTrue($real_checkbox->isVisible());
    $this->assertFalse($real_checkbox->isChecked());
    $this->assertFalse($dummy_checkbox->isVisible());

    // Enable the permission for all authenticated users.
    $page->findField('authenticated[administer modules]')->click();

    // The real and dummy checkboxes switch visibility and the dummy is now both
    // checked and disabled.
    $this->assertFalse($real_checkbox->isVisible());
    $this->assertTrue($dummy_checkbox->isVisible());
    $this->assertTrue($dummy_checkbox->isChecked());
    $this->assertTrue($dummy_checkbox->hasAttribute('disabled'));
  }

}
