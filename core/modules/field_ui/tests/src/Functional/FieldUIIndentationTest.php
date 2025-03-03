<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests indentation on Field UI.
 *
 * @group field_ui
 */
class FieldUIIndentationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui', 'field_ui_test'];

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
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node display',
    ]);
    $this->drupalLogin($admin_user);

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

  }

  /**
   * Tests that the indentation classes are present in the content type display settings.
   */
  public function testIndentation(): void {
    $this->drupalGet('admin/structure/types/manage/page/display');
    $this->assertSession()->responseContains('js-indentation indentation');
  }

}
