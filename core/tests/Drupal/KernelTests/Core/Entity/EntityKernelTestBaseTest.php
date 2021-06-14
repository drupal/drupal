<?php

namespace Drupal\KernelTests\Core\Entity;

/**
 * @covers \Drupal\KernelTests\Core\Entity\EntityKernelTestBase
 *
 * @group Entity
 */
class EntityKernelTestBaseTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createUser();
  }

  /**
   * Tests that the current user is set up correctly.
   */
  public function testSetUpCurrentUser() {
    $account = $this->setUpCurrentUser();
    $current_user = \Drupal::currentUser();
    $this->assertSame($account->id(), $current_user->id());
  }

  /**
   * Ensure that calls to enable or disable modules result in our getting
   * updated references to the DI container's services.
   */
  public function testEnsureContainerIntegrity() {
    $storage = new \SplObjectStorage();
    $this->disableModules(['user']);
    $storage->attach($this->entityTypeManager);
    $this->assertTrue($storage->contains(\Drupal::entityTypeManager()));
  }

}
