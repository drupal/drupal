<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\field\EntityLink.
 */
#[CoversClass(EntityLink::class)]
#[Group('Views')]
class LinkBaseTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpMockLoggerWithMissingEntity();
    $container = \Drupal::getContainer();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('renderer', $this->createStub(RendererInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * Tests the render method when getEntity returns NULL.
   */
  public function testRenderNullEntity(): void {
    $row = new ResultRow();

    $access = new AccessResultAllowed();
    $languageManager = $this->createStub(LanguageManagerInterface::class);
    $languageManager
      ->method('isMultilingual')
      ->willReturn(TRUE);
    $field = $this->getMockBuilder(LinkBase::class)
      ->setConstructorArgs([
        ['entity_type' => 'foo', 'entity field' => 'bar'],
        'foo',
        [],
        $this->createStub(AccessManagerInterface::class),
        $this->createStub(EntityTypeManagerInterface::class),
        $this->createStub(EntityRepositoryInterface::class),
        $languageManager,
      ])
      ->onlyMethods(['checkUrlAccess', 'getUrlInfo'])
      ->getMock();
    $field->expects($this->once())
      ->method('checkUrlAccess')
      ->willReturn($access);

    $view = $this->createStub(ViewExecutable::class);
    $display = $this->createStub(DisplayPluginBase::class);

    $field->init($view, $display);
    $field_built = $field->render($row);
    $this->assertEquals('', \Drupal::service('renderer')->render($field_built));
  }

}
