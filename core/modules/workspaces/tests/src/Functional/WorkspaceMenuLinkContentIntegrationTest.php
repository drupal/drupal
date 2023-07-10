<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
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
      'administer site configuration',
      'administer workspaces',
    ];
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalPlaceBlock('system_menu_block:main');
  }

  /**
   * Tests custom menu links in non-default workspaces.
   */
  public function testWorkspacesWithCustomMenuLinks() {
    $stage = Workspace::load('stage');

    $this->setupWorkspaceSwitcherBlock();

    $default_title = 'default';
    $default_link = '#live';
    $menu_link_content = MenuLinkContent::create([
      'title' => $default_title,
      'menu_name' => 'main',
      'link' => [['uri' => 'internal:/' . $default_link]],
    ]);
    $menu_link_content->save();

    $pending_title = 'pending';
    $pending_link = 'http://example.com';
    $this->switchToWorkspace($stage);
    $menu_link_content->set('title', $pending_title);
    $menu_link_content->set('link', [['uri' => $pending_link]]);
    $menu_link_content->save();

    $this->drupalGet('');
    $assert_session = $this->assertSession();
    $assert_session->linkExists($pending_title);
    $assert_session->linkByHrefExists($pending_link);

    // Add a new menu link in the Stage workspace.
    $menu_link_content = MenuLinkContent::create([
      'title' => 'stage link',
      'menu_name' => 'main',
      'link' => [['uri' => 'internal:/#stage']],
    ]);
    $menu_link_content->save();

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
