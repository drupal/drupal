<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Entity\EntityFormTest.
 */

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityForm
 * @group Entity
 */
class EntityFormTest extends UnitTestCase {

  /**
   * The mocked entity form.
   *
   * @var \Drupal\Core\Entity\EntityFormInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityForm;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityForm = new EntityForm();
  }

  /**
   * Tests the form ID generation.
   *
   * @covers ::getFormId()
   *
   * @dataProvider providerTestFormIds
   */
  public function testFormId($expected, $definition) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('hasKey')
      ->with('bundle')
      ->will($this->returnValue($definition['bundle']));

    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array(), $definition['entity_type']), '', TRUE, TRUE, TRUE, array('getEntityType', 'bundle'));

    $entity->expects($this->any())
      ->method('getEntityType')
      ->will($this->returnValue($entity_type));
    $entity->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue($definition['bundle']));

    $this->entityForm->setEntity($entity);
    $this->entityForm->setOperation($definition['operation']);

    $this->assertSame($expected, $this->entityForm->getFormId());
  }

  /**
   * Provides test data for testFormId().
   */
  public function providerTestFormIds() {
    return array(
      array('article_node_form', array(
        'bundle' => 'article',
        'entity_type' => 'node',
        'operation' => 'default',
      )),
      array('article_node_delete_form', array(
        'bundle' => 'article',
        'entity_type' => 'node',
        'operation' => 'delete',
      )),
      array('user_user_form', array(
        'bundle' => 'user',
        'entity_type' => 'user',
        'operation' => 'default',
      )),
      array('user_form', array(
        'bundle' => '',
        'entity_type' => 'user',
        'operation' => 'default',
      )),
      array('user_delete_form', array(
        'bundle' => '',
        'entity_type' => 'user',
        'operation' => 'delete',
      )),
    );
  }

  /**
   * @covers ::copyFormValuesToEntity
   */
  public function testCopyFormValuesToEntity() {
    $entity_id = 'test_config_entity_id';
    $values = ['id' => $entity_id];
    $entity = $this->getMockBuilder('\Drupal\Tests\Core\Config\Entity\Fixtures\ConfigEntityBaseWithPluginCollections')
      ->setConstructorArgs([$values, 'test_config_entity'])
      ->setMethods(['getPluginCollections'])
      ->getMock();
    $entity->expects($this->atLeastOnce())
      ->method('getPluginCollections')
      ->willReturn(['key_controlled_by_plugin_collection' => NULL]);
    $this->entityForm->setEntity($entity);

    $form_state = (new FormState())->setValues([
      'regular_key' => 'foo',
      'key_controlled_by_plugin_collection' => 'bar',
    ]);
    $result = $this->entityForm->buildEntity([], $form_state);

    $this->assertSame($entity_id, $result->id());
    // The regular key should have a value, but the one controlled by a plugin
    // collection should not have been set.
    $this->assertSame('foo', $result->get('regular_key'));
    $this->assertNull($result->get('key_controlled_by_plugin_collection'));
  }

}
