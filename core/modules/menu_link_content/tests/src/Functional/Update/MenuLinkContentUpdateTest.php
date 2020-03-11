<?php

namespace Drupal\Tests\menu_link_content\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the upgrade path for custom menu links.
 *
 * @group menu_link_content
 * @group Update
 * @group legacy
 */
class MenuLinkContentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.menu-link-content-null-data-3056543.php',
    ];
  }

  /**
   * Tests the addition of the publishing status entity key.
   *
   * @see menu_link_content_update_8601()
   */
  public function testPublishedEntityKeyAddition() {
    $this->runUpdates();

    // Log in as user 1.
    $account = User::load(1);
    $account->passRaw = 'drupal';
    $this->drupalLogin($account);

    // Make sure our custom menu link exists.
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/structure/menu/item/1/edit');
    $assert_session->checkboxChecked('edit-enabled-value');

    // Check that custom menu links can be created, saved and then loaded.
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
    $menu_link = $storage->create([
      'menu_name' => 'main',
      'link' => 'route:user.page',
      'title' => 'Pineapple',
    ]);
    $menu_link->save();

    $menu_link = $storage->loadUnchanged($menu_link->id());

    $this->assertEquals('main', $menu_link->getMenuName());
    $this->assertEquals('Pineapple', $menu_link->label());
    $this->assertEquals('route:user.page', $menu_link->link->uri);
    $this->assertTrue($menu_link->isPublished());
  }

  /**
   * Tests the conversion of custom menu links to be revisionable.
   *
   * @see menu_link_content_post_update_make_menu_link_content_revisionable()
   */
  public function testConversionToRevisionable() {
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('menu_link_content');
    $this->assertFalse($entity_type->isRevisionable());

    // Set the batch size to 1 to test multiple steps.
    drupal_rewrite_settings([
      'settings' => [
        'update_sql_batch_size' => (object) [
          'value' => 1,
          'required' => TRUE,
        ],
      ],
    ]);

    // Check that there are broken menu links in the database tables, initially.
    $this->assertMenuLinkTitle(997, '');
    $this->assertMenuLinkTitle(998, '');
    $this->assertMenuLinkTitle(999, 'menu_link_999-es');

    $this->runUpdates();

    // Check that the update function returned the expected message.
    $this->assertSession()->pageTextContains('Custom menu links have been converted to be revisionable. 2 menu links with data integrity issues were restored. More details have been logged.');

    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('menu_link_content');
    $this->assertTrue($entity_type->isRevisionable());

    // Log in as user 1.
    $account = User::load(1);
    $account->passRaw = 'drupal';
    $this->drupalLogin($account);

    // Make sure our custom menu link exists.
    $assert_session = $this->assertSession();
    $this->drupalGet('admin/structure/menu/item/1/edit');
    $assert_session->checkboxChecked('edit-enabled-value');

    // Check that custom menu links can be created, saved and then loaded.
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link */
    $menu_link = $storage->create([
      'menu_name' => 'main',
      'link' => 'route:user.page',
      'title' => 'Pineapple',
    ]);
    $menu_link->save();

    $storage->resetCache();
    $menu_link = $storage->loadRevision($menu_link->getRevisionId());

    $this->assertEquals('main', $menu_link->getMenuName());
    $this->assertEquals('Pineapple', $menu_link->label());
    $this->assertEquals('route:user.page', $menu_link->link->uri);
    $this->assertTrue($menu_link->isPublished());

    // Check that two menu links were restored and one was ignored. The latter
    // cannot be manually restored, since we would end up with two data table
    // records having "default_langcode" equalling 1, which would not make
    // sense.
    $this->assertMenuLinkTitle(997, 'menu_link_997');
    $this->assertMenuLinkTitle(998, 'menu_link_998');
    $this->assertMenuLinkTitle(999, 'menu_link_999-es');
  }

  /**
   * Assert that a menu link label matches the expectation.
   *
   * @param string $id
   *   The menu link ID.
   * @param string $expected_title
   *   The expected menu link title.
   */
  protected function assertMenuLinkTitle($id, $expected_title) {
    $database = \Drupal::database();
    $query = $database->select('menu_link_content_data', 'd');
    $query->join('menu_link_content', 'b', 'b.id = d.id AND d.default_langcode = 1');
    $title = $query
      ->fields('d', ['title'])
      ->condition('d.id', $id)
      ->execute()
      ->fetchField();

    $this->assertSame($expected_title, $title ?: '');
  }

  /**
   * Test the update hook requirements check for revisionable menu links.
   *
   * @see menu_link_content_post_update_make_menu_link_content_revisionable()
   * @see menu_link_content_requirements()
   */
  public function testMissingDataUpdateRequirementsCheck() {
    // Insert invalid data for a non-existent menu link.
    Database::getConnection()->insert('menu_link_content')
      ->fields([
        'id' => '3',
        'bundle' => 'menu_link_content',
        'uuid' => '15396f85-3c11-4f52-81af-44d2cb5e829f',
        'langcode' => 'en',
      ])
      ->execute();
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);
    $this->drupalGet($this->updateUrl);

    $this->assertSession()->pageTextContains('Errors found');
    $this->assertSession()->elementTextContains('css', '.system-status-report__entry--error', 'The make_menu_link_content_revisionable database update cannot be run until the data has been fixed.');
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
