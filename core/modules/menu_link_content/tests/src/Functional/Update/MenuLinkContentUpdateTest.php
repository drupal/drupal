<?php

namespace Drupal\Tests\menu_link_content\Functional\Update;

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
   * {@inheritdoc}
   */
  protected function replaceUser1() {
    // Do not replace the user from our dump.
  }

}
