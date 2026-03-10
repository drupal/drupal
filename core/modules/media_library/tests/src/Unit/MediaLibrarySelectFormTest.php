<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media_library\Plugin\views\field\MediaLibrarySelectForm;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\display\DefaultDisplay;
use Drupal\views\Plugin\ViewsPluginManager;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests Drupal\media_library\Plugin\views\field\MediaLibrarySelectForm.
 */
#[CoversClass(MediaLibrarySelectForm::class)]
#[Group('media_library')]
class MediaLibrarySelectFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Tests views form.
   */
  public function testViewsForm(): void {
    $row = new ResultRow();

    $field = $this->getMockBuilder(MediaLibrarySelectForm::class)
      ->onlyMethods(['getEntity'])
      ->disableOriginalConstructor()
      ->getMock();
    $field->expects($this->atLeastOnce())
      ->method('getEntity')
      ->willReturn(NULL);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->createStub(TranslationInterface::class));
    \Drupal::setContainer($container);

    $request = $this->createStub(Request::class);
    $request->query = new InputBag();

    $view = $this->createStub(ViewExecutable::class);
    $view
      ->method('getRequest')
      ->willReturn($request);
    $view
      ->method('initStyle')
      ->willReturn(TRUE);

    $display = $this->createStub(DefaultDisplay::class);
    $display->display['id'] = 'foo';
    $view
      ->method('getDisplay')
      ->willReturn($display);

    $view_entity = $this->createStub(View::class);
    $view_entity
      ->method('get')
      ->willReturn([]);
    $view->storage = $view_entity;

    $display_manager = $this->createStub(ViewsPluginManager::class);
    $display_manager
      ->method('createInstance')
      ->willReturn($this->createStub(DefaultDisplay::class));
    $container->set('plugin.manager.views.display', $display_manager);
    \Drupal::setContainer($container);

    $form_state = $this->createStub(FormStateInterface::class);
    $view->result = [$row];
    $field->view = $view;
    $field->options = ['id' => 'bar'];
    $form = [];
    $field->viewsForm($form, $form_state);
    $this->assertNotEmpty($form);
    $this->assertNotEmpty($field->view->result);
    $this->assertIsArray($form[$field->options['id']][0]);
    $this->assertEmpty($form[$field->options['id']][0]);
  }

}
