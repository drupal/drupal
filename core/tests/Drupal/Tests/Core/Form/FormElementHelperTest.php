<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormElementHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the form element helper.
 */
#[CoversClass(FormElementHelper::class)]
#[Group('Drupal')]
#[Group('Form')]
class FormElementHelperTest extends UnitTestCase {

  /**
   * Tests the getElementByName() method.
   *
   * @legacy-covers ::getElementByName
   */
  #[DataProvider('getElementByNameProvider')]
  public function testGetElementByName($name, $form, $expected): void {
    $this->assertSame($expected, FormElementHelper::getElementByName($name, $form));
  }

  /**
   * Provides test data.
   */
  public static function getElementByNameProvider(): array {
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
   * @legacy-covers ::getElementTitle
   */
  #[DataProvider('getElementTitleProvider')]
  public function testGetElementTitle($name, $form, $expected): void {
    $element = FormElementHelper::getElementByName($name, $form);
    $this->assertSame($expected, FormElementHelper::getElementTitle($element));
  }

  /**
   * Provides test data.
   */
  public static function getElementTitleProvider(): array {
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
