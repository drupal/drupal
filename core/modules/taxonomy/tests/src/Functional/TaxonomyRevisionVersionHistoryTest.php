<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Taxonomy term version history test.
 *
 * @group taxonomy
 * @coversDefaultClass \Drupal\Core\Entity\Controller\VersionHistoryController
 */
class TaxonomyRevisionVersionHistoryTest extends BrowserTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'view term revisions in test',
    'revert all taxonomy revisions',
    'delete all taxonomy revisions',
  ];

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  private $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->vocabulary = $this->createVocabulary(['vid' => 'test', 'name' => 'Test']);
  }

  /**
   * Tests version history page.
   */
  public function testVersionHistory(): void {
    $entity = Term::create([
      'vid' => $this->vocabulary->id(),
      'name' => 'Test taxonomy term',
    ]);

    $entity
      ->setDescription('Description 1')
      ->setRevisionCreationTime((new \DateTimeImmutable('1st June 2020 7am'))->getTimestamp())
      ->setRevisionLogMessage('first revision log')
      ->setRevisionUser($this->drupalCreateUser(name: 'first author'))
      ->setNewRevision();
    $entity->save();

    $entity
      ->setDescription('Description 2')
      ->setRevisionCreationTime((new \DateTimeImmutable('2nd June 2020 8am'))->getTimestamp())
      ->setRevisionLogMessage('second revision log')
      ->setRevisionUser($this->drupalCreateUser(name: 'second author'))
      ->setNewRevision();
    $entity->save();

    $entity
      ->setDescription('Description 3')
      ->setRevisionCreationTime((new \DateTimeImmutable('3rd June 2020 9am'))->getTimestamp())
      ->setRevisionLogMessage('third revision log')
      ->setRevisionUser($this->drupalCreateUser(name: 'third author'))
      ->setNewRevision();
    $entity->save();

    $this->drupalLogin($this->drupalCreateUser($this->permissions));
    $this->drupalGet($entity->toUrl('version-history'));
    $this->assertSession()->elementsCount('css', 'table tbody tr', 3);

    // Order is newest to oldest revision by creation order.
    $row1 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(1)');
    // Latest revision does not have revert or delete revision operation.
    $this->assertSession()->elementNotExists('named', ['link', 'Revert'], $row1);
    $this->assertSession()->elementNotExists('named', ['link', 'Delete'], $row1);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', 'Current revision');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', 'third revision log');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '3 Jun 2020 - 09:00 by third author');

    $row2 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(2)');
    $this->assertSession()->elementExists('named', ['link', 'Revert'], $row2);
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $row2);
    $this->assertSession()->elementTextNotContains('css', 'table tbody tr:nth-child(2)', 'Current revision');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', 'second revision log');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '2 Jun 2020 - 08:00 by second author');

    $row3 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(3)');
    $this->assertSession()->elementExists('named', ['link', 'Revert'], $row3);
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $row3);
    $this->assertSession()->elementTextNotContains('css', 'table tbody tr:nth-child(2)', 'Current revision');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(3)', 'first revision log');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(3)', '1 Jun 2020 - 07:00 by first author');
  }

}
