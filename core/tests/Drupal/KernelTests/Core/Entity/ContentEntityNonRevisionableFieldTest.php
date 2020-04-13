<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\entity_test\Entity\EntityTestRev;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\system\Functional\Entity\Traits\EntityDefinitionTestTrait;

/**
 * Tests non-revisionable fields on revisionable (and translatable) entities.
 *
 * @group Entity
 */
class ContentEntityNonRevisionableFieldTest extends EntityKernelTestBase {

  use EntityDefinitionTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language'];

  /**
   * The EntityTestMulRev entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mulRev;

  /**
   * The EntityTestRev entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $rev;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('entity_test_rev');
    $this->mulRev = $this->entityTypeManager->getStorage('entity_test_mulrev');
    $this->rev = $this->entityTypeManager->getStorage('entity_test_rev');
  }

  /**
   * Tests non-revisionable fields on revisionable and translatable entities.
   */
  public function testMulNonRevisionableField() {
    $user1 = $this->createUser();
    $user2 = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
      'non_rev_field' => 'Huron',
    ]);
    $entity->save();

    // Create a test entity.
    $entity2 = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
      'non_rev_field' => 'Michigan',
    ]);
    $entity2->save();

    $this->assertEquals('Huron', $entity->get('non_rev_field')->value, 'Huron found on entity 1');
    $this->assertEquals('Michigan', $entity2->get('non_rev_field')->value, 'Michigan found on entity 2');

    $entity->setNewRevision();
    $entity->setOwner($user2);
    $entity->save();
    $entity2->setNewRevision();
    $entity2->setOwner($user2);
    $entity2->save();
    $this->assertEquals($user2->id(), $entity->getOwner()->id(), 'User 2 found on entity 1');
    $this->assertEquals($user2->id(), $entity2->getOwner()->id(), 'User 2 found on entity 2');

    $entity->addTranslation('de');
    $entity->save();
    $entity2->addTranslation('de');
    $entity2->save();

    $expected_revision_ids = [
      4 => 2,
      3 => 1,
      2 => 2,
      1 => 1,
    ];
    $revision_ids = $this->mulRev->getQuery()
      ->allRevisions()
      ->sort('revision_id', 'DESC')
      ->execute();
    $this->assertEquals($expected_revision_ids, $revision_ids, 'Revision ids found');

    $expected_non_rev_field_revision_ids = [
      3 => 1,
      1 => 1,
    ];
    $non_rev_field_revision_ids = $this->mulRev->getQuery()
      ->allRevisions()
      ->condition('non_rev_field', 'Huron')
      ->sort('revision_id', 'DESC')
      ->execute();
    $this->assertEquals($expected_non_rev_field_revision_ids, $non_rev_field_revision_ids, 'Revision ids found');
  }

  /**
   * Tests non-revisionable fields on revisionable entities.
   */
  public function testNonRevisionableField() {
    $user1 = $this->createUser();
    $user2 = $this->createUser();

    // Create a test entity.
    $entity = EntityTestRev::create([
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'non_rev_field' => 'Superior',
    ]);
    $entity->save();

    // Create a test entity.
    $entity2 = EntityTestRev::create([
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'non_rev_field' => 'Ontario',
    ]);
    $entity2->save();

    $this->assertEquals('Superior', $entity->get('non_rev_field')->value, 'Superior found on entity 1');
    $this->assertEquals('Ontario', $entity2->get('non_rev_field')->value, 'Ontario found on entity 2');

    $entity->setNewRevision();
    $entity->setOwner($user2);
    $entity->save();
    $entity2->setNewRevision();
    $entity2->setOwner($user2);
    $entity2->save();
    $this->assertEquals($user2->id(), $entity->getOwner()->id(), 'User 2 found on entity 1');
    $this->assertEquals($user2->id(), $entity2->getOwner()->id(), 'User 2 found on entity 2');

    $expected_revision_ids = [
      4 => 2,
      3 => 1,
      2 => 2,
      1 => 1,
    ];
    $revision_ids = $this->rev->getQuery()
      ->allRevisions()
      ->sort('revision_id', 'DESC')
      ->execute();
    $this->assertEquals($expected_revision_ids, $revision_ids, 'Revision ids found');

    $expected_non_rev_field_revision_ids = [
      3 => 1,
      1 => 1,
    ];
    $non_rev_field_revision_ids = $this->rev->getQuery()
      ->allRevisions()
      ->condition('non_rev_field', 'Superior')
      ->sort('revision_id', 'DESC')
      ->execute();
    $this->assertEquals($expected_non_rev_field_revision_ids, $non_rev_field_revision_ids, 'Revision ids found');
  }

  /**
   * Tests multi column non revisionable base field for revisionable entity.
   */
  public function testMultiColumnNonRevisionableBaseField() {
    \Drupal::state()->set('entity_test.multi_column', TRUE);
    $this->applyEntityUpdates('entity_test_mulrev');
    // Refresh the storage.
    $this->mulRev = $this->entityTypeManager->getStorage('entity_test_mulrev');
    $user1 = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'user_id' => $user1->id(),
      'language' => 'en',
      'non_rev_field' => 'Huron',
      'description' => [
        'shape' => 'shape',
        'color' => 'color',
      ],
    ]);
    $entity->save();
    $entity = $this->mulRev->loadUnchanged($entity->id());
    $expected = [
      [
        'shape' => 'shape',
        'color' => 'color',
      ],
    ];
    $this->assertEquals('Huron', $entity->get('non_rev_field')->value, 'Huron found on entity 1');
    $this->assertEquals($expected, $entity->description->getValue());
  }

}
