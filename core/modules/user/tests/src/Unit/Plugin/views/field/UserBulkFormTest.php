<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Plugin\views\field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\system\ActionConfigEntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Plugin\views\field\UserBulkForm;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\user\Plugin\views\field\UserBulkForm.
 */
#[CoversClass(UserBulkForm::class)]
#[Group('user')]
class UserBulkFormTest extends UnitTestCase {

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
        ->willReturn('user');
      $actions[$i] = $action;
    }

    $action = $this->createStub(ActionConfigEntityInterface::class);
    $action
      ->method('getType')
      ->willReturn('node');
    $actions[] = $action;

    $entity_storage = $this->createStub(EntityStorageInterface::class);
    $entity_storage
      ->method('loadMultiple')
      ->willReturn($actions);

    $entity_type_manager = $this->createStub(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->method('getStorage')
      ->with('action')
      ->willReturn($entity_storage);

    $entity_repository = $this->createStub(EntityRepositoryInterface::class);

    $language_manager = $this->createStub(LanguageManagerInterface::class);

    $messenger = $this->createStub(MessengerInterface::class);

    $route_match = $this->createStub(ResettableStackedRouteMatchInterface::class);

    $views_data = $this->createStub(ViewsData::class);
    $views_data
      ->method('get')
      ->with('users')
      ->willReturn(['table' => ['entity type' => 'user']]);
    $container = new ContainerBuilder();
    $container->set('views.views_data', $views_data);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $storage = $this->createStub(ViewEntityInterface::class);
    $storage
      ->method('get')
      ->with('base_table')
      ->willReturn('users');

    $executable = $this->createStub(ViewExecutable::class);
    $executable->storage = $storage;

    $display = $this->createStub(DisplayPluginBase::class);

    $definition['title'] = '';
    $options = [];

    $user_bulk_form = new UserBulkForm([], 'user_bulk_form', $definition, $entity_type_manager, $language_manager, $messenger, $entity_repository, $route_match);
    $user_bulk_form->init($executable, $display, $options);

    $reflected_actions = (new \ReflectionObject($user_bulk_form))->getProperty('actions');
    $this->assertEquals(array_slice($actions, 0, -1, TRUE), $reflected_actions->getValue($user_bulk_form));
  }

}
