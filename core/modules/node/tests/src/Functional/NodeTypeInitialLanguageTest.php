<?php

namespace Drupal\Tests\node\Functional;

use Drupal\Core\Language\LanguageInterface;

/**
 * Tests node type initial language settings.
 *
 * @group node
 */
class NodeTypeInitialLanguageTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'administer node display',
      'administer languages',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the node type initial language defaults, and modifies them.
   *
   * The default initial language must be the site's default, and the language
   * locked option must be on.
   */
  public function testNodeTypeInitialLanguageDefaults() {
    $this->drupalGet('admin/structure/types/manage/article');
    $this->assertTrue($this->assertSession()->optionExists('edit-language-configuration-langcode', LanguageInterface::LANGCODE_SITE_DEFAULT)->isSelected());
    $this->assertSession()->checkboxNotChecked('edit-language-configuration-language-alterable');

    // Tests if the language field cannot be rearranged on the manage fields tab.
    $this->drupalGet('admin/structure/types/manage/article/fields');
    $this->assertSession()->elementNotExists('xpath', '//*[@id="field-overview"]/*[@id="language"]');

    // Verify that language is not selectable on node add page by default.
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldNotExists('langcode');

    // Adds a new language and set it as default.
    $edit = [
      'predefined_langcode' => 'hu',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');
    $edit = [
      'site_default_language' => 'hu',
    ];
    $this->drupalGet('admin/config/regional/language');
    $this->submitForm($edit, 'Save configuration');

    // Tests the initial language after changing the site default language.
    // First unhide the language selector.
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/types/manage/article');
    $this->submitForm($edit, 'Save content type');
    $this->drupalGet('node/add/article');
    // Ensure that the language is selectable on node add page when language
    // not hidden.
    $this->assertSession()->fieldExists('langcode[0][value]');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'hu')->isSelected());

    // Tests if the language field can be rearranged on the manage form display
    // tab.
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->assertSession()->elementExists('xpath', '//*[@id="langcode"]');

    // Tests if the language field can be rearranged on the manage display tab.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertSession()->elementExists('xpath', '//*[@id="langcode"]');

    // Tests if the language field is hidden by default.
    $this->assertTrue($this->assertSession()->optionExists('edit-fields-langcode-region', 'hidden')->isSelected());

    // Changes the initial language settings.
    $edit = [
      'language_configuration[langcode]' => 'en',
    ];
    $this->drupalGet('admin/structure/types/manage/article');
    $this->submitForm($edit, 'Save content type');
    $this->drupalGet('node/add/article');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'en')->isSelected());
  }

  /**
   * Tests language field visibility features.
   */
  public function testLanguageFieldVisibility() {
    // Creates a node to test Language field visibility feature.
    $edit = [
      'title[0][value]' => $this->randomMachineName(8),
      'body[0][value]' => $this->randomMachineName(16),
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotEmpty($node, 'Node found in database.');

    // Loads node page and check if Language field is hidden by default.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementNotExists('xpath', '//div[@id="field-language-display"]/div');

    // Configures Language field formatter and check if it is saved.
    $edit = [
      'fields[langcode][type]' => 'language',
      'fields[langcode][region]' => 'content',
    ];
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->assertTrue($this->assertSession()->optionExists('edit-fields-langcode-type', 'language')->isSelected());

    // Loads node page and check if Language field is shown.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->elementExists('xpath', '//div[@id="field-language-display"]/div');
  }

}
