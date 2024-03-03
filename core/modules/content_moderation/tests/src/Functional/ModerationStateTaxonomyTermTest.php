<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the taxonomy term moderation handler.
 *
 * @group content_moderation
 */
class ModerationStateTaxonomyTermTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a "Tags" vocabulary.
    $bundle = Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
      'new_revision' => FALSE,
    ])->save();
  }

  /**
   * Tests the taxonomy term moderation handler alters the forms as intended.
   *
   * @covers \Drupal\content_moderation\Entity\Handler\TaxonomyTermModerationHandler::enforceRevisionsEntityFormAlter
   * @covers \Drupal\content_moderation\Entity\Handler\TaxonomyTermModerationHandler::enforceRevisionsBundleFormAlter
   */
  public function testEnforceRevisionsEntityFormAlter(): void {
    $this->drupalLogin($this->adminUser);

    // Enable moderation for the tags vocabulary.
    $edit['bundles[tags]'] = TRUE;
    $this->drupalGet('/admin/config/workflow/workflows/manage/editorial/type/taxonomy_term');
    $this->submitForm($edit, 'Save');

    // Check that revision is checked by default when content moderation is
    // enabled for the vocabulary.
    $this->drupalGet('/admin/structure/taxonomy/manage/tags');
    $this->assertSession()->checkboxChecked('revision');
    $this->assertSession()->pageTextContains('Revisions must be required when moderation is enabled.');
    $this->assertSession()->fieldDisabled('revision');

    // Create a taxonomy term and save it as draft.
    $term = Term::create([
      'name' => 'Test tag',
      'vid' => 'tags',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $term->save();

    // Check that revision is checked by default when editing a term and
    // content moderation is enabled for the term's vocabulary.
    $this->drupalGet($term->toUrl('edit-form'));
    $this->assertSession()->checkboxChecked('revision');
    $this->assertSession()->pageTextContains('Revisions must be required when moderation is enabled.');
    $this->assertSession()->fieldDisabled('revision');
  }

}
