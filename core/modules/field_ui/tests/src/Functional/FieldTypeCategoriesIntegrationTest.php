<?php

declare(strict_types=1);

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
  public function testLibrariesLoaded(): void {
    $this->drupalGet('admin/structure/types/manage/' . $this->drupalCreateContentType()->id() . '/fields/add-field');
    $settings = $this->getDrupalSettings();
    $css_libraries = [
      'file/drupal.file-icon',
      'text/drupal.text-icon',
      'options/drupal.options-icon',
      'comment/drupal.comment-icon',
      'link/drupal.link-icon',
    ];
    $libraries = explode(',', $settings['ajaxPageState']['libraries']);
    foreach ($css_libraries as $css_library) {
      $this->assertContains($css_library, $libraries);
    }
  }

}
