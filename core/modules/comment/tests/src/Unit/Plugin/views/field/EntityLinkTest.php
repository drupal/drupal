<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit\Plugin\views\field;

use Drupal\comment\Plugin\views\field\EntityLink;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\comment\Plugin\views\field\EntityLink
 * @group comment
 */
class EntityLinkTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpMockLoggerWithMissingEntity();
  }

  /**
   * Test the render method when getEntity returns NULL.
   *
   * @covers ::render
   */
  public function testRenderNullEntity(): void {
    $row = new ResultRow();
    $field = new EntityLink(['entity_type' => 'foo', 'entity field' => 'bar'], '', []);
    $view = $this->createMock(ViewExecutable::class);
    $display = $this->createMock(DisplayPluginBase::class);
    $field->init($view, $display);
    $this->assertEmpty($field->render($row));
  }

}
