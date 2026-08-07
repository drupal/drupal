<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\FunctionalJavascript\workspaces;

use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workspaces\Functional\WorkspaceTestUtilities;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the workspace entity.
 */
#[Group('toolbar')]
#[RunTestsInSeparateProcesses]
class WorkspaceTest extends BrowserTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dynamic_page_cache',
    'node',
    'toolbar',
    'user',
    'workspaces',
    'workspaces_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor1;

  /**
   * A test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor2;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $permissions = [
      'access administration pages',
      'administer site configuration',
      'create workspace',
      'edit own workspace',
      'edit any workspace',
      'view any workspace',
      'view own workspace',
      'access toolbar',
    ];

    $this->editor1 = $this->drupalCreateUser($permissions);
    $this->editor2 = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests creating a workspace with special characters.
   */
  public function testSpecialCharacters(): void {
    $this->drupalLogin($this->editor1);
    $page = $this->getSession()->getPage();

    // Test a valid workspace name.
    $this->createAndActivateWorkspaceThroughUi('Workspace 1', 'workspace_1');
    $this->assertSession()->elementTextContains('css', '.workspaces-toolbar-tab', 'Workspace 1');

    // Test and invalid workspace name.
    $this->drupalGet('/admin/config/workflow/workspaces/add');
    $this->assertSession()->statusCodeEquals(200);

    $page->fillField('label', 'workspace2');
    $page->fillField('id', 'A!"£%^&*{}#~@?');
    $page->findButton('Save')->click();
    $page->hasContent("This value is not valid");
  }

  /**
   * Tests that the toolbar correctly shows the active workspace.
   */
  public function testWorkspaceToolbar(): void {
    $this->drupalLogin($this->editor1);

    $this->drupalGet('/admin/config/workflow/workspaces/add');
    $this->submitForm([
      'id' => 'test_workspace',
      'label' => 'Test workspace',
    ], 'Save');

    // Activate the test workspace.
    $this->drupalGet('/admin/config/workflow/workspaces/manage/test_workspace/activate');
    $this->submitForm([], 'Confirm');

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    // Toolbar should show the correct label.
    $this->assertTrue($page->hasLink('Test workspace'));

    // Change the workspace label.
    $this->drupalGet('/admin/config/workflow/workspaces/manage/test_workspace/edit');
    $this->submitForm(['label' => 'New name'], 'Save');

    $this->drupalGet('<front>');
    $page = $this->getSession()->getPage();
    // Toolbar should show the new label.
    $this->assertTrue($page->hasLink('New name'));
  }

  /**
   * Tests that the toolbar workspace switcher doesn't disable the page cache.
   */
  public function testToolbarSwitcherDynamicPageCache(): void {
    $node_type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(['type' => $node_type->id()]);
    $this->drupalLogin($this->drupalCreateUser([
      'access toolbar',
      'view any workspace',
    ]));
    $this->drupalGet($node->toUrl());
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'MISS');
    // Reload the page, it should be cached now.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->elementExists('css', '.workspaces-toolbar-tab');
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
  }

}
