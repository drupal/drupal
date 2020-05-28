<?php

namespace Drupal\Tests\views_ui\Functional;

/**
 * Tests covering Preview of unsaved Views.
 *
 * @group views_ui
 */
class UnsavedPreviewTest extends UITestBase {

  /**
    * Views used by this test.
    *
    * @var array
    */
  public static $testViews = ['content'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An admin user with the 'administer views' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views_ui'];

  /**
   * Sets up a Drupal site for running functional and integration tests.
   */
  protected function setUp($import_test_views = TRUE): void {
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
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Add Page'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/structure/views/nojs/display/content/page_2/path');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, ['path' => 'foobarbaz'], t('Apply'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Update preview'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText(t('This display has no path'));

    $this->drupalGet('admin/structure/views/view/content/edit/page_2');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Update preview'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertLinkByHref('foobarbaz');
  }

}
