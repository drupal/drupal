<?php

namespace Drupal\Tests\node\Unit\Plugin\views\field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Plugin\views\field\NodeBulkForm;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\node\Plugin\views\field\NodeBulkForm
 * @group node
 */
class NodeBulkFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Tests the constructor assignment of actions.
   */
  public function testConstructor() {
    $actions = [];

    for ($i = 1; $i <= 2; $i++) {
      $action = $this->getMock('\Drupal\system\ActionConfigEntityInterface');
      $action->expects($this->any())
        ->method('getType')
        ->will($this->returnValue('node'));
      $actions[$i] = $action;
    }

    $action = $this->getMock('\Drupal\system\ActionConfigEntityInterface');
    $action->expects($this->any())
      ->method('getType')
      ->will($this->returnValue('user'));
    $actions[] = $action;

    $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $entity_storage->expects($this->any())
      ->method('loadMultiple')
      ->will($this->returnValue($actions));

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('action')
      ->will($this->returnValue($entity_storage));

    $entity_repository = $this->createMock(EntityRepositoryInterface::class);

    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $messenger = $this->getMock('Drupal\Core\Messenger\MessengerInterface');

    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->any())
      ->method('get')
      ->with('node')
      ->will($this->returnValue(['table' => ['entity type' => 'node']]));
    $container = new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $storage = $this->getMock('Drupal\views\ViewEntityInterface');
    $storage->expects($this->any())
      ->method('get')
      ->with('base_table')
      ->will($this->returnValue('node'));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->storage = $storage;

    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $definition['title'] = '';
    $options = [];

    $node_bulk_form = new NodeBulkForm([], 'node_bulk_form', $definition, $entity_type_manager, $language_manager, $messenger, $entity_repository);
    $node_bulk_form->init($executable, $display, $options);

    $this->assertAttributeEquals(array_slice($actions, 0, -1, TRUE), 'actions', $node_bulk_form);
  }

}
