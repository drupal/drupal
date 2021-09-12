<?php

namespace Drupal\Tests\system\Kernel\Common;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Performs integration tests on \Drupal::service('renderer')->render().
 *
 * @group system
 */
class FormElementsRenderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['common_test', 'system'];

  /**
   * Tests rendering form elements without passing through
   * \Drupal::formBuilder()->doBuildForm().
   */
  public function testDrupalRenderFormElements() {
    // Define a series of form elements.
    $element = [
      '#type' => 'button',
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'submit']);

    $element = [
      '#type' => 'textfield',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'text']);

    $element = [
      '#type' => 'password',
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'password']);

    $element = [
      '#type' => 'textarea',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//textarea');

    $element = [
      '#type' => 'radio',
      '#title' => $this->randomMachineName(),
      '#value' => FALSE,
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'radio']);

    $element = [
      '#type' => 'checkbox',
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'checkbox']);

    $element = [
      '#type' => 'select',
      '#title' => $this->randomMachineName(),
      '#options' => [
        0 => $this->randomMachineName(),
        1 => $this->randomMachineName(),
      ],
    ];
    $this->assertRenderedElement($element, '//select');

    $element = [
      '#type' => 'file',
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'file']);

    $element = [
      '#type' => 'item',
      '#title' => $this->randomMachineName(),
      '#markup' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//div[contains(@class, :class) and contains(., :markup)]/label[contains(., :label)]', [
      ':class' => 'js-form-type-item',
      ':markup' => $element['#markup'],
      ':label' => $element['#title'],
    ]);

    $element = [
      '#type' => 'hidden',
      '#title' => $this->randomMachineName(),
      '#value' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//input[@type=:type]', [':type' => 'hidden']);

    $element = [
      '#type' => 'link',
      '#title' => $this->randomMachineName(),
      '#url' => Url::fromRoute('common_test.destination'),
      '#options' => [
        'absolute' => TRUE,
      ],
    ];
    $this->assertRenderedElement($element, '//a[@href=:href and contains(., :title)]', [
      ':href' => URL::fromRoute('common_test.destination')->setAbsolute()->toString(),
      ':title' => $element['#title'],
    ]);

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//details/summary[contains(., :title)]', [
      ':title' => $element['#title'],
    ]);

    $element = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//details');

    $element['item'] = [
      '#type' => 'item',
      '#title' => $this->randomMachineName(),
      '#markup' => $this->randomMachineName(),
    ];
    $this->assertRenderedElement($element, '//details/div[contains(@class, :class) and contains(., :markup)]', [
      ':class' => 'js-form-type-item',
      ':markup' => $element['item']['#markup'],
    ]);
  }

  /**
   * Tests that elements are rendered properly.
   */
  protected function assertRenderedElement(array $element, $xpath, array $xpath_args = []) {
    $this->render($element);

    $xpath = $this->buildXPathQuery($xpath, $xpath_args);
    $element += ['#value' => NULL];
    $this->assertFieldByXPath($xpath, $element['#value'], new FormattableMarkup('#type @type was properly rendered.', [
      '@type' => var_export($element['#type'], TRUE),
    ]));
  }

}
