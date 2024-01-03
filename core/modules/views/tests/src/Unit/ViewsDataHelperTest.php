<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewsDataHelper;
use Drupal\views\Tests\ViewTestData;

/**
 * @coversDefaultClass \Drupal\views\ViewsDataHelper
 * @group views
 */
class ViewsDataHelperTest extends UnitTestCase {

  /**
   * Returns the views data definition.
   *
   * @return array
   */
  protected function viewsData() {
    $data = ViewTestData::viewsData();

    // Tweak the views data to have a base for testing
    // \Drupal\views\ViewsDataHelper::fetchFields().
    unset($data['views_test_data']['id']['field']);
    unset($data['views_test_data']['name']['argument']);
    unset($data['views_test_data']['age']['filter']);
    unset($data['views_test_data']['job']['sort']);
    $data['views_test_data']['created']['area']['id'] = 'text';
    $data['views_test_data']['age']['area']['id'] = 'text';
    $data['views_test_data']['age']['area']['sub_type'] = 'header';
    $data['views_test_data']['job']['area']['id'] = 'text';
    $data['views_test_data']['job']['area']['sub_type'] = ['header', 'footer'];

    return $data;
  }

  /**
   * Tests fetchFields.
   */
  public function testFetchFields() {
    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $views_data->expects($this->once())
      ->method('getAll')
      ->willReturn($this->viewsData());

    $data_helper = new ViewsDataHelper($views_data);

    $expected = [
      'field' => [
        'age',
        'created',
        'job',
        'name',
        'status',
      ],
      'argument' => [
        'age',
        'created',
        'id',
        'job',
      ],
      'filter' => [
        'created',
        'id',
        'job',
        'name',
        'status',
      ],
      'sort' => [
        'age',
        'created',
        'id',
        'name',
        'status',
      ],
      'area' => [
        'age',
        'created',
        'job',
      ],
      'header' => [
        'age',
        'created',
        'job',
      ],
      'footer' => [
        'age',
        'created',
        'job',
      ],
    ];

    $handler_types = ['field', 'argument', 'filter', 'sort', 'area'];
    foreach ($handler_types as $handler_type) {
      $fields = $data_helper->fetchFields('views_test_data', $handler_type);
      $expected_keys = $expected[$handler_type];
      array_walk($expected_keys, function (&$item) {
        $item = "views_test_data.$item";
      });
      $this->assertEquals($expected_keys, array_keys($fields), "Handlers of type $handler_type are not listed as expected");
    }

    // Check for subtype filtering, so header and footer.
    foreach (['header', 'footer'] as $sub_type) {
      $fields = $data_helper->fetchFields('views_test_data', 'area', FALSE, $sub_type);

      $expected_keys = $expected[$sub_type];
      array_walk($expected_keys, function (&$item) {
        $item = "views_test_data.$item";
      });
      $this->assertEquals($expected_keys, array_keys($fields), "Sub_type $sub_type is not filtered as expected.");
    }
  }

}
