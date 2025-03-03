<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the language functionality for the taxonomy terms.
 *
 * @group taxonomy
 */
class TermLanguageTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an administrative user.
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));

    // Create a vocabulary to which the terms will be assigned.
    $this->vocabulary = $this->createVocabulary();

    // Add some custom languages.
    foreach (['aa', 'bb', 'cc'] as $language_code) {
      ConfigurableLanguage::create([
        'id' => $language_code,
        'label' => $this->randomMachineName(),
      ])->save();
    }
  }

  /**
   * Tests the language of a term.
   */
  public function testTermLanguage(): void {
    // Configure the vocabulary to not hide the language selector.
    $edit = [
      'default_language[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id());
    $this->submitForm($edit, 'Save');

    // Add a term.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    // Check that we have the language selector.
    $this->assertSession()->fieldExists('edit-langcode-0-value');
    // Submit the term.
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'langcode[0][value]' => 'aa',
    ];
    $this->submitForm($edit, 'Save');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $edit['name[0][value]'],
    ]);
    $term = reset($terms);
    $this->assertEquals($edit['langcode[0][value]'], $term->language()->getId(), 'The term contains the correct langcode.');

    // Check if on the edit page the language is correct.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', $edit['langcode[0][value]'])->isSelected());

    // Change the language of the term.
    $edit['langcode[0][value]'] = 'bb';
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check again that on the edit page the language is correct.
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', $edit['langcode[0][value]'])->isSelected());
  }

  /**
   * Tests the default language selection for taxonomy terms.
   */
  public function testDefaultTermLanguage(): void {
    // Configure the vocabulary to not hide the language selector, and make the
    // default language of the terms fixed.
    $edit = [
      'default_language[langcode]' => 'bb',
      'default_language[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id());
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'bb')->isSelected());

    // Make the default language of the terms to be the current interface.
    $edit = [
      'default_language[langcode]' => 'current_interface',
      'default_language[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id());
    $this->submitForm($edit, 'Save');
    $this->drupalGet('aa/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'aa')->isSelected());
    $this->drupalGet('bb/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'bb')->isSelected());

    // Change the default language of the site and check if the default terms
    // language is still correctly selected.
    $this->config('system.site')->set('default_langcode', 'cc')->save();
    $edit = [
      'default_language[langcode]' => LanguageInterface::LANGCODE_SITE_DEFAULT,
      'default_language[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id());
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'cc')->isSelected());
  }

  /**
   * Tests that translated terms are displayed correctly on the term overview.
   */
  public function testTermTranslatedOnOverviewPage(): void {
    // Configure the vocabulary to not hide the language selector.
    $edit = [
      'default_language[language_alterable]' => TRUE,
    ];
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id());
    $this->submitForm($edit, 'Save');

    // Add a term.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/add');
    // Submit the term.
    $edit = [
      'name[0][value]' => $this->randomMachineName(),
      'langcode[0][value]' => 'aa',
    ];
    $this->submitForm($edit, 'Save');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'name' => $edit['name[0][value]'],
    ]);
    $term = reset($terms);

    // Add a translation for that term.
    $translated_title = $this->randomMachineName();
    $term->addTranslation('bb', [
      'name' => $translated_title,
    ]);
    $term->save();

    // Overview page in the other language shows the translated term
    $this->drupalGet('bb/admin/structure/taxonomy/manage/' . $this->vocabulary->id() . '/overview');
    $this->assertSession()->responseMatches('|<a[^>]*>' . $translated_title . '</a>|');
  }

}
