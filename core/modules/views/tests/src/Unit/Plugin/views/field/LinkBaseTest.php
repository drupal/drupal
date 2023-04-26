<?php

namespace Drupal\Tests\views\Unit\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\EntityLink
 * @group Views
 */
class LinkBaseTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpMockLoggerWithMissingEntity();
    $container = \Drupal::getContainer();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    $container->set('renderer', $this->createMock(RendererInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Tests the render method when getEntity returns NULL.
   *
   * @covers ::render
   */
  public function testRenderNullEntity(): void {
    $row = new ResultRow();

    $access = new AccessResultAllowed();
    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $languageManager->expects($this->any())
      ->method('isMultilingual')
      ->willReturn(TRUE);
    $field = $this->getMockBuilder(LinkBase::class)
      ->setConstructorArgs([
        ['entity_type' => 'foo', 'entity field' => 'bar'],
        'foo',
        [],
        $this->createMock(AccessManagerInterface::class),
        $this->createMock(EntityTypeManagerInterface::class),
        $this->createMock(EntityRepositoryInterface::class),
        $languageManager,
      ])
      ->onlyMethods(['checkUrlAccess', 'getUrlInfo'])
      ->getMock();
    $field->expects($this->any())
      ->method('checkUrlAccess')
      ->willReturn($access);

    $view = $this->createMock(ViewExecutable::class);
    $display = $this->createMock(DisplayPluginBase::class);

    $field->init($view, $display);
    $field_built = $field->render($row);
    $this->assertEquals('', \Drupal::service('renderer')->render($field_built));
  }

}
