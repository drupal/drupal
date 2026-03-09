<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests output on the status overview page.
 */
#[Group('shortcut')]
#[RunTestsInSeparateProcesses]
class TrustedHostsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'shortcut'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that shortcut module works together with host verification.
   */
  public function testShortcut(): void {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $shortcut_storage = $entity_type_manager->getStorage('shortcut');

    $shortcut = $shortcut_storage->create([
      'title' => 'Test Shortcut Label',
      'link' => 'internal:/admin/reports/status',
      'shortcut_set' => 'default',
    ]);
    $shortcut_storage->save($shortcut);

    // Grant the current user access to see the shortcuts.
    $role_storage = $entity_type_manager->getStorage('user_role');
    $roles = $this->loggedInUser->getRoles(TRUE);
    /** @var \Drupal\user\RoleInterface $role */
    $role = $role_storage->load(reset($roles));
    $role->grantPermission('access shortcuts')->save();

    $this->drupalPlaceBlock('shortcuts');

    $this->drupalGet('');
    $this->assertSession()->linkExists($shortcut->label());
  }

}
