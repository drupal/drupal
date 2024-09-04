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
   * Tests the fake checkboxes added to the permissions page.
   */
  public function testPermissionCheckboxes(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/people/permissions');

    $page = $this->getSession()->getPage();
    $wrapper = $page->find('css', '.form-item-' . $this->rid . '-administer-modules');
    $fake_checkbox = $wrapper->find('css', '.fake-checkbox');

    // The real per-role checkbox is visible and unchecked, the fake copy does
    // not exist yet.
    $this->assertNull($fake_checkbox);

    // Enable the permission for all authenticated users.
    $page->findField('authenticated[administer modules]')->click();

    // The checkboxes have been initialized.
    $real_checkbox = $wrapper->find('css', '.real-checkbox');
    $fake_checkbox = $wrapper->find('css', '.fake-checkbox');

    // The real and fake checkboxes switch visibility and the fake is now both
    // checked and disabled.
    $this->assertFalse($real_checkbox->isVisible());
    $this->assertTrue($fake_checkbox->isVisible());
    $this->assertTrue($fake_checkbox->isChecked());
    $this->assertTrue($fake_checkbox->hasAttribute('disabled'));
  }

}
