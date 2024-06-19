<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\Unit;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\field_ui\Form\FieldConfigEditForm;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field_ui\Form\FieldConfigEditForm
 *
 * @group field_ui
 */
class FieldConfigEditFormTest extends UnitTestCase {

  /**
   * The field config edit form.
   *
   * @var \Drupal\field_ui\Form\FieldConfigEditForm|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldConfigEditForm;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_bundle_info = $this->createMock('\Drupal\Core\Entity\EntityTypeBundleInfoInterface');
    $typed_data = $this->createMock('\Drupal\Core\TypedData\TypedDataManagerInterface');
    $temp_store = $this->createMock(PrivateTempStore::class);
    $element_info_manager = $this->createMock(ElementInfoManagerInterface::class);
    $entity_display_repository = $this->createMock(EntityDisplayRepositoryInterface::class);
    $this->fieldConfigEditForm = new FieldConfigEditForm($entity_type_bundle_info, $typed_data, $entity_display_repository, $temp_store, $element_info_manager);
  }

  /**
   * @covers ::hasAnyRequired
   *
   * @dataProvider providerRequired
   */
  public function testHasAnyRequired(array $element, bool $result): void {
    $reflection = new \ReflectionClass('\Drupal\field_ui\Form\FieldConfigEditForm');
    $method = $reflection->getMethod('hasAnyRequired');
    $this->assertEquals($result, $method->invoke($this->fieldConfigEditForm, $element));
  }

  /**
   * Provides test cases with required and optional elements.
   */
  public static function providerRequired(): \Generator {
    yield 'required' => [
      [['#required' => TRUE]],
      TRUE,
    ];
    yield 'optional' => [
      [['#required' => FALSE]],
      FALSE,
    ];
    yield 'required and optional' => [
      [['#required' => TRUE], ['#required' => FALSE]],
      TRUE,
    ];
    yield 'empty' => [
      [[], []],
      FALSE,
    ];
    yield 'multiple required' => [
      [[['#required' => TRUE]], [['#required' => TRUE]]],
      TRUE,
    ];
    yield 'multiple optional' => [
      [[['#required' => FALSE]], [['#required' => FALSE]]],
      FALSE,
    ];
  }

}
