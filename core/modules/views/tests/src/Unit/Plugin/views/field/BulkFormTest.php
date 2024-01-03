<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\BulkForm
 * @group Views
 */
class BulkFormTest extends UnitTestCase {

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

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($container);

    $field = $this->getMockBuilder(BulkForm::class)
      ->onlyMethods(['getEntityType', 'getEntity'])
      ->disableOriginalConstructor()
      ->getMock();
    $field->expects($this->any())
      ->method('getEntityType')
      ->willReturn('foo');
    $field->expects($this->any())
      ->method('getEntity')
      ->willReturn(NULL);

    $query = $this->getMockBuilder(QueryPluginBase::class)
      ->onlyMethods(['getEntityTableInfo'])
      ->disableOriginalConstructor()
      ->getMock();
    $query->expects($this->any())
      ->method('getEntityTableInfo')
      ->willReturn([]);
    $view = $this->getMockBuilder(ViewExecutable::class)
      ->onlyMethods(['getQuery'])
      ->disableOriginalConstructor()
      ->getMock();
    $view->expects($this->any())
      ->method('getQuery')
      ->willReturn($query);
    $view->result = [$row];
    $view->query = $query;
    $field->view = $view;
    $field->options = ['id' => 'bar', 'action_title' => 'zee'];
    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];
    $field->viewsForm($form, $form_state);
    $this->assertNotEmpty($form);
    $this->assertIsArray($form[$field->options['id']][0]);
    $this->assertEmpty($form[$field->options['id']][0]);
  }

}
