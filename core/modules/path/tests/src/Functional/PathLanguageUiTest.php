<?php

namespace Drupal\Tests\path\Functional;

use Drupal\Core\Language\LanguageInterface;

/**
 * Confirm that the Path module user interface works with languages.
 *
 * @group path
 */
class PathLanguageUiTest extends PathTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['path', 'locale', 'locale_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();

    // Create and log in user.
    $web_user = $this->drupalCreateUser([
      'edit any page content',
      'create page content',
      'administer url aliases',
      'create url aliases',
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($web_user);

    // Enable French language.
    $edit = [];
    $edit['predefined_langcode'] = 'fr';

    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => 1];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
  }

  /**
   * Tests that a language-neutral URL alias works.
   */
  public function testLanguageNeutralUrl() {
    $name = $this->randomMachineName(8);
    $edit = [];
    $edit['path[0][value]'] = '/admin/config/search/path';
    $edit['alias[0][value]'] = '/' . $name;
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $this->drupalGet($name);
    $this->assertText(t('Filter aliases'), 'Language-neutral URL alias works');
  }

  /**
   * Tests that a default language URL alias works.
   */
  public function testDefaultLanguageUrl() {
    $name = $this->randomMachineName(8);
    $edit = [];
    $edit['path[0][value]'] = '/admin/config/search/path';
    $edit['alias[0][value]'] = '/' . $name;
    $edit['langcode[0][value]'] = 'en';
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $this->drupalGet($name);
    $this->assertText(t('Filter aliases'), 'English URL alias works');
  }

  /**
   * Tests that a non-default language URL alias works.
   */
  public function testNonDefaultUrl() {
    $name = $this->randomMachineName(8);
    $edit = [];
    $edit['path[0][value]'] = '/admin/config/search/path';
    $edit['alias[0][value]'] = '/' . $name;
    $edit['langcode[0][value]'] = 'fr';
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $this->drupalGet('fr/' . $name);
    $this->assertText(t('Filter aliases'), 'Foreign URL alias works');
  }

  /**
   * Test that language unspecific aliases are shown and saved in the node form.
   */
  public function testNotSpecifiedNode() {
    // Create test node.
    $node = $this->drupalCreateNode();

    // Create a language-unspecific alias in the admin UI, ensure that is
    // displayed and the langcode is not changed when saving.
    $edit = [
      'path[0][value]' => '/node/' . $node->id(),
      'alias[0][value]' => '/' . $this->getRandomGenerator()->word(8),
      'langcode[0][value]' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $this->drupalPostForm('admin/config/search/path/add', $edit, t('Save'));

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('path[0][alias]', $edit['alias[0][value]']);
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet('admin/config/search/path');
    $this->assertSession()->pageTextContains('None');
    $this->assertSession()->pageTextNotContains('English');

    // Create another node, with no alias, to ensure non-language specific
    // aliases are loaded correctly.
    $node = $this->drupalCreateNode();
    $this->drupalget($node->toUrl('edit-form'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->pageTextNotContains(t('The alias is already in use.'));
  }

}
