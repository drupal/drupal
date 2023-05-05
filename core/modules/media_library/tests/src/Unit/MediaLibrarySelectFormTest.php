<?php

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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\media_library\Plugin\views\field\MediaLibrarySelectForm
 * @group media_library
 */
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
   * @covers ::viewsForm
   */
  public function testViewsForm(): void {
    $row = new ResultRow();

    $field = $this->getMockBuilder(MediaLibrarySelectForm::class)
      ->onlyMethods(['getEntity'])
      ->disableOriginalConstructor()
      ->getMock();
    $field->expects($this->any())
      ->method('getEntity')
      ->willReturn(NULL);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($container);

    $query = $this->getMockBuilder(ParameterBag::class)
      ->onlyMethods(['all'])
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->any())
      ->method('all')
      ->willReturn([]);

    $request = $this->getMockBuilder(Request::class)
      ->disableOriginalConstructor()
      ->getMock();
    $request->query = $query;

    $view = $this->getMockBuilder(ViewExecutable::class)
      ->onlyMethods(['getRequest', 'initStyle', 'getDisplay'])
      ->disableOriginalConstructor()
      ->getMock();
    $view->expects($this->any())
      ->method('getRequest')
      ->willReturn($request);
    $view->expects($this->any())
      ->method('initStyle')
      ->willReturn(TRUE);

    $display = $this->getMockBuilder(DefaultDisplay::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display->display['id'] = 'foo';
    $view->expects($this->any())
      ->method('getDisplay')
      ->willReturn($display);

    $view_entity = $this->getMockBuilder(View::class)
      ->disableOriginalConstructor()
      ->getMock();
    $view_entity->expects($this->any())
      ->method('get')
      ->willReturn([]);
    $view->storage = $view_entity;

    $display_manager = $this->getMockBuilder(ViewsPluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display = $this->getMockBuilder(DefaultDisplay::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display_manager->expects($this->any())
      ->method('createInstance')
      ->willReturn($display);
    $container->set('plugin.manager.views.display', $display_manager);
    \Drupal::setContainer($container);

    $form_state = $this->createMock(FormStateInterface::class);
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
