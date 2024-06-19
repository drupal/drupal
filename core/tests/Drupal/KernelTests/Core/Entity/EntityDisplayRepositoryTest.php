<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\KernelTests\KernelTestBase;

// cspell:ignore pastafazoul

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityDisplayRepository
 *
 * @group Entity
 */
class EntityDisplayRepositoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

  /**
   * The entity display repository under test.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->displayRepository = \Drupal::service('entity_display.repository');

    // Create a new view mode for users.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_view_mode')
      ->create([
        'id' => 'user.pastafazoul',
        'label' => $this->randomMachineName(),
        'targetEntityType' => 'user',
      ])
      ->save();

    // Create a new form mode for users.
    $this->container->get('entity_type.manager')
      ->getStorage('entity_form_mode')
      ->create([
        'id' => 'user.register',
        'label' => $this->randomMachineName(),
        'targetEntityType' => 'user',
      ])
      ->save();
  }

  /**
   * @covers ::getViewDisplay
   */
  public function testViewDisplay(): void {
    $display = $this->displayRepository->getViewDisplay('user', 'user');
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $display);
    $this->assertTrue($display->isNew(), 'Default view display was created on demand.');
    $this->assertSame(EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE, $display->getMode());

    $display->createCopy('pastafazoul')->save();

    $display = $this->displayRepository->getViewDisplay('user', 'user', 'pastafazoul');
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $display);
    $this->assertFalse($display->isNew(), 'An existing view display was loaded.');
    $this->assertSame('pastafazoul', $display->getMode());

    $display = $this->displayRepository->getViewDisplay('user', 'user', 'magic');
    $this->assertInstanceOf(EntityViewDisplayInterface::class, $display);
    $this->assertTrue($display->isNew(), 'A new non-default view display was created on demand.');
    $this->assertSame('magic', $display->getMode());
  }

  /**
   * @covers ::getFormDisplay
   */
  public function testFormDisplay(): void {
    $display = $this->displayRepository->getFormDisplay('user', 'user');
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $display);
    $this->assertTrue($display->isNew(), 'Default form display was created on demand.');
    $this->assertSame(EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE, $display->getMode());

    $display->createCopy('register')->save();

    $display = $this->displayRepository->getFormDisplay('user', 'user', 'register');
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $display);
    $this->assertFalse($display->isNew(), 'An existing form display was loaded.');
    $this->assertSame('register', $display->getMode());

    $display = $this->displayRepository->getFormDisplay('user', 'user', 'magic');
    $this->assertInstanceOf(EntityFormDisplayInterface::class, $display);
    $this->assertTrue($display->isNew(), 'A new non-default form display was created on demand.');
    $this->assertSame('magic', $display->getMode());
  }

}
