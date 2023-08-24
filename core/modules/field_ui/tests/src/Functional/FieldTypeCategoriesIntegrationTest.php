<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests field UI integration with field type categories for loading libraries.
 *
 * @group field_ui
 */
class FieldTypeCategoriesIntegrationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'field_ui',
    'options',
    'comment',
    'link',
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
    // Create a test user.
    $admin_user = $this->drupalCreateUser(['administer node fields']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests if the libraries are loaded on FieldStorageAddForm.
   */
  public function testLibrariesLoaded() {
    $this->drupalGet('admin/structure/types/manage/' . $this->drupalCreateContentType()->id() . '/fields/add-field');
    $page_content = $this->getSession()->getPage()->getContent();
    $css_libraries = [
      'drupal.file-icon',
      'drupal.text-icon',
      'drupal.options-icon',
      'drupal.comment-icon',
      'drupal.link-icon',
    ];
    foreach ($css_libraries as $css_library) {
      // Check if the library asset is present in the rendered HTML.
      $this->assertStringContainsString($css_library, $page_content);
    }
  }

}
