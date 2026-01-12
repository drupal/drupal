<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\Views;

use Drupal\rest\Plugin\views\style\Serializer;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Tests\ViewTestData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\rest\Plugin\views\style\Serializer.
 */
#[CoversClass(Serializer::class)]
#[Group('views')]
#[RunTestsInSeparateProcesses]
class StyleSerializerKernelTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_serializer_display_entity'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest_test_views', 'serialization', 'rest'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    ViewTestData::createTestViews(static::class, ['rest_test_views']);
  }

  /**
   * Tests calculate dependencies.
   */
  public function testCalculateDependencies(): void {
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('test_serializer_display_entity');
    $display = &$view->getDisplay('rest_export_1');

    $display['display_options']['defaults']['style'] = FALSE;
    $display['display_options']['style']['type'] = 'serializer';
    $display['display_options']['style']['options']['formats'] = ['json', 'xml'];
    $view->save();

    $view->calculateDependencies();
    $this->assertEquals(['module' => ['rest', 'serialization', 'user']], $view->getDependencies());
  }

}
