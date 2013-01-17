<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTranslationUITest.
 */

namespace Drupal\node\Tests;

use Drupal\translation_entity\Tests\EntityTranslationUITest;

/**
 * Tests the Node Translation UI.
 */
class NodeTranslationUITest extends EntityTranslationUITest {

  /**
   * The title of the test node.
   */
  protected $title;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'translation_entity', 'node', 'field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Node translation UI',
      'description' => 'Tests the node translation UI.',
      'group' => 'Node',
    );
  }

  /**
   * Overrides \Drupal\simpletest\WebTestBase::setUp().
   */
  function setUp() {
    $this->entityType = 'node';
    $this->bundle = 'article';
    $this->title = $this->randomName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::setupBundle().
   */
  protected function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(array('type' => $this->bundle, 'name' => $this->bundle));
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getTranslatorPermission().
   */
  function getTranslatorPermissions() {
    return array("edit any $this->bundle content", "translate $this->entityType entities", 'edit original values');
  }

  /**
   * Tests translate link on content admin page.
   */
  function testTranslateLinkContentAdminPage() {
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'access content overview', 'administer nodes', 'bypass node access'));
    $this->drupalLogin($this->admin_user);

    $page = $this->drupalCreateNode(array('type' => 'page'));
    $article = $this->drupalCreateNode(array('type' => 'article'));

    // Verify translation links.
    $this->drupalGet('admin/content');
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $article->nid . '/translations');
    $this->assertNoLinkByHref('node/' . $page->nid . '/translations');
  }

  /**
   * Tests field translation form.
   */
  function testFieldTranslationForm() {
    $admin_user = $this->drupalCreateUser(array('translate any entity', 'access administration pages', 'bypass node access', 'administer node fields'));
    $this->drupalLogin($admin_user);

    $article = $this->drupalCreateNode(array('type' => 'article', 'langcode' => 'en'));

    // Visit translation page.
    $this->drupalGet('node/' . $article->nid . '/translations');
    $this->assertRaw('Not translated');

    // Delete the only translatable field.
    field_delete_field('field_test_et_ui_test');

    // Visit translation page.
    $this->drupalGet('node/' . $article->nid . '/translations');
    $this->assertRaw('no translatable fields');
  }

  /**
   * Overrides \Drupal\translation_entity\Tests\EntityTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Node title is not translatable yet, hence we use a fixed value.
    return array('title' => $this->title) + parent::getNewEntityValues($langcode);
  }

  /**
   * Test that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle() {
    // Create a bundle that does not have translation enabled.
    $disabledBundle = $this->randomName();
    $this->drupalCreateContentType(array('type' => $disabledBundle, 'name' => $disabledBundle));

    // Create a node for each bundle.
    $enabledNode = $this->drupalCreateNode(array('type' => $this->bundle));
    $disabledNode = $this->drupalCreateNode(array('type' => $disabledBundle));

    // Make sure that only a single row was inserted into the
    // {translation_entity} table.
    $rows = db_query('SELECT * FROM {translation_entity}')->fetchAll();
    $this->assertEqual(1, count($rows));
    $this->assertEqual($enabledNode->id(), reset($rows)->entity_id);
  }

}
