<?php

namespace Drupal\Tests\system\Functional\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Create a entity with revisions and test viewing, saving, reverting, and
 * deleting revisions.
 *
 * @group Entity
 */
class EntityRevisionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'language'];

  /**
   * A user with permission to administer entity_test content.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  protected function setUp() {
    parent::setUp();

    // Create and log in user.
    $this->webUser = $this->drupalCreateUser([
      'administer entity_test content',
      'view test entity',
    ]);
    $this->drupalLogin($this->webUser);

    // Enable an additional language.
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Check node revision related operations.
   */
  public function testRevisions() {

    // All revisable entity variations have to have the same results.
    foreach (entity_test_entity_types(ENTITY_TEST_TYPES_REVISABLE) as $entity_type) {
      $this->runRevisionsTests($entity_type);
    }
  }

  /**
   * Executes the revision tests for the given entity type.
   *
   * @param string $entity_type
   *   The entity type to run the tests with.
   */
  protected function runRevisionsTests($entity_type) {

    // Create initial entity.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create([
        'name' => 'foo',
        'user_id' => $this->webUser->id(),
      ]);
    $entity->field_test_text->value = 'bar';
    $entity->save();

    $names = [];
    $texts = [];
    $created = [];
    $revision_ids = [];

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $legacy_revision_id = $entity->revision_id->value;
      $legacy_name = $entity->name->value;
      $legacy_text = $entity->field_test_text->value;

      $entity = $this->container->get('entity_type.manager')
        ->getStorage($entity_type)->load($entity->id->value);
      $entity->setNewRevision(TRUE);
      $names[] = $entity->name->value = $this->randomMachineName(32);
      $texts[] = $entity->field_test_text->value = $this->randomMachineName(32);
      $created[] = $entity->created->value = time() + $i + 1;
      $entity->save();
      $revision_ids[] = $entity->revision_id->value;

      // Check that the fields and properties contain new content.
      $this->assertTrue($entity->revision_id->value > $legacy_revision_id, format_string('%entity_type: Revision ID changed.', ['%entity_type' => $entity_type]));
      $this->assertNotEqual($entity->name->value, $legacy_name, format_string('%entity_type: Name changed.', ['%entity_type' => $entity_type]));
      $this->assertNotEqual($entity->field_test_text->value, $legacy_text, format_string('%entity_type: Text changed.', ['%entity_type' => $entity_type]));
    }

    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type);
    for ($i = 0; $i < $revision_count; $i++) {
      // Load specific revision.
      $entity_revision = $storage->loadRevision($revision_ids[$i]);

      // Check if properties and fields contain the revision specific content.
      $this->assertEqual($entity_revision->revision_id->value, $revision_ids[$i], format_string('%entity_type: Revision ID matches.', ['%entity_type' => $entity_type]));
      $this->assertEqual($entity_revision->name->value, $names[$i], format_string('%entity_type: Name matches.', ['%entity_type' => $entity_type]));
      $this->assertEqual($entity_revision->field_test_text->value, $texts[$i], format_string('%entity_type: Text matches.', ['%entity_type' => $entity_type]));

      // Check non-revisioned values are loaded.
      $this->assertTrue(isset($entity_revision->created->value), format_string('%entity_type: Non-revisioned field is loaded.', ['%entity_type' => $entity_type]));
      $this->assertEqual($entity_revision->created->value, $created[2], format_string('%entity_type: Non-revisioned field value is the same between revisions.', ['%entity_type' => $entity_type]));
    }

    // Confirm the correct revision text appears in the edit form.
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->load($entity->id->value);
    $this->drupalGet($entity_type . '/manage/' . $entity->id->value . '/edit');
    $this->assertFieldById('edit-name-0-value', $entity->name->value, format_string('%entity_type: Name matches in UI.', ['%entity_type' => $entity_type]));
    $this->assertFieldById('edit-field-test-text-0-value', $entity->field_test_text->value, format_string('%entity_type: Text matches in UI.', ['%entity_type' => $entity_type]));
  }

  /**
   * Tests that an entity revision is upcasted in the correct language.
   */
  public function testEntityRevisionParamConverter() {
    // Create a test entity with multiple revisions and translations for them.
    $entity = EntityTestMulRev::create([
      'name' => 'default revision - en',
      'user_id' => $this->webUser,
      'language' => 'en',
    ]);
    $entity->addTranslation('de', ['name' => 'default revision - de']);
    $entity->save();

    $pending_revision = \Drupal::entityTypeManager()->getStorage('entity_test_mulrev')->loadUnchanged($entity->id());

    $pending_revision->setNewRevision();
    $pending_revision->isDefaultRevision(FALSE);

    $pending_revision->name = 'pending revision - en';
    $pending_revision->save();

    $pending_revision_translation = $pending_revision->getTranslation('de');
    $pending_revision_translation->name = 'pending revision - de';
    $pending_revision_translation->save();

    // Check that the entity revision is upcasted in the correct language.
    $revision_url = 'entity_test_mulrev/' . $entity->id() . '/revision/' . $pending_revision->getRevisionId() . '/view';

    $this->drupalGet($revision_url);
    $this->assertText('pending revision - en');
    $this->assertNoText('pending revision - de');

    $this->drupalGet('de/' . $revision_url);
    $this->assertText('pending revision - de');
    $this->assertNoText('pending revision - en');
  }

  /**
   * Tests manual revert of the revision ID value.
   *
   * @covers \Drupal\Core\Entity\ContentEntityBase::getRevisionId
   * @covers \Drupal\Core\Entity\ContentEntityBase::getLoadedRevisionId
   * @covers \Drupal\Core\Entity\ContentEntityBase::setNewRevision
   * @covers \Drupal\Core\Entity\ContentEntityBase::isNewRevision
   */
  public function testNewRevisionRevert() {
    $entity = EntityTestMulRev::create(['name' => 'EntityLoadedRevisionTest']);
    $entity->save();

    // Check that revision ID field is reset while the loaded revision ID is
    // preserved when flagging a new revision.
    $revision_id = $entity->getRevisionId();
    $entity->setNewRevision();
    $this->assertNull($entity->getRevisionId());
    $this->assertEquals($revision_id, $entity->getLoadedRevisionId());
    $this->assertTrue($entity->isNewRevision());

    // Check that after manually restoring the original revision ID, the entity
    // is stored without creating a new revision.
    $key = $entity->getEntityType()->getKey('revision');
    $entity->set($key, $revision_id);
    $entity->save();
    $this->assertEquals($revision_id, $entity->getRevisionId());
    $this->assertEquals($revision_id, $entity->getLoadedRevisionId());

    // Check that manually restoring the original revision ID causes the "new
    // revision" state to be reverted.
    $entity->setNewRevision();
    $this->assertNull($entity->getRevisionId());
    $this->assertEquals($revision_id, $entity->getLoadedRevisionId());
    $this->assertTrue($entity->isNewRevision());
    $entity->set($key, $revision_id);
    $this->assertFalse($entity->isNewRevision());
    $this->assertEquals($revision_id, $entity->getRevisionId());
    $this->assertEquals($revision_id, $entity->getLoadedRevisionId());

    // Check that flagging a new revision again works correctly.
    $entity->setNewRevision();
    $this->assertNull($entity->getRevisionId());
    $this->assertEquals($revision_id, $entity->getLoadedRevisionId());
    $this->assertTrue($entity->isNewRevision());
  }

}
