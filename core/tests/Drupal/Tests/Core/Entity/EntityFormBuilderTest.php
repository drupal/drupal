<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityFormBuilder
 * @group Entity
 */
class EntityFormBuilderTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formBuilder;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->formBuilder = $this->createMock('Drupal\Core\Form\FormBuilderInterface');
    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->entityFormBuilder = new EntityFormBuilder($this->entityTypeManager, $this->formBuilder);
  }

  /**
   * Tests the getForm() method.
   *
   * @covers ::getForm
   */
  public function testGetForm() {
    $form_controller = $this->createMock('Drupal\Core\Entity\EntityFormInterface');
    $form_controller->expects($this->any())
      ->method('getFormId')
      ->willReturn('the_form_id');
    $this->entityTypeManager->expects($this->any())
      ->method('getFormObject')
      ->with('the_entity_type', 'default')
      ->willReturn($form_controller);

    $this->formBuilder->expects($this->once())
      ->method('buildForm')
      ->with($form_controller, $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'))
      ->willReturn('the form contents');

    $entity = $this->createMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('getEntityTypeId')
      ->willReturn('the_entity_type');

    $this->assertSame('the form contents', $this->entityFormBuilder->getForm($entity));
  }

}
