<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests workspace integration for custom menu links.
 *
 * @group workspaces
 * @group menu_link_content
 */
class WorkspaceMenuLinkContentIntegrationTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'menu_link_content',
    'menu_ui',
    'node',
    'workspaces',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer menu',
      'administer site configuration',
      'administer workspaces',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalPlaceBlock('system_menu_block:main');
  }

  /**
   * Tests custom menu links in non-default workspaces.
   */
  public function testWorkspacesWithCustomMenuLinks(): void {
    $stage = Workspace::load('stage');

    $this->setupWorkspaceSwitcherBlock();

    $default_title = 'default';
    $default_link = '#live';

    // Add a new menu link in Live.
    $this->drupalGet('admin/structure/menu/manage/main/add');
    $this->submitForm([
      'title[0][value]' => $default_title,
      'link[0][uri]' => $default_link,
    ], 'Save');
    $menu_links = \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->loadByProperties(['title' => $default_title]);
    $menu_link = reset($menu_links);

    $pending_title = 'pending';
    $pending_link = 'http://example.com';

    // Change the menu link in 'stage' and check that the updated values are
    // visible in that workspace.
    $this->switchToWorkspace($stage);
    $this->drupalGet("admin/structure/menu/item/{$menu_link->id()}/edit");
    $this->submitForm([
      'title[0][value]' => $pending_title,
      'link[0][uri]' => $pending_link,
    ], 'Save');

    $this->drupalGet('');
    $assert_session = $this->assertSession();
    $assert_session->linkExists($pending_title);
    $assert_session->linkByHrefExists($pending_link);

    // Add a new menu link in the Stage workspace.
    $this->drupalGet('admin/structure/menu/manage/main/add');
    $this->submitForm([
      'title[0][value]' => 'stage link',
      'link[0][uri]' => '#stage',
    ], 'Save');

    $this->drupalGet('');
    $assert_session->linkExists('stage link');
    $assert_session->linkByHrefExists('#stage');

    // Switch back to the Live workspace and check that the menu link has the
    // default values.
    $this->switchToLive();
    $this->drupalGet('');
    $assert_session->linkExists($default_title);
    $assert_session->linkByHrefExists($default_link);
    $assert_session->linkNotExists($pending_title);
    $assert_session->linkByHrefNotExists($pending_link);
    $assert_session->linkNotExists('stage link');
    $assert_session->linkByHrefNotExists('#stage');

    // Publish the workspace and check that the menu link has been updated.
    $stage->publish();
    $this->drupalGet('');
    $assert_session->linkNotExists($default_title);
    $assert_session->linkByHrefNotExists($default_link);
    $assert_session->linkExists($pending_title);
    $assert_session->linkByHrefExists($pending_link);
    $assert_session->linkExists('stage link');
    $assert_session->linkByHrefExists('#stage');
  }

}
