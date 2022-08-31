<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Test contextual filters with 'allow multiple values' setting for user roles.
 *
 * @group views
 */
class ContextualFiltersStringTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views_ui',
    'views_test_config',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_user_role_contextual_filter'];

  /**
   * Role id of role 1.
   *
   * @var string
   */
  public $role1;

  /**
   * Role id of role 2.
   *
   * @var string
   */
  public $role2;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    // Create Roles and users.
    $this->role1 = $this->drupalCreateRole(['access content'], 'editor', 'Editor');
    $this->role2 = $this->drupalCreateRole(['access content'], 'publisher', 'Publisher');

    $this->createUser([], 'user1', FALSE, ['roles' => [$this->role1]]);
    $this->createUser([], 'user2', FALSE, ['roles' => [$this->role2]]);
    $this->createUser([], 'user3', FALSE, ['roles' => [$this->role1, $this->role2]]);
    $this->createUser([], 'user4', FALSE, ['roles' => [$this->role2]]);
    $this->createUser([], 'user5', FALSE, ['roles' => [$this->role1, $this->role2]]);

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests contextual filter for the user roles.
   */
  public function testUserRoleContextualFilter() {
    $this->drupalGet('admin/structure/views/view/test_user_role_contextual_filter');
    $edit = [
      'view_args' => $this->role1,
    ];
    $this->submitForm($edit, 'Update preview');
    $this->assertSession()->pageTextContains('user1');
    $this->assertSession()->pageTextContains('user3');
    $this->assertSession()->pageTextContains('user5');
    $this->assertSession()->pageTextNotContains('user2');
    $this->assertSession()->pageTextNotContains('user4');

    $edit = [
      'view_args' => $this->role2,
    ];
    $this->submitForm($edit, 'Update preview');
    $this->assertSession()->pageTextContains('user2');
    $this->assertSession()->pageTextContains('user3');
    $this->assertSession()->pageTextContains('user4');
    $this->assertSession()->pageTextContains('user5');
    $this->assertSession()->pageTextNotContains('user1');

    $edit = [
      'view_args' => "$this->role1,$this->role2",
    ];
    $this->submitForm($edit, 'Update preview');
    $this->assertSession()->pageTextContains('user3');
    $this->assertSession()->pageTextContains('user5');
    $this->assertSession()->pageTextNotContains('user1');
    $this->assertSession()->pageTextNotContains('user2');
    $this->assertSession()->pageTextNotContains('user4');

    $edit = [
      'view_args' => "$this->role1+$this->role2",
    ];
    $this->submitForm($edit, 'Update preview');
    $this->assertSession()->pageTextContains('user1');
    $this->assertSession()->pageTextContains('user2');
    $this->assertSession()->pageTextContains('user3');
    $this->assertSession()->pageTextContains('user4');
    $this->assertSession()->pageTextContains('user5');
  }

}
