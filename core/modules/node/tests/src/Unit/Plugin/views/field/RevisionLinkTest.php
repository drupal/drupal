<?php

namespace Drupal\Tests\node\Unit\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\Plugin\views\field\RevisionLink;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\node\Plugin\views\field\RevisionLink
 * @group node
 */
class RevisionLinkTest extends UnitTestCase {

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
    $field = new RevisionLink(['entity_type' => 'foo', 'entity field' => 'bar'], '', [], $this->createMock(AccessManagerInterface::class), $this->createMock(EntityTypeManagerInterface::class), $this->createMock(EntityRepositoryInterface::class), $this->createMock(LanguageManagerInterface::class));
    $view = $this->createMock(ViewExecutable::class);
    $display = $this->createMock(DisplayPluginBase::class);
    $field->init($view, $display);
    $this->assertEmpty($field->render($row));
  }

}
