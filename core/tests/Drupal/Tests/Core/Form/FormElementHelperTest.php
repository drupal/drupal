<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormElementHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the form element helper.
 *
 * @group Drupal
 * @group Form
 *
 * @coversDefaultClass \Drupal\Core\Form\FormElementHelper
 */
class FormElementHelperTest extends UnitTestCase {

  /**
   * Tests the getElementByName() method.
   *
   * @covers ::getElementByName
   *
   * @dataProvider getElementByNameProvider
   */
  public function testGetElementByName($name, $form, $expected) {
    $this->assertSame($expected, FormElementHelper::getElementByName($name, $form));
  }

  /**
   * Provides test data.
   */
  public function getElementByNameProvider() {
    $data = [];
    $data[] = ['id', [], []];
    $data[] = [
      'id',
      [
        'id' => [
          '#title' => 'ID',
          '#parents' => ['id'],
        ],
      ],
      [
        '#title' => 'ID',
        '#parents' => ['id'],
      ],
    ];
    $data[] = [
      'id',
      [
        'fieldset' => [
          'id' => [
            '#title' => 'ID',
            '#parents' => ['id'],
          ],
          '#parents' => ['fieldset'],
        ],
      ],
      [
        '#title' => 'ID',
        '#parents' => ['id'],
      ],
    ];
    $data[] = [
      'fieldset',
      [
        'fieldset' => [
          'id' => [
            '#title' => 'ID',
            '#parents' => ['id'],
          ],
          '#parents' => ['fieldset'],
        ],
      ],
      [
        'id' => [
          '#title' => 'ID',
          '#parents' => ['id'],
        ],
        '#parents' => ['fieldset'],
      ],
    ];
    $data[] = [
      'fieldset][id',
      [
        'fieldset' => [
          '#tree' => TRUE,
          'id' => [
            '#title' => 'ID',
            '#parents' => ['fieldset', 'id'],
          ],
          '#parents' => ['fieldset'],
        ],
      ],
      [
        '#title' => 'ID',
        '#parents' => ['fieldset', 'id'],
      ],
    ];
    return $data;
  }

  /**
   * Tests the getElementTitle() method.
   *
   * @covers ::getElementTitle
   *
   * @dataProvider getElementTitleProvider
   */
  public function testGetElementTitle($name, $form, $expected) {
    $element = FormElementHelper::getElementByName($name, $form);
    $this->assertSame($expected, FormElementHelper::getElementTitle($element));
  }

  /**
   * Provides test data.
   */
  public function getElementTitleProvider() {
    $data = [];
    $data[] = ['id', [], ''];
    $data[] = [
      'id',
      [
        'id' => [
          '#title' => 'ID',
          '#parents' => ['id'],
        ],
      ],
      'ID',
    ];
    $data[] = [
      'id',
      [
        'fieldset' => [
          'id' => [
            '#title' => 'ID',
            '#parents' => ['id'],
          ],
          '#parents' => ['fieldset'],
        ],
      ],
      'ID',
    ];
    $data[] = [
      'fieldset',
      [
        'fieldset' => [
          'id' => [
            '#title' => 'ID',
            '#parents' => ['id'],
          ],
          '#parents' => ['fieldset'],
        ],
      ],
      'ID',
    ];
    $data[] = [
      'fieldset][id',
      [
        'fieldset' => [
          '#tree' => TRUE,
          'id' => [
            '#title' => 'ID',
            '#parents' => ['fieldset', 'id'],
          ],
          '#parents' => ['fieldset'],
        ],
      ],
      'ID',
    ];
    return $data;
  }

}
