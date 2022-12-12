<?php

declare(strict_types = 1);

namespace Drupal\Tests\block_content\Functional;

/**
 * Block content version history test.
 *
 * @group block_content
 * @coversDefaultClass \Drupal\Core\Entity\Controller\VersionHistoryController
 */
class BlockContentRevisionVersionHistoryTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $permissions = [
    'view any basic block content history',
    'revert any basic block content revisions',
    'delete any basic block content revisions',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests version history page.
   */
  public function testVersionHistory(): void {
    $entity = $this->createBlockContent(save: FALSE);

    $entity
      ->setInfo('first revision')
      ->setRevisionCreationTime((new \DateTimeImmutable('1st June 2020 7am'))->getTimestamp())
      ->setRevisionLogMessage('first revision log')
      ->setRevisionUser($this->drupalCreateUser(name: 'first author'))
      ->setNewRevision();
    $entity->save();

    $entity
      ->setInfo('second revision')
      ->setRevisionCreationTime((new \DateTimeImmutable('2nd June 2020 8am'))->getTimestamp())
      ->setRevisionLogMessage('second revision log')
      ->setRevisionUser($this->drupalCreateUser(name: 'second author'))
      ->setNewRevision();
    $entity->save();

    $entity
      ->setInfo('third revision')
      ->setRevisionCreationTime((new \DateTimeImmutable('3rd June 2020 9am'))->getTimestamp())
      ->setRevisionLogMessage('third revision log')
      ->setRevisionUser($this->drupalCreateUser(name: 'third author'))
      ->setNewRevision();
    $entity->save();

    $this->drupalGet($entity->toUrl('version-history'));
    $this->assertSession()->elementsCount('css', 'table tbody tr', 3);

    // Order is newest to oldest revision by creation order.
    $row1 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(1)');
    // Latest revision does not have revert or delete revision operation.
    $this->assertSession()->elementNotExists('named', ['link', 'Revert'], $row1);
    $this->assertSession()->elementNotExists('named', ['link', 'Delete'], $row1);
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', 'Current revision');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', 'third revision log');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(1)', '06/03/2020 - 09:00 by third author');

    $row2 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(2)');
    $this->assertSession()->elementExists('named', ['link', 'Revert'], $row2);
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $row2);
    $this->assertSession()->elementTextNotContains('css', 'table tbody tr:nth-child(2)', 'Current revision');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', 'second revision log');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(2)', '06/02/2020 - 08:00 by second author');

    $row3 = $this->assertSession()->elementExists('css', 'table tbody tr:nth-child(3)');
    $this->assertSession()->elementExists('named', ['link', 'Revert'], $row3);
    $this->assertSession()->elementExists('named', ['link', 'Delete'], $row3);
    $this->assertSession()->elementTextNotContains('css', 'table tbody tr:nth-child(2)', 'Current revision');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(3)', 'first revision log');
    $this->assertSession()->elementTextContains('css', 'table tbody tr:nth-child(3)', '06/01/2020 - 07:00 by first author');
  }

}
