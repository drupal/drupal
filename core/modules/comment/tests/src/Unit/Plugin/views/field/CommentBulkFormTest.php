<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit\Plugin\views\field;

use Drupal\comment\Plugin\views\field\CommentBulkForm;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\system\ActionConfigEntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\comment\Plugin\views\field\CommentBulkForm.
 */
#[CoversClass(CommentBulkForm::class)]
#[Group('comment')]
class CommentBulkFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Tests the constructor assignment of actions.
   */
  public function testConstructor(): void {
    $actions = [];

    for ($i = 1; $i <= 2; $i++) {
      $action = $this->createStub(ActionConfigEntityInterface::class);
      $action
        ->method('getType')
        ->willReturn('comment');
      $actions[$i] = $action;
    }

    $action = $this->createStub(ActionConfigEntityInterface::class);
    $action
      ->method('getType')
      ->willReturn('user');
    $actions[] = $action;

    $entity_storage = $this->createStub(EntityStorageInterface::class);
    $entity_storage
      ->method('loadMultiple')
      ->willReturn($actions);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('action')
      ->willReturn($entity_storage);

    $entity_repository = $this->createStub(EntityRepositoryInterface::class);

    $language_manager = $this->createStub(LanguageManagerInterface::class);

    $messenger = $this->createStub(MessengerInterface::class);

    $route_match = $this->createStub(ResettableStackedRouteMatchInterface::class);

    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->once())
      ->method('get')
      ->with('comment')
      ->willReturn(['table' => ['entity type' => 'comment']]);
    $container = new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $storage = $this->createMock('Drupal\views\ViewEntityInterface');
    $storage->expects($this->once())
      ->method('get')
      ->with('base_table')
      ->willReturn('comment');

    $executable = $this->createStub(ViewExecutable::class);
    $executable->storage = $storage;

    $display = $this->createStub(DisplayPluginBase::class);

    $definition['title'] = '';
    $options = [];

    $comment_bulk_form = new CommentBulkForm([], 'comment_bulk_form', $definition, $entity_type_manager, $language_manager, $messenger, $entity_repository, $route_match);
    $comment_bulk_form->init($executable, $display, $options);

    $reflected_actions = (new \ReflectionObject($comment_bulk_form))->getProperty('actions');
    $this->assertEquals(array_slice($actions, 0, -1, TRUE), $reflected_actions->getValue($comment_bulk_form));
  }

}
