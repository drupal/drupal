<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationTestBase;
use Drupal\Tests\language\Traits\LanguageTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Node Translation UI.
 */
#[Group('search')]
#[RunTestsInSeparateProcesses]
class SearchNodeTranslationUITest extends ContentTranslationTestBase {

  use LanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'language',
    'content_translation',
    'node',
    'field_ui',
    'search',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = 'node';
    $this->bundle = 'article';
    parent::setUp();

    // Create the bundle.
    $this->drupalCreateContentType(['type' => 'article', 'title' => 'Article']);
    $this->doSetup();

    $this->drupalLogin($this->translator);
  }

  /**
   * Test deletion of translated content from search and index rebuild.
   */
  public function testSearchIndexRebuildOnTranslationDeletion(): void {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer content types',
      'delete content translations',
      'administer content translation',
      'translate any entity',
      'administer search',
      'search content',
      'delete any article content',
    ]);
    $this->drupalLogin($admin_user);

    // Create a node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
    ]);

    // Add a French translation.
    $translation = $node->addTranslation('fr');
    $translation->title = 'First rev fr title';
    $translation->setNewRevision(FALSE);
    $translation->save();

    // Check if 1 page is listed for indexing.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There is 1 item left to index.');

    // Run cron.
    $this->drupalGet('admin/config/system/cron');
    $this->getSession()->getPage()->pressButton('Run cron');

    // Assert no items are left for indexing.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There are 0 items left to index.');

    // Search for French content.
    $this->drupalGet('search/node', ['query' => ['keys' => urlencode('First rev fr title')]]);
    $this->assertSession()->pageTextContains('First rev fr title');

    // Delete translation.
    $this->drupalGet('fr/node/' . $node->id() . '/delete');
    $this->getSession()->getPage()->pressButton('Delete French translation');

    // Run cron.
    $this->drupalGet('admin/config/system/cron');
    $this->getSession()->getPage()->pressButton('Run cron');

    // Assert no items are left for indexing.
    $this->drupalGet('admin/config/search/pages');
    $this->assertSession()->pageTextContains('There are 0 items left to index.');

    // Search for French content.
    $this->drupalGet('search/node', ['query' => ['keys' => urlencode('First rev fr title')]]);
    $this->assertSession()->pageTextNotContains('First rev fr title');
  }

}
