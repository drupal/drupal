<?php

namespace Drupal\FunctionalTests\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests revision view page.
 *
 * @group Entity
 * @coversDefaultClass \Drupal\Core\Entity\Controller\EntityRevisionViewController
 */
class RevisionViewTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'entity_test',
    'entity_test_revlog',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests revision page.
   *
   * @param string $entityTypeId
   *   Entity type to test.
   * @param string $expectedPageTitle
   *   Expected page title.
   *
   * @covers ::__invoke
   *
   * @dataProvider providerRevisionPage
   */
  public function testRevisionPage(string $entityTypeId, string $expectedPageTitle): void {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage($entityTypeId);

    // Add a field to test revision page output.
    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => $entityTypeId,
      'field_name' => 'foo',
      'type' => 'string',
    ]);
    $fieldStorage->save();
    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => $entityTypeId,
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository */
    $displayRepository = \Drupal::service('entity_display.repository');
    $displayRepository->getViewDisplay($entityTypeId, $entityTypeId)
      ->setComponent('foo', [
        'type' => 'string',
      ])
      ->save();

    $entity = $storage->create(['type' => $entityTypeId]);
    $entity->setName('revision 1, view revision');
    $revision1Body = $this->randomMachineName();
    $entity->foo = $revision1Body;
    $entity->setNewRevision();
    if ($entity instanceof RevisionLogInterface) {
      $date = new \DateTime('11 January 2009 4:00:00pm');
      $entity->setRevisionCreationTime($date->getTimestamp());
    }
    $entity->save();
    $revisionId = $entity->getRevisionId();

    $entity->setName('revision 2, view revision');
    $revision2Body = $this->randomMachineName();
    $entity->foo = $revision2Body;
    if ($entity instanceof RevisionLogInterface) {
      $entity->setRevisionCreationTime($date->modify('+1 hour')->getTimestamp());
    }
    $entity->setNewRevision();
    $entity->save();

    $revision = $storage->loadRevision($revisionId);
    $this->drupalGet($revision->toUrl('revision'));

    $this->assertSession()->pageTextContains($expectedPageTitle);
    $this->assertSession()->pageTextContains($revision1Body);
    $this->assertSession()->pageTextNotContains($revision2Body);
  }

  /**
   * Data provider for testRevisionPage.
   */
  public function providerRevisionPage(): array {
    return [
      ['entity_test_rev', 'Revision of revision 1, view revision'],
      ['entity_test_revlog', 'Revision of revision 1, view revision from Sun, 01/11/2009 - 16:00'],
    ];
  }

}
