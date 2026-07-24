<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\layout_builder\Form\RevertOverridesForm;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Layout Builder revert overrides form.
 */
#[CoversClass(RevertOverridesForm::class)]
#[Group('layout_builder')]
class RevertOverridesFormTest extends UnitTestCase {

  /**
   * Tests ::getDescription() with entity, NULL, and ContextException cases.
   */
  public function testGetDescription(): void {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $form = new RevertOverridesForm(
      $this->prophesize(LayoutTempstoreRepositoryInterface::class)->reveal(),
      $this->prophesize(MessengerInterface::class)->reveal(),
    );

    $reflection = new \ReflectionProperty(RevertOverridesForm::class, 'sectionStorage');
    $default = 'The layout will be reverted to its default state. All layout modifications and inline blocks will be reset.';

    // Test entity label.
    $entity = $this->prophesize(EntityInterface::class);
    $entity->label()->willReturn('My Node');
    $section_storage = $this->prophesize(OverridesSectionStorageInterface::class);
    $section_storage->getContextValue('entity')->willReturn($entity->reveal());
    $reflection->setValue($form, $section_storage->reveal());
    $this->assertSame(
      'The layout for <em class="placeholder">My Node</em> will be reverted to its default state. All layout modifications and inline blocks will be reset.',
      (string) $form->getDescription(),
    );

    // Test NULL context.
    $section_storage = $this->prophesize(OverridesSectionStorageInterface::class);
    $section_storage->getContextValue('entity')->willReturn(NULL);
    $reflection->setValue($form, $section_storage->reveal());
    $this->assertSame($default, (string) $form->getDescription());

    // Test exception.
    $section_storage = $this->prophesize(OverridesSectionStorageInterface::class);
    $section_storage->getContextValue('entity')->willThrow(new ContextException());
    $reflection->setValue($form, $section_storage->reveal());
    $this->assertSame($default, (string) $form->getDescription());
  }

}
