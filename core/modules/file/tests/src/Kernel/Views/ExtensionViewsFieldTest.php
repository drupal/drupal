<?php

namespace Drupal\Tests\file\Kernel\Views;

use Drupal\Core\Render\RenderContext;
use Drupal\file\Entity\File;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the core Drupal\file\Plugin\views\field\Extension handler.
 *
 * @group file
 */
class ExtensionViewsFieldTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'file_test_views', 'user'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['file_extension_view'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();
    ViewTestData::createTestViews(static::class, ['file_test_views']);

    $this->installEntitySchema('file');

    file_put_contents('public://file.png', '');
    File::create([
      'uri' => 'public://file.png',
      'filename' => 'file.png',
    ])->save();

    file_put_contents('public://file.tar', '');
    File::create([
      'uri' => 'public://file.tar',
      'filename' => 'file.tar',
    ])->save();

    file_put_contents('public://file.tar.gz', '');
    File::create([
      'uri' => 'public://file.tar.gz',
      'filename' => 'file.tar.gz',
    ])->save();

    file_put_contents('public://file', '');
    File::create([
      'uri' => 'public://file',
      'filename' => 'file',
    ])->save();
  }

  /**
   * Tests file extension views field handler extension_detect_tar option.
   */
  public function testFileExtensionTarOption() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('file_extension_view');
    $view->setDisplay();
    $this->executeView($view);

    // Test without the tar option.
    $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      $this->assertEquals('png', $view->field['extension']->advancedRender($view->result[0]));
      $this->assertEquals('tar', $view->field['extension']->advancedRender($view->result[1]));
      $this->assertEquals('gz', $view->field['extension']->advancedRender($view->result[2]));
      $this->assertEquals('', $view->field['extension']->advancedRender($view->result[3]));
    });

    // Test with the tar option.
    $view = Views::getView('file_extension_view');
    $view->setDisplay();
    $view->initHandlers();

    $view->field['extension']->options['settings']['extension_detect_tar'] = TRUE;
    $this->executeView($view);

    $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      $this->assertEquals('png', $view->field['extension']->advancedRender($view->result[0]));
      $this->assertEquals('tar', $view->field['extension']->advancedRender($view->result[1]));
      $this->assertEquals('tar.gz', $view->field['extension']->advancedRender($view->result[2]));
      $this->assertEquals('', $view->field['extension']->advancedRender($view->result[3]));
    });
  }

}
