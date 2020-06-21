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
  public static $modules = ['language', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->assertText('The language French has been created and can now be used');
    $this->assertUrl(Url::fromRoute('entity.configurable_language.collection', [], ['absolute' => TRUE])->toString());
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
    $option_elements = $this->xpath('//select[@id="edit-predefined-langcode/option"]');
    $options = [];
    foreach ($option_elements as $option_element) {
      $options[] = $option_element->getText();
    }
    // Remove the 'Custom language...' option form the end.
    array_pop($options);
    // Order language list.
    $options_ordered = $options;
    natcasesort($options_ordered);

    // Check the language list displayed is ordered.
    $this->assertTrue($options === $options_ordered, 'Language list is ordered.');
  }

}
