<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Adds a new language with translations and tests language list order.
 *
 * @group language
 */
class LanguageLocaleListTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add a default locale storage for all these tests.
    $this->storage = $this->container->get('locale.storage');
  }

  /**
   * Tests adding, editing, and deleting languages.
   */
  public function testLanguageLocaleList() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Add predefined language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');
    $this->assertSession()->statusMessageContains('The language French has been created and can now be used', 'status');
    $this->assertSession()->addressEquals(Url::fromRoute('entity.configurable_language.collection'));
    $this->rebuildContainer();

    // Translate Spanish language to French (Espagnol).
    $source = $this->storage->createString([
      'source' => 'Spanish',
      'context' => '',
    ])->save();
    $this->storage->createTranslation([
      'lid' => $source->lid,
      'language' => 'fr',
      'translation' => 'Espagnol',
    ])->save();

    // Get language list displayed in select list.
    $this->drupalGet('fr/admin/config/regional/language/add');
    $options = $this->assertSession()->selectExists('edit-predefined-langcode')->findAll('css', 'option');
    $options = array_map(function ($item) {
      return $item->getText();
    }, $options);
    // Remove the 'Custom language...' option form the end.
    array_pop($options);
    // Order language list.
    $options_ordered = $options;
    natcasesort($options_ordered);

    // Check the language list displayed is ordered.
    $this->assertSame($options, $options_ordered, 'Language list is ordered.');
  }

}
