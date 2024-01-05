<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit\Plugin\views\field;

use Drupal\comment\Plugin\views\field\LinkReply;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\comment\Plugin\views\field\LinkReply
 * @group comment
 */
class LinkReplyTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpMockLoggerWithMissingEntity();
    $container = \Drupal::getContainer();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Test the render method when getEntity returns NULL.
   *
   * @covers ::render
   */
  public function testRenderNullEntity(): void {
    $row = new ResultRow();
    $field = new LinkReply(['entity_type' => 'foo', 'entity field' => 'bar'], '', [], $this->createMock(AccessManagerInterface::class), $this->createMock(EntityTypeManagerInterface::class), $this->createMock(EntityRepositoryInterface::class), $this->createMock(LanguageManagerInterface::class));
    $view = $this->createMock(ViewExecutable::class);
    $display = $this->createMock(DisplayPluginBase::class);
    $field->init($view, $display);
    $this->assertEmpty($field->render($row));
  }

}
