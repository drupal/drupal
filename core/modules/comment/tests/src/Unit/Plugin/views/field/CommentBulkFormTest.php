<?php

namespace Drupal\Tests\comment\Unit\Plugin\views\field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\comment\Plugin\views\field\CommentBulkForm;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\comment\Plugin\views\field\CommentBulkForm
 * @group comment
 */
class CommentBulkFormTest extends UnitTestCase {

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
        ->will($this->returnValue('comment'));
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

    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->once())
      ->method('getStorage')
      ->with('action')
      ->will($this->returnValue($entity_storage));

    $language_manager = $this->getMock('Drupal\Core\Language\LanguageManagerInterface');

    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->any())
      ->method('get')
      ->with('comment')
      ->will($this->returnValue(['table' => ['entity type' => 'comment']]));
    $container = new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $storage = $this->getMock('Drupal\views\ViewEntityInterface');
    $storage->expects($this->any())
      ->method('get')
      ->with('base_table')
      ->will($this->returnValue('comment'));

    $executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $executable->storage = $storage;

    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $definition['title'] = '';
    $options = [];

    $comment_bulk_form = new CommentBulkForm([], 'comment_bulk_form', $definition, $entity_manager, $language_manager);
    $comment_bulk_form->init($executable, $display, $options);

    $this->assertAttributeEquals(array_slice($actions, 0, -1, TRUE), 'actions', $comment_bulk_form);
  }

}
