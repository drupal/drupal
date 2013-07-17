<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeTranslationUITest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Entity\EntityInterface;
use Drupal\content_translation\Tests\ContentTranslationUITest;

/**
 * Tests the Node Translation UI.
 */
class NodeTranslationUITest extends ContentTranslationUITest {

  /**
   * The title of the test node.
   */
  protected $title;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'node', 'datetime', 'field_ui');

  public static function getInfo() {
    return array(
      'name' => 'Node translation UI',
      'description' => 'Tests the node translation UI.',
      'group' => 'Node',
    );
  }

  function setUp() {
    $this->entityType = 'node';
    $this->bundle = 'article';
    $this->title = $this->randomName();
    parent::setUp();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::setupBundle().
   */
  protected function setupBundle() {
    parent::setupBundle();
    $this->drupalCreateContentType(array('type' => $this->bundle, 'name' => $this->bundle));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer nodes', "edit any $this->bundle content"));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // Node title is not translatable yet, hence we use a fixed value.
    return array('title' => $this->title) + parent::getNewEntityValues($langcode);
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getFormSubmitAction().
   */
  protected function getFormSubmitAction(EntityInterface $entity) {
    if ($entity->status) {
      return t('Save and unpublish');
    }
    return t('Save and keep unpublished');
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::assertPublishedStatus().
   */
  protected function assertPublishedStatus() {
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $path = $this->controller->getEditPath($entity);
    $languages = language_list();

    $actions = array(
      array(t('Save and publish'), t('Save and keep published')),
      array(t('Save and unpublish'), t('Save and keep unpublished')),
    );

    foreach ($actions as $index => $status_actions) {
      // (Un)publish the node translations and check that the translation
      // statuses are (un)published accordingly.
      foreach ($this->langcodes as $langcode) {
        if (!empty($status_actions)) {
          $action = array_shift($status_actions);
        }
        $this->drupalPost($path, array(), $action, array('language' => $languages[$langcode]));
      }
      $entity = entity_load($this->entityType, $this->entityId, TRUE);
      foreach ($this->langcodes as $langcode) {
        // The node is created as unpulished thus we switch to the published
        // status first.
        $status = !$index;
        $this->assertEqual($status, $entity->translation[$langcode]['status'], 'The translation has been correctly unpublished.');
      }
    }
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::assertAuthoringInfo().
   */
  protected function assertAuthoringInfo() {
    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    $path = $this->controller->getEditPath($entity);
    $languages = language_list();
    $values = array();

    // Post different authoring information for each translation.
    foreach ($this->langcodes as $index => $langcode) {
      $user = $this->drupalCreateUser();
      $values[$langcode] = array(
        'uid' => $user->id(),
        'created' => REQUEST_TIME - mt_rand(0, 1000),
      );
      $edit = array(
        'name' => $user->name,
        'date[date]' => format_date($values[$langcode]['created'], 'custom', 'Y-m-d'),
        'date[time]' => format_date($values[$langcode]['created'], 'custom', 'H:i:s'),
      );
      $this->drupalPost($path, $edit, $this->getFormSubmitAction($entity), array('language' => $languages[$langcode]));
    }

    $entity = entity_load($this->entityType, $this->entityId, TRUE);
    foreach ($this->langcodes as $langcode) {
      $this->assertEqual($entity->translation[$langcode]['uid'] == $values[$langcode]['uid'], 'Translation author correctly stored.');
      $this->assertEqual($entity->translation[$langcode]['created'] == $values[$langcode]['created'], 'Translation date correctly stored.');
    }
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
    $admin_user = $this->drupalCreateUser(array_merge($this->getTranslatorPermissions(), array('access administration pages', 'bypass node access', 'administer node fields')));
    $this->drupalLogin($admin_user);

    $article = $this->drupalCreateNode(array('type' => 'article', 'langcode' => 'en'));

    // Visit translation page.
    $this->drupalGet('node/' . $article->nid . '/translations');
    $this->assertRaw('Not translated');

    // Delete the only translatable field.
    field_info_field('field_test_et_ui_test')->delete();

    // Visit translation page.
    $this->drupalGet('node/' . $article->nid . '/translations');
    $this->assertRaw('No translatable fields');
  }

  /**
   * Tests that no metadata is stored for a disabled bundle.
   */
  public function testDisabledBundle() {
    // Create a bundle that does not have translation enabled.
    $disabledBundle = $this->randomName();
    $this->drupalCreateContentType(array('type' => $disabledBundle, 'name' => $disabledBundle));

    // Create a node for each bundle.
    $enabledNode = $this->drupalCreateNode(array('type' => $this->bundle));
    $disabledNode = $this->drupalCreateNode(array('type' => $disabledBundle));

    // Make sure that only a single row was inserted into the
    // {content_translation} table.
    $rows = db_query('SELECT * FROM {content_translation}')->fetchAll();
    $this->assertEqual(1, count($rows));
    $this->assertEqual($enabledNode->id(), reset($rows)->entity_id);
  }

}
