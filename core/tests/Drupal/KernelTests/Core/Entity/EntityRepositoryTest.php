<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the entity repository.
 *
 * @group Entity
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityRepository
 */
class EntityRepositoryTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
    'language',
    'system',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityRepository = $this->container->get('entity.repository');

    $this->setUpCurrentUser();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_rev');
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mulrev');

    $this->installConfig(['system', 'language']);
    ConfigurableLanguage::createFromLangcode('it')
      ->setWeight(1)
      ->save();
    ConfigurableLanguage::createFromLangcode('ro')
      ->setWeight(2)
      ->save();

    $this->container->get('state')->set('entity_test.translation', TRUE);
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();
  }

  /**
   * Tests retrieving active variants.
   *
   * @covers ::getActive
   * @covers ::getActiveMultiple
   */
  public function testGetActive() {
    $en_contexts = $this->getLanguageContexts('en');

    // Check that when the entity does not exist NULL is returned.
    $entity_type_id = 'entity_test';
    $active = $this->entityRepository->getActive($entity_type_id, -1);
    $this->assertNull($active);

    // Check that the correct active variant is returned for a non-translatable,
    // non-revisionable entity.
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create($values);
    $storage->save($entity);
    $entity = $storage->load($entity->id());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $active */
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($entity, $active);

    // Check that the correct active variant is returned for a non-translatable
    // revisionable entity.
    $entity_type_id = 'entity_test_rev';
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    $entity = $storage->create($values);
    $storage->save($entity);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
    $revision = $storage->createRevision($entity, FALSE);
    $revision->save();
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertEntityType($active, $entity_type_id);
    $this->assertSame($revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision2 */
    $revision2 = $storage->createRevision($revision);
    $revision2->save();
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($revision2->getLoadedRevisionId(), $active->getLoadedRevisionId());

    // Check that the correct active variant is returned for a translatable
    // non-revisionable entity.
    $entity_type_id = 'entity_test_mul';
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    $entity = $storage->create($values);
    $storage->save($entity);

    $langcode = 'it';
    /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
    $translation = $entity->addTranslation($langcode, $values);
    $storage->save($translation);
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertEntityType($active, $entity_type_id);
    $this->assertSame($entity->language()->getId(), $active->language()->getId());

    $it_contexts = $this->getLanguageContexts($langcode);
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $it_contexts);
    $this->assertSame($translation->language()->getId(), $active->language()->getId());

    // Check that the correct active variant is returned for a translatable and
    // revisionable entity.
    $entity_type_id = 'entity_test_mulrev';
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    $entity = $storage->create($values);
    $storage->save($entity);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $en_revision */
    $en_revision = $storage->createRevision($entity, FALSE);
    $storage->save($en_revision);
    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertEntityType($active, $entity_type_id);
    $this->assertSame($en_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());

    $revision_translation = $en_revision->addTranslation($langcode, $values);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision */
    $it_revision = $storage->createRevision($revision_translation, FALSE);
    $storage->save($it_revision);

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($en_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($en_revision->language()->getId(), $active->language()->getId());

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $it_contexts);
    $this->assertSame($it_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($it_revision->language()->getId(), $active->language()->getId());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $en_revision2 */
    $en_revision2 = $storage->createRevision($en_revision);
    $storage->save($en_revision2);

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($en_revision2->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($en_revision2->language()->getId(), $active->language()->getId());

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $it_contexts);
    $this->assertSame($it_revision->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($it_revision->language()->getId(), $active->language()->getId());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision2 */
    $it_revision2 = $storage->createRevision($it_revision);
    $storage->save($it_revision2);

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $en_contexts);
    $this->assertSame($it_revision2->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($it_revision2->getUntranslated()->language()->getId(), $active->language()->getId());

    $active = $this->entityRepository->getActive($entity_type_id, $entity->id(), $it_contexts);
    $this->assertSame($it_revision2->getLoadedRevisionId(), $active->getLoadedRevisionId());
    $this->assertSame($it_revision2->language()->getId(), $active->language()->getId());

    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $entity2 */
    $entity2 = $storage->create($values);
    $storage->save($entity2);
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $active */
    $active = $this->entityRepository->getActiveMultiple($entity_type_id, [$entity->id(), $entity2->id()], $it_contexts);
    $this->assertSame($it_revision2->getLoadedRevisionId(), $active[$entity->id()]->getLoadedRevisionId());
    $this->assertSame($it_revision2->language()->getId(), $active[$entity->id()]->language()->getId());
    $this->assertSame($entity2->getLoadedRevisionId(), $active[$entity2->id()]->getLoadedRevisionId());
    $this->assertSame($entity2->language()->getId(), $active[$entity2->id()]->language()->getId());

    $this->doTestLanguageFallback('getActive');
  }

  /**
   * Tests retrieving canonical variants.
   *
   * @covers ::getCanonical
   * @covers ::getCanonicalMultiple
   */
  public function testGetCanonical() {
    // Check that when the entity does not exist NULL is returned.
    $entity_type_id = 'entity_test_mul';
    $canonical = $this->entityRepository->getActive($entity_type_id, -1);
    $this->assertNull($canonical);

    // Check that the correct language fallback rules are applied.
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->create($values);
    $storage->save($entity);

    $langcode = 'it';
    $it_contexts = $this->getLanguageContexts($langcode);
    $canonical = $this->entityRepository->getCanonical($entity_type_id, $entity->id(), $it_contexts);
    $this->assertSame($entity->getUntranslated()->language()->getId(), $canonical->language()->getId());

    /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
    $translation = $entity->addTranslation($langcode, $values);
    $storage->save($translation);
    $canonical = $this->entityRepository->getCanonical($entity_type_id, $entity->id(), $it_contexts);
    $this->assertSame($translation->language()->getId(), $canonical->language()->getId());

    $canonical = $this->entityRepository->getCanonical($entity_type_id, $entity->id());
    $this->assertSame($entity->getUntranslated()->language()->getId(), $canonical->language()->getId());

    /** @var \Drupal\entity_test\Entity\EntityTestMul $entity2 */
    $entity2 = $storage->create($values);
    $storage->save($entity2);
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $canonical */
    $canonical = $this->entityRepository->getCanonicalMultiple($entity_type_id, [$entity->id(), $entity2->id()], $it_contexts);
    $this->assertSame($translation->language()->getId(), $canonical[$entity->id()]->language()->getId());
    $this->assertSame($entity2->language()->getId(), $canonical[$entity2->id()]->language()->getId());

    $this->doTestLanguageFallback('getCanonical');
  }

  /**
   * Check that language fallback is applied.
   *
   * @param string $method_name
   *   An entity repository method name.
   */
  protected function doTestLanguageFallback($method_name) {
    $entity_type_id = 'entity_test_mulrev';
    $en_contexts = $this->getLanguageContexts('en');
    $it_contexts = $this->getLanguageContexts('it');
    $ro_contexts = $this->getLanguageContexts('ro');

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $values = ['name' => $this->randomString()];

    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $entity3 */
    $entity3 = $storage->create(['langcode' => 'it'] + $values);
    $entity3->addTranslation('ro', $values);
    $storage->save($entity3);
    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $active */
    $active = $this->entityRepository->{$method_name}($entity_type_id, $entity3->id(), $en_contexts);
    $this->assertSame('it', $active->language()->getId());

    $active = $this->entityRepository->{$method_name}($entity_type_id, $entity3->id(), $ro_contexts);
    $this->assertSame('ro', $active->language()->getId());

    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $entity4 */
    $entity4 = $storage->create(['langcode' => 'ro'] + $values);
    $entity4->addTranslation('en', $values);
    $storage->save($entity4);
    $active = $this->entityRepository->{$method_name}($entity_type_id, $entity4->id(), $it_contexts);
    $this->assertSame('en', $active->language()->getId());

    /** @var \Drupal\entity_test\Entity\EntityTestMulRev $entity5 */
    $entity5 = $storage->create(['langcode' => 'ro'] + $values);
    $storage->save($entity5);
    $active = $this->entityRepository->{$method_name}($entity_type_id, $entity5->id(), $it_contexts);
    $this->assertSame('ro', $active->language()->getId());
    $active = $this->entityRepository->{$method_name}($entity_type_id, $entity5->id(), $en_contexts);
    $this->assertSame('ro', $active->language()->getId());
  }

  /**
   * Asserts that the entity has the expected entity type ID.
   *
   * @param object|null $entity
   *   An entity object or NULL.
   * @param string $expected_entity_type_id
   *   The expected entity type ID.
   */
  protected function assertEntityType($entity, $expected_entity_type_id) {
    $this->assertInstanceOf(EntityTest::class, $entity);
    $this->assertEquals($expected_entity_type_id, $entity->getEntityTypeId());
  }

  /**
   * Returns a set of language contexts matching the specified language.
   *
   * @param string $langcode
   *   A language code.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of contexts.
   */
  protected function getLanguageContexts($langcode) {
    $prefix = '@language.current_language_context:';
    return [
      $prefix . LanguageInterface::TYPE_INTERFACE => new Context(new ContextDefinition('language'), $langcode),
      $prefix . LanguageInterface::TYPE_CONTENT => new Context(new ContextDefinition('language'), $langcode),
    ];
  }

}
