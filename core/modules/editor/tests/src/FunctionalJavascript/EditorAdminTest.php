<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

// cspell:ignore sulaco

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
    'editor_test',
    'ckeditor5',
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
  public function testEditorSelection(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/config/content/formats/add');
    $page->fillField('name', 'Sulaco');
    // Wait for machine name to be filled in.
    $this->assertNotEmpty($assert_session->waitForText('sulaco'));
    $page->selectFieldOption('editor[editor]', 'unicorn');
    $this->assertNotEmpty($this->assertSession()->waitForField('editor[settings][ponies_too]'));
    $page->pressButton('Save configuration');

    // Test that toggling the editor selection off and back on works.
    $this->drupalGet('/admin/config/content/formats/manage/sulaco');
    // Deselect and reselect an editor.
    $page->selectFieldOption('editor[editor]', '');
    $this->assertNotEmpty($this->assertSession()->waitForElementRemoved('named', ['field', 'editor[settings][ponies_too]']));
    $page->selectFieldOption('editor[editor]', 'unicorn');
    $this->assertNotEmpty($this->assertSession()->waitForField('editor[settings][ponies_too]'));
  }

  /**
   * Tests that editor creation works fine while switching text editor field.
   *
   * The order in which the different editors are selected is significant,
   * because the form state must change accordingly.
   * @see https://www.drupal.org/project/drupal/issues/3230829
   */
  public function testEditorCreation(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('/admin/config/content/formats/add');
    $page->fillField('name', $this->randomString());
    $page->selectFieldOption('editor[editor]', 'ckeditor5');
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', 'ul.ckeditor5-toolbar-available__buttons'));

    $page->selectFieldOption('editor[editor]', '');
    $this->assertNotEmpty($this->assertSession()->waitForElementRemoved('css', 'ul.ckeditor5-toolbar-available__buttons'));
    $this->assertEmpty($this->assertSession()->waitForField('editor[settings][ponies_too]'));

    $page->selectFieldOption('editor[editor]', 'unicorn');
    $this->assertNotEmpty($this->assertSession()->waitForField('editor[settings][ponies_too]'));
  }

}
