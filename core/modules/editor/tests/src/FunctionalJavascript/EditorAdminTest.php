<?php

namespace Drupal\Tests\editor\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * @group editor
 */
class EditorAdminTest extends WebDriverTestBase {

  /**
   * The user to use during testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer filters',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that editor selection can be toggled without breaking ajax.
   */
  public function testEditorSelection() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/config/content/formats/add');
    $page->fillField('name', 'Sulaco');
    // Wait for machine name to be filled in.
    $this->assertNotEmpty($assert_session->waitForText('sulaco'));
    $page->selectFieldOption('editor[editor]', 'ckeditor');
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', 'ul.ckeditor-toolbar-group-buttons'));
    $page->pressButton('Save configuration');

    // Test that toggling the editor selection off and back on works.
    $this->drupalGet('/admin/config/content/formats/manage/sulaco');
    // Deselect and reselect an editor.
    $page->selectFieldOption('editor[editor]', '');
    $this->assertNotEmpty($this->assertSession()->waitForElementRemoved('css', 'ul.ckeditor-toolbar-group-buttons'));
    $page->selectFieldOption('editor[editor]', 'ckeditor');
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', 'ul.ckeditor-toolbar-group-buttons'));
  }

}
