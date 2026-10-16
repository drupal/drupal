<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Form\BundleEntityDeleteForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the bundle deletion form.
 */
#[CoversClass(BundleEntityDeleteForm::class)]
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class BundleDeleteFormTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'entity_test',
  ];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager->getStorage('entity_test_bundle')->create([
      'label' => 'Test bundle',
      'id' => 'test',
    ])->save();

    // Create a user who can see the bundle delete form.
    $user = $this->createUser([
      'administer entity_test_bundle content',
      'administer entity_test_with_bundle content',
    ]);
    $this->setCurrentUser($user);
  }

  /**
   * Tests that deleting a bundle with content entities is not allowed.
   */
  public function testBundleEntityDeleteForm(): void {
    $test_entity = $this->entityTypeManager->getStorage('entity_test_with_bundle')->create([
      'type' => 'test',
    ]);
    $test_entity->save();

    // Attempt to delete the bundle, which should not be allowed.
    $this->drupalGet('/entity_test_bundle/delete/test');
    $this->assertSession()->pageTextContains('Test bundle is used by 1 test entity with bundle on your site. You may not remove this test entity bundle until you have removed all of the Test bundle test entity with bundle entities.');
    $this->assertSession()->pageTextNotContains('This action cannot be undone.');

    // Delete the entity.
    $test_entity->delete();

    // Attempt to delete the bundle, which should now be allowed.
    $this->drupalGet('/entity_test_bundle/delete/test');
    $this->assertSession()->pageTextContains('This action cannot be undone.');
  }

}
