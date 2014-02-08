<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityFormBuilder
 */
class EntityFormBuilderTest extends UnitTestCase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
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
  public static function getInfo() {
    return array(
      'name' => 'Entity Form Builder test',
      'description' => 'Unit test the entity form builder.',
      'group' => 'Entity',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->formBuilder = $this->getMock('Drupal\Core\Form\FormBuilderInterface');
    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->entityFormBuilder = new EntityFormBuilder($this->entityManager, $this->formBuilder);
  }

  /**
   * Tests the getForm() method.
   *
   * @covers ::getForm()
   */
  public function testGetForm() {
    $this->formBuilder->expects($this->once())
      ->method('buildForm')
      ->with('the_form_id', $this->isType('array'))
      ->will($this->returnValue('the form contents'));

    $form_controller = $this->getMock('Drupal\Core\Entity\EntityFormControllerInterface');
    $form_controller->expects($this->any())
      ->method('getFormId')
      ->will($this->returnValue('the_form_id'));
    $this->entityManager->expects($this->any())
      ->method('getFormController')
      ->with('the_entity_type', 'default')
      ->will($this->returnValue($form_controller));

    $entity = $this->getMock('Drupal\Core\Entity\EntityInterface');
    $entity->expects($this->once())
      ->method('getEntityTypeId')
      ->will($this->returnValue('the_entity_type'));

    $this->assertSame('the form contents', $this->entityFormBuilder->getForm($entity));
  }

}
