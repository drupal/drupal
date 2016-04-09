<?php

namespace Drupal\views_ui\Tests;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests covering Preview of unsaved Views.
 *
 * @group views_ui
 */
class UnsavedPreviewTest extends ViewTestBase {

  /**
    * Views used by this test.
    *
    * @var array
    */
  public static $testViews = ['content'];

  /**
   * An admin user with the 'administer views' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('node', 'views_ui');

  /**
   * Sets up a Drupal site for running functional and integration tests.
   */
  protected function setUp() {
    parent::setUp(FALSE);

    $this->adminUser = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests previews of unsaved new page displays.
   */
  public function testUnsavedPageDisplayPreview() {
    $this->drupalCreateContentType(['type' => 'page']);
    for ($i = 0; $i < 5; $i++) {
      $this->drupalCreateNode();
    }

    $this->drupalGet('admin/structure/views/view/content');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, [], t('Add Page'));
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/views/nojs/display/content/page_2/path');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, ['path' => 'foobarbaz'], t('Apply'));
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, [], t('Update preview'));
    $this->assertResponse(200);
    $this->assertText(t('This display has no path'));

    $this->drupalGet('admin/structure/views/view/content/edit/page_2');
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertResponse(200);

    $this->drupalPostForm(NULL, [], t('Update preview'));
    $this->assertResponse(200);
    $this->assertLinkByHref('foobarbaz');
  }

}
