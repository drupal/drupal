<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Database\Database;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeAccessTrait;
use Drupal\user\RoleInterface;

/**
 * Tests node administration page functionality.
 *
 * @group node
 */
class NodeAdminTest extends NodeTestBase {

  use NodeAccessTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to bypass access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user with the 'access content overview' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser1;

  /**
   * A normal user with permission to view own unpublished content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser2;

  /**
   * A normal user with permission to bypass node access content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $baseUser3;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Remove the "view own unpublished content" permission which is set
    // by default for authenticated users so we can test this permission
    // correctly.
    user_role_revoke_permissions(RoleInterface::AUTHENTICATED_ID, ['view own unpublished content']);

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'access content overview',
      'administer nodes',
      'bypass node access',
    ]);
    $this->baseUser1 = $this->drupalCreateUser(['access content overview']);
    $this->baseUser2 = $this->drupalCreateUser([
      'access content overview',
      'view own unpublished content',
    ]);
    $this->baseUser3 = $this->drupalCreateUser([
      'access content overview',
      'bypass node access',
    ]);
  }

  /**
   * Tests that the table sorting works on the content admin pages.
   */
  public function testContentAdminSort(): void {
    $this->drupalLogin($this->adminUser);

    $changed = \Drupal::time()->getRequestTime();
    $connection = Database::getConnection();
    foreach (['dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB'] as $prefix) {
      $changed += 1000;
      $node = $this->drupalCreateNode(['title' => $prefix . $this->randomMachineName(6)]);
      $connection->update('node_field_data')
        ->fields(['changed' => $changed])
        ->condition('nid', $node->id())
        ->execute();
    }

    // Test that the default sort by node.changed DESC actually fires properly.
    $nodes_query = $connection->select('node_field_data', 'n')
      ->fields('n', ['title'])
      ->orderBy('changed', 'DESC')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content');
    foreach ($nodes_query as $delta => $string) {
      // Verify that the node was found in the correct order.
      $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery('//table/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', [
        ':label' => $string,
      ]));
    }

    // Compare the rendered HTML node list to a query for the nodes ordered by
    // title to account for possible database-dependent sort order.
    $nodes_query = $connection->select('node_field_data', 'n')
      ->fields('n', ['title'])
      ->orderBy('title')
      ->execute()
      ->fetchCol();

    $this->drupalGet('admin/content', ['query' => ['sort' => 'asc', 'order' => 'title']]);
    foreach ($nodes_query as $delta => $string) {
      // Verify that the node was found in the correct order.
      $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery('//table/tbody/tr[' . ($delta + 1) . ']/td[2]/a[normalize-space(text())=:label]', [
        ':label' => $string,
      ]));
    }
    // Verify aria-sort is present and its value matches the sort order.
    $this->assertSession()->elementAttributeContains('css', 'table thead tr th.views-field-title', 'aria-sort', 'ascending');
  }

  /**
   * Tests content overview with different user permissions.
   *
   * Taxonomy filters are tested separately.
   *
   * @see TaxonomyNodeFilterTestCase
   */
  public function testContentAdminPages(): void {
    $this->drupalLogin($this->adminUser);

    // Use an explicit changed time to ensure the expected order in the content
    // admin listing. We want these to appear in the table in the same order as
    // they appear in the following code, and the 'content' View has a table
    // style configuration with a default sort on the 'changed' field DESC.
    $time = time();
    $nodes['published_page'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time--]);
    $nodes['published_article'] = $this->drupalCreateNode(['type' => 'article', 'changed' => $time--]);
    $nodes['unpublished_page_1'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time--, 'uid' => $this->baseUser1->id(), 'status' => 0]);
    $nodes['unpublished_page_2'] = $this->drupalCreateNode(['type' => 'page', 'changed' => $time, 'uid' => $this->baseUser2->id(), 'status' => 0]);

    // Verify view, edit, and delete links for any content.
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);

    $node_type_labels = $this->xpath('//td[contains(@class, "views-field-type")]');
    $delta = 0;
    foreach ($nodes as $node) {
      $this->assertSession()->linkByHrefExists('node/' . $node->id());
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/edit');
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/delete');
      // Verify that we can see the content type label.
      $this->assertEquals(trim($node_type_labels[$delta]->getText()), $node->type->entity->label());
      $delta++;
    }

    // Verify filtering by publishing status.
    $this->drupalGet('admin/content', ['query' => ['status' => TRUE]]);

    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_article']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/edit');

    // Verify filtering by status and content type.
    $this->drupalGet('admin/content', ['query' => ['status' => TRUE, 'type' => 'page']]);

    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_article']->id() . '/edit');

    // Verify no operation links are displayed for regular users.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser1);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_page']->id());
    $this->assertSession()->linkByHrefExists('node/' . $nodes['published_article']->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_page']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_page']->id() . '/delete');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_article']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['published_article']->id() . '/delete');

    // Verify no unpublished content is displayed without permission.
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertSession()->fieldNotExists('nodes[' . $nodes['published_page']->id() . ']');

    // Verify unpublished content is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser2);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('node/' . $nodes['unpublished_page_2']->id());
    // Verify no operation links are displayed.
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_2']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_2']->id() . '/delete');

    // Verify user cannot see unpublished content of other users.
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id());
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('node/' . $nodes['unpublished_page_1']->id() . '/delete');

    // Verify no tableselect.
    $this->assertSession()->fieldNotExists('nodes[' . $nodes['unpublished_page_2']->id() . ']');

    // Verify node access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->baseUser3);
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    foreach ($nodes as $node) {
      $this->assertSession()->linkByHrefExists('node/' . $node->id());
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/edit');
      $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/delete');
    }
    // Ensure that the language table column and the language exposed filter are
    // not visible on monolingual sites.
    $this->assertSession()->fieldNotExists('langcode');
    $this->assertEquals(0, count($this->cssSelect('td.views-field-langcode')));
    $this->assertEquals(0, count($this->cssSelect('td.views-field-langcode')));
  }

  /**
   * Tests that the content overview page does not filter out nodes.
   */
  public function testContentAdminPageWithLimitedContentViewer(): void {
    \Drupal::service('module_installer')->install(['node_access_test']);
    $this->addPrivateField(NodeType::load('page'));
    node_access_rebuild();

    $role_id = $this->drupalCreateRole([
      'access content overview',
      'view own unpublished content',
      'node test view',
    ]);
    $viewer_user = $this->drupalCreateUser(values: ['roles' => [$role_id]]);
    // Create published and unpublished content authored by an administrator and
    // the viewer user.
    $nodes_visible = [];
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->adminUser->id(), 'title' => 'Published page by admin']);
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $viewer_user->id(), 'title' => 'Published own page']);
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->adminUser->id(), 'title' => 'Published private page by admin', 'private' => ['value' => 1]]);
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $viewer_user->id(), 'title' => 'Published own private page', 'private' => ['value' => 1]]);
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $viewer_user->id(), 'title' => 'Unpublished own page', 'status' => NodeInterface::NOT_PUBLISHED]);
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $viewer_user->id(), 'title' => 'Unpublished own private page', 'status' => NodeInterface::NOT_PUBLISHED, 'private' => ['value' => 1]]);
    $nodes_visible[] = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->adminUser->id(), 'title' => 'Unpublished private page by admin', 'status' => NodeInterface::NOT_PUBLISHED, 'private' => ['value' => 1]]);

    $this->drupalLogin($viewer_user);
    // Confirm the current user has limited privileges.
    $admin_permissions = ['administer nodes', 'bypass node access'];
    foreach ($admin_permissions as $admin_permission) {
      $this->assertFalse(\Drupal::service('current_user')->hasPermission($admin_permission), sprintf('The current user does not have "%s" permission.', $admin_permission));
    }
    // Confirm that the nodes are visible to the less privileged user.
    foreach ($nodes_visible as $node) {
      self::assertTrue($node->access('view', $viewer_user));
      $this->drupalGet('admin/content');
      $this->assertSession()->linkByHrefExists('node/' . $node->id(), 0, sprintf('The "%s" node is visible on the admin/content page.', $node->getTitle()));
    }

    // Without the "node test view" permission the unpublished page of the
    // admin user is not visible.
    $this->drupalLogin($this->drupalCreateUser(values: [
      'roles' => [
        $this->drupalCreateRole([
          'access content overview',
          'view own unpublished content',
        ]),
      ],
    ]));
    $unpublished_node_by_admin = $this->drupalCreateNode(['type' => 'page', 'uid' => $this->adminUser->id(), 'title' => 'Unpublished page by admin', 'status' => 0]);
    self::assertFalse($unpublished_node_by_admin->access('view'));
    $this->drupalGet('admin/content');
    $this->assertSession()->linkByHrefNotExists('node/' . $unpublished_node_by_admin->id());
  }

  /**
   * Tests content overview for a multilingual site.
   */
  public function testContentAdminPageMultilingual(): void {
    $this->drupalLogin($this->adminUser);

    \Drupal::service('module_installer')->install(['language']);
    ConfigurableLanguage::create([
      'id' => 'es',
      'label' => 'Spanish',
    ])->save();

    $this->drupalCreateNode(['type' => 'page', 'title' => 'English title'])
      ->addTranslation('es')
      ->setTitle('Spanish title')
      ->save();

    $this->drupalGet('admin/content');

    // Ensure that both the language table column as well as the language
    // exposed filter are visible on multilingual sites.
    $this->assertSession()->fieldExists('langcode');
    $this->assertEquals(2, count($this->cssSelect('td.views-field-langcode')));
    $this->assertEquals(2, count($this->cssSelect('td.views-field-langcode')));

    $this->assertSession()->pageTextContains('English title');
    $this->assertSession()->pageTextContains('Spanish title');

    $this->drupalGet('admin/content', ['query' => ['langcode' => '***LANGUAGE_site_default***']]);
    $this->assertSession()->pageTextContains('English title');
    $this->assertSession()->pageTextNotContains('Spanish title');

    $this->drupalGet('admin/content', ['query' => ['langcode' => 'en']]);
    $this->assertSession()->pageTextContains('English title');
    $this->assertSession()->pageTextNotContains('Spanish title');

    $this->drupalGet('admin/content', ['query' => ['langcode' => 'und']]);
    $this->assertSession()->pageTextNotContains('English title');
    $this->assertSession()->pageTextNotContains('Spanish title');

    $this->drupalGet('admin/content', ['query' => ['langcode' => 'zxx']]);
    $this->assertSession()->pageTextNotContains('English title');
    $this->assertSession()->pageTextNotContains('Spanish title');

    $this->drupalGet('admin/content', ['query' => ['langcode' => html_entity_decode('***LANGUAGE_language_interface***')]]);
    $this->assertSession()->pageTextContains('English title');
    $this->assertSession()->pageTextNotContains('Spanish title');

    $this->drupalGet('es/admin/content', ['query' => ['langcode' => html_entity_decode('***LANGUAGE_language_interface***')]]);
    $this->assertSession()->pageTextNotContains('English title');
    $this->assertSession()->pageTextContains('Spanish title');
  }

}
