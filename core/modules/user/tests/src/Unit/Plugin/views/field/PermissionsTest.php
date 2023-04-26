<?php

namespace Drupal\Tests\user\Unit\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\Plugin\views\field\Permissions;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\user\Plugin\views\field\Permissions
 * @group user
 */
class PermissionsTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpMockLoggerWithMissingEntity();
    $container = \Drupal::getContainer();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    $container->set('user.permissions', $this->createMock(PermissionHandlerInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Tests the preRender method when getEntity returns NULL.
   *
   * @covers ::preRender
   */
  public function testPreRenderNullEntity(): void {
    $values = [new ResultRow()];
    $field = new Permissions(['entity_type' => 'foo', 'entity field' => 'bar'], '', [], $this->createMock(ModuleHandlerInterface::class), $this->createMock(EntityTypeManagerInterface::class));
    $view = $this->createMock(ViewExecutable::class);
    $display = $this->createMock(DisplayPluginBase::class);
    $field->init($view, $display);
    $field->preRender($values);
    $this->assertEmpty($field->items);
  }

}
