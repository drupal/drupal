<?php

namespace Drupal\Tests\node\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Menu\AssertBreadcrumbTrait;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Ensures that node type functions work correctly.
 *
 * @group node
 */
class NodeTypeTest extends NodeTestBase {

  use AssertBreadcrumbTrait;
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Ensures that node type functions (node_type_get_*) work correctly.
   *
   * Load available node types and validate the returned data.
   */
  public function testNodeTypeGetFunctions() {
    $node_types = NodeType::loadMultiple();
    $node_names = node_type_get_names();

    $this->assertTrue(isset($node_types['article']), 'Node type article is available.');
    $this->assertTrue(isset($node_types['page']), 'Node type basic page is available.');

    $this->assertEquals($node_names['article'], $node_types['article']->label(), 'Correct node type base has been returned.');

    $article = NodeType::load('article');
    $this->assertEquals($node_types['article'], $article, 'Correct node type has been returned.');
    $this->assertEquals($node_types['article']->label(), $article->label(), 'Correct node type name has been returned.');
  }

  /**
   * Tests creating a content type programmatically and via a form.
   */
  public function testNodeTypeCreation() {
    // Create a content type programmatically.
    $type = $this->drupalCreateContentType();

    $type_exists = (bool) NodeType::load($type->id());
    $this->assertTrue($type_exists, 'The new content type has been created in the database.');

    // Log in a test user.
    $web_user = $this->drupalCreateUser([
      'create ' . $type->label() . ' content',
    ]);
    $this->drupalLogin($web_user);

    $this->drupalGet('node/add/' . $type->id());
    $this->assertSession()->statusCodeEquals(200);

    // Create a content type via the user interface.
    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
    ]);
    $this->drupalLogin($web_user);

    $this->drupalGet('node/add');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:node_type_list');
    $this->assertCacheContext('user.permissions');
    $elements = $this->cssSelect('dl.node-type-list dt');
    $this->assertCount(3, $elements);

    $edit = [
      'name' => 'foo',
      'title_label' => 'title for foo',
      'type' => 'foo',
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm($edit, 'Save and manage fields');
    $type_exists = (bool) NodeType::load('foo');
    $this->assertTrue($type_exists, 'The new content type has been created in the database.');

    $this->drupalGet('node/add');
    $elements = $this->cssSelect('dl.node-type-list dt');
    $this->assertCount(4, $elements);
  }

  /**
   * Tests editing a node type using the UI.
   */
  public function testNodeTypeEditing() {
    $assert = $this->assertSession();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($web_user);

    $field = FieldConfig::loadByName('node', 'page', 'body');
    $this->assertEquals('Body', $field->getLabel(), 'Body field was found.');

    // Verify that title and body fields are displayed.
    $this->drupalGet('node/add/page');
    $assert->pageTextContains('Title');
    $assert->pageTextContains('Body');

    // Rename the title field.
    $edit = [
      'title_label' => 'Foo',
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, 'Save content type');

    $this->drupalGet('node/add/page');
    $assert->pageTextContains('Foo');
    $assert->pageTextNotContains('Title');

    // Change the name and the description.
    $edit = [
      'name' => 'Bar',
      'description' => 'Lorem ipsum.',
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, 'Save content type');

    $this->drupalGet('node/add');
    $assert->pageTextContains('Bar');
    $assert->pageTextContains('Lorem ipsum');
    $this->clickLink('Bar');
    $assert->pageTextContains('Foo');
    $assert->pageTextContains('Body');

    // Change the name through the API
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::load('page');
    $node_type->set('name', 'NewBar');
    $node_type->save();

    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $node_bundles = $bundle_info->getBundleInfo('node');
    $this->assertEquals('NewBar', $node_bundles['page']['label'], 'Node type bundle cache is updated');

    // Remove the body field.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.body/delete');
    $this->submitForm([], 'Delete');
    // Resave the settings for this type.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm([], 'Save content type');
    $front_page_path = Url::fromRoute('<front>')->toString();
    $this->assertBreadcrumb('admin/structure/types/manage/page/fields', [
      $front_page_path => 'Home',
      'admin/structure/types' => 'Content types',
      'admin/structure/types/manage/page' => 'NewBar',
    ]);
    // Check that the body field doesn't exist.
    $this->drupalGet('node/add/page');
    $assert->pageTextNotContains('Body');
  }

  /**
   * Tests deleting a content type that still has content.
   */
  public function testNodeTypeDeletion() {
    $this->drupalPlaceBlock('page_title_block');
    // Create a content type programmatically.
    $type = $this->drupalCreateContentType();

    // Log in a test user.
    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
    ]);
    $this->drupalLogin($web_user);

    // Add a new node of this type.
    $node = $this->drupalCreateNode(['type' => $type->id()]);
    // Attempt to delete the content type, which should not be allowed.
    $this->drupalGet('admin/structure/types/manage/' . $type->label() . '/delete');
    $this->assertRaw(
     t('%type is used by 1 piece of content on your site. You can not remove this content type until you have removed all of the %type content.', ['%type' => $type->label()])
    );
    $this->assertNoText('This action cannot be undone.');

    // Delete the node.
    $node->delete();
    // Attempt to delete the content type, which should now be allowed.
    $this->drupalGet('admin/structure/types/manage/' . $type->label() . '/delete');
    $this->assertRaw(
      t('Are you sure you want to delete the content type %type?', ['%type' => $type->label()])
    );
    $this->assertSession()->pageTextContains('This action cannot be undone.');

    // Test that a locked node type could not be deleted.
    $this->container->get('module_installer')->install(['node_test_config']);
    // Lock the default node type.
    $locked = \Drupal::state()->get('node.type.locked');
    $locked['default'] = 'default';
    \Drupal::state()->set('node.type.locked', $locked);
    // Call to flush all caches after installing the forum module in the same
    // way installing a module through the UI does.
    $this->resetAll();
    $this->drupalGet('admin/structure/types/manage/default');
    $this->assertSession()->linkNotExists('Delete');
    $this->drupalGet('admin/structure/types/manage/default/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->container->get('module_installer')->uninstall(['node_test_config']);
    $this->container = \Drupal::getContainer();
    unset($locked['default']);
    \Drupal::state()->set('node.type.locked', $locked);
    $this->drupalGet('admin/structure/types/manage/default');
    $this->clickLink(t('Delete'));
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Delete');
    $this->assertFalse((bool) NodeType::load('default'), 'Node type with machine default deleted.');
  }

  /**
   * Tests Field UI integration for content types.
   */
  public function testNodeTypeFieldUiPermissions() {
    // Create an admin user who can only manage node fields.
    $admin_user_1 = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($admin_user_1);

    // Test that the user only sees the actions available to them.
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->linkByHrefExists('admin/structure/types/manage/article/fields');
    $this->assertSession()->linkByHrefNotExists('admin/structure/types/manage/article/display');

    // Create another admin user who can manage node fields display.
    $admin_user_2 = $this->drupalCreateUser([
      'administer content types',
      'administer node display',
    ]);
    $this->drupalLogin($admin_user_2);

    // Test that the user only sees the actions available to them.
    $this->drupalGet('admin/structure/types');
    $this->assertSession()->linkByHrefNotExists('admin/structure/types/manage/article/fields');
    $this->assertSession()->linkByHrefExists('admin/structure/types/manage/article/display');
  }

  /**
   * Tests for when there are no content types defined.
   */
  public function testNodeTypeNoContentType() {
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info */
    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $this->assertCount(2, $bundle_info->getBundleInfo('node'), 'The bundle information service has 2 bundles for the Node entity type.');
    $web_user = $this->drupalCreateUser(['administer content types']);
    $this->drupalLogin($web_user);

    // Delete 'article' bundle.
    $this->drupalGet('admin/structure/types/manage/article/delete');
    $this->submitForm([], 'Delete');
    // Delete 'page' bundle.
    $this->drupalGet('admin/structure/types/manage/page/delete');
    $this->submitForm([], 'Delete');

    // Navigate to content type administration screen
    $this->drupalGet('admin/structure/types');
    $this->assertRaw(t('No content types available. <a href=":link">Add content type</a>.', [
        ':link' => Url::fromRoute('node.type_add')->toString(),
      ]));

    $bundle_info->clearCachedBundles();
    $this->assertCount(0, $bundle_info->getBundleInfo('node'), 'The bundle information service has 0 bundles for the Node entity type.');
  }

}
