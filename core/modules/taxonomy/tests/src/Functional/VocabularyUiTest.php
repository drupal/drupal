<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the taxonomy vocabulary interface.
 *
 * @group taxonomy
 */
class VocabularyUiTest extends TaxonomyTestBase {

  /**
   * The vocabulary used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(['administer taxonomy']));
    $this->vocabulary = $this->createVocabulary();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Create, edit and delete a vocabulary via the user interface.
   */
  public function testVocabularyInterface(): void {
    // Visit the main taxonomy administration page.
    $this->drupalGet('admin/structure/taxonomy');

    // Create a new vocabulary.
    $this->clickLink('Add vocabulary');
    $edit = [];
    $vid = $this->randomMachineName();
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $edit['vid'] = $vid;
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains("Created new vocabulary {$edit['name']}.");

    // Edit the vocabulary.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertSession()->pageTextContains($edit['name']);
    $this->assertSession()->pageTextContains($edit['description']);
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.taxonomy_term.add_form', ['taxonomy_vocabulary' => $edit['vid']])->toString());
    $this->clickLink('Edit vocabulary');
    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['description'] = $this->randomMachineName();
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertSession()->pageTextContains($edit['name']);
    $this->assertSession()->pageTextContains($edit['description']);

    // Try to submit a vocabulary with a duplicate machine name.
    $edit['vid'] = $vid;
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Try to submit an invalid machine name.
    $edit['vid'] = '!&^%';
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name must contain only lowercase letters, numbers, and underscores.');

    // Ensure that vocabulary titles are escaped properly.
    $edit = [];
    $edit['name'] = 'Don\'t Panic';
    $edit['description'] = $this->randomMachineName();
    $edit['vid'] = 'don_t_panic';
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->submitForm($edit, 'Save');

    $site_name = $this->config('system.site')->get('name');
    $this->assertSession()->titleEquals("Don't Panic | $site_name");

    // Delete the vocabulary.
    $this->drupalGet('admin/structure/taxonomy');
    $href = Url::fromRoute('entity.taxonomy_vocabulary.delete_form', ['taxonomy_vocabulary' => $edit['vid']])->toString();
    $xpath = $this->assertSession()->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $href]);
    $link = $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertEquals('Delete vocabulary', $link->getText());
    $link->click();

    // Confirm deletion.
    $this->assertSession()->responseContains(new FormattableMarkup('Are you sure you want to delete the vocabulary %name?', ['%name' => $edit['name']]));
    $this->submitForm([], 'Delete');
    $this->assertSession()->responseContains(new FormattableMarkup('Deleted vocabulary %name.', ['%name' => $edit['name']]));
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $this->assertNull(Vocabulary::load($edit['vid']), 'Vocabulary not found.');
  }

  /**
   * Changing weights on the vocabulary overview with two or more vocabularies.
   */
  public function testTaxonomyAdminChangingWeights(): void {
    // Create some vocabularies.
    for ($i = 0; $i < 10; $i++) {
      $this->createVocabulary();
    }
    // Get all vocabularies and change their weights.
    $vocabularies = Vocabulary::loadMultiple();
    $edit = [];
    foreach ($vocabularies as $key => $vocabulary) {
      $weight = -$vocabulary->get('weight');
      $vocabularies[$key]->set('weight', $weight);
      $edit['vocabularies[' . $key . '][weight]'] = $weight;
    }
    // Saving the new weights via the interface.
    $this->drupalGet('admin/structure/taxonomy');
    $this->submitForm($edit, 'Save');

    // Load the vocabularies from the database.
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $new_vocabularies = Vocabulary::loadMultiple();

    // Check that the weights are saved in the database correctly.
    foreach ($vocabularies as $key => $vocabulary) {
      $this->assertEquals($new_vocabularies[$key]->get('weight'), $vocabularies[$key]->get('weight'), 'The vocabulary weight was changed.');
    }
  }

  /**
   * Tests the vocabulary overview with no vocabularies.
   */
  public function testTaxonomyAdminNoVocabularies(): void {
    // Delete all vocabularies.
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $key => $vocabulary) {
      $vocabulary->delete();
    }
    // Confirm that no vocabularies are found in the database.
    $this->assertEmpty(Vocabulary::loadMultiple(), 'No vocabularies found.');
    $this->drupalGet('admin/structure/taxonomy');
    // Check the default message for no vocabularies.
    $this->assertSession()->pageTextContains('No vocabularies available.');
  }

  /**
   * Deleting a vocabulary.
   */
  public function testTaxonomyAdminDeletingVocabulary(): void {
    // Create a vocabulary.
    $vid = $this->randomMachineName();
    $edit = [
      'name' => $this->randomMachineName(),
      'vid' => $vid,
    ];
    $this->drupalGet('admin/structure/taxonomy/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Created new vocabulary');

    // Check the created vocabulary.
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $vocabulary = Vocabulary::load($vid);
    $this->assertNotEmpty($vocabulary, 'Vocabulary found.');

    // Delete the vocabulary.
    $this->drupalGet('admin/structure/taxonomy/manage/' . $vocabulary->id());
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains("Are you sure you want to delete the vocabulary {$vocabulary->label()}?");
    $this->assertSession()->pageTextContains('Deleting a vocabulary will delete all the terms in it. This action cannot be undone.');

    // Confirm deletion.
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("Deleted vocabulary {$vocabulary->label()}.");
    $this->container->get('entity_type.manager')->getStorage('taxonomy_vocabulary')->resetCache();
    $this->assertNull(Vocabulary::load($vid), 'Vocabulary not found.');
  }

}
