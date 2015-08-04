<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityRevisionTranslationTest.
 */

namespace Drupal\system\Tests\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests proper revision propagation of entities.
 *
 * @group Entity
 */
class EntityRevisionTranslationTest extends EntityUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();

    $this->installEntitySchema('entity_test_mulrev');
  }

  /**
   * Tests if the translation object has the right revision id after new revision.
   */
  public function testNewRevisionAfterTranslation() {
    $user = $this->createUser();

    // Create a test entity.
    $entity = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'user_id' => $user->id(),
      'language' => 'en',
    ]);
    $entity->save();
    $old_rev_id = $entity->getRevisionId();

    $translation = $entity->addTranslation('de');
    $translation->setNewRevision();
    $translation->save();

    $this->assertTrue($translation->getRevisionId() > $old_rev_id, 'The saved translation in new revision has a newer revision id.');
    $this->assertTrue($this->reloadEntity($entity)->getRevisionId() > $old_rev_id, 'The entity from the storage has a newer revision id.');
  }

}
