<?php

namespace Drupal\Tests\media_library\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the media library field widget.
 *
 * @group media_library
 */
class FieldWidgetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_library_test',
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

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access content',
      'create basic_page content',
      'edit own basic_page content',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests saving a required media library field without a value.
   */
  public function testEmptyValue() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Make field_unlimited_media required.
    $field_config = FieldConfig::loadByName('node', 'basic_page', 'field_unlimited_media');
    $field_config->setRequired(TRUE)->save();

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    $page->fillField('title[0][value]', 'My page');
    $page->pressButton('Save');

    // Check that a clear error message is shown.
    $assert_session->pageTextNotContains('This value should not be null');
    $assert_session->pageTextContains(sprintf('%s field is required.', $field_config->label()));
  }

}
