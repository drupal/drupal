<?php

namespace Drupal\Tests\config\Functional;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests configuration entities.
 *
 * @group config
 */
class ConfigDependencyWebTest extends BrowserTestBase {

  /**
   * The maximum length for the entity storage used in this test.
   */
  const MAX_ID_LENGTH = ConfigEntityStorage::MAX_ID_LENGTH;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['config_test'];

  /**
   * Tests ConfigDependencyDeleteFormTrait.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyDeleteFormTrait
   */
  public function testConfigDependencyDeleteFormTrait() {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('config_test');
    // Entity1 will be deleted by the test.
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
        'label' => 'Entity One',
      ]
    );
    $entity1->save();

    // Entity2 has a dependency on Entity1 but it can be fixed because
    // \Drupal\config_test\Entity::onDependencyRemoval() will remove the
    // dependency before config entities are deleted.
    $entity2 = $storage->create(
      [
        'id' => 'entity2',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity2->save();

    $this->drupalGet($entity2->toUrl('delete-form'));
    $this->assertNoText(t('Configuration updates'), 'No configuration updates found.');
    $this->assertNoText(t('Configuration deletions'), 'No configuration deletes found.');
    $this->drupalGet($entity1->toUrl('delete-form'));
    $this->assertNoText(t('Configuration updates'), 'No configuration updates found.');
    $this->assertText(t('Configuration deletions'), 'Configuration deletions found.');
    $this->assertText($entity2->id(), 'Entity2 id found');
    $this->drupalPostForm($entity1->toUrl('delete-form'), [], 'Delete');
    $storage->resetCache();
    $this->assertFalse($storage->loadMultiple([$entity1->id(), $entity2->id()]), 'Test entities deleted');

    // Set a more complicated test where dependencies will be fixed.
    // Entity1 will be deleted by the test.
    $entity1 = $storage->create(
      [
        'id' => 'entity1',
      ]
    );
    $entity1->save();
    \Drupal::state()->set('config_test.fix_dependencies', [$entity1->getConfigDependencyName()]);

    // Entity2 has a dependency on Entity1 but it can be fixed because
    // \Drupal\config_test\Entity::onDependencyRemoval() will remove the
    // dependency before config entities are deleted.
    $entity2 = $storage->create(
      [
        'id' => 'entity2',
        'label' => 'Entity Two',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity1->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity2->save();

    // Entity3 will be unchanged because it is dependent on Entity2 which can
    // be fixed.
    $entity3 = $storage->create(
      [
        'id' => 'entity3',
        'dependencies' => [
          'enforced' => [
            'config' => [$entity2->getConfigDependencyName()],
          ],
        ],
      ]
    );
    $entity3->save();

    $this->drupalGet($entity1->toUrl('delete-form'));
    $this->assertText(t('Configuration updates'), 'Configuration updates found.');
    $this->assertNoText(t('Configuration deletions'), 'No configuration deletions found.');
    $this->assertNoText($entity2->id(), 'Entity2 id not found');
    $this->assertText($entity2->label(), 'Entity2 label not found');
    $this->assertNoText($entity3->id(), 'Entity3 id not found');
    $this->drupalPostForm($entity1->toUrl('delete-form'), [], 'Delete');
    $storage->resetCache();
    $this->assertFalse($storage->load('entity1'), 'Test entity 1 deleted');
    $entity2 = $storage->load('entity2');
    $this->assertTrue($entity2, 'Entity 2 not deleted');
    $this->assertEquals([], $entity2->calculateDependencies()->getDependencies()['config'], 'Entity 2 dependencies updated to remove dependency on Entity1.');
    $entity3 = $storage->load('entity3');
    $this->assertTrue($entity3, 'Entity 3 not deleted');
    $this->assertEquals([$entity2->getConfigDependencyName()], $entity3->calculateDependencies()->getDependencies()['config'], 'Entity 3 still depends on Entity 2.');

  }

}
