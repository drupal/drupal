<?php

namespace Drupal\Tests\Theme;

use Drupal\Core\Form\FormState;
use Drupal\form_test\Form\FormTestValidateRequiredForm;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's hook_form_alter.
 *
 * @group olivero
 */
final class OliveroFormAlterTest extends UnitTestCase {

  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../../themes/olivero/olivero.theme';
  }

  /**
   * Tests the hook_form_alter adjustments.
   *
   * @dataProvider dataForFormAlterTest
   */
  public function testAlteredForm(array $form, array $expected_form) {
    $form_state = new FormState();
    olivero_form_alter($form, $form_state, 'llama_form');

    self::assertEquals($expected_form, $form);
  }

  public function dataForFormAlterTest() {
    // If only one button, class is added to submit.
    yield [
      [
        'actions' => [
          'submit' => [
            '#type' => 'submit',
          ],
        ],
      ],
      [
        'actions' => [
          'submit' => [
            '#type' => 'submit',
            '#attributes' => [
              'class' => ['button--primary'],
            ],
          ],
        ],
      ],
    ];
    // If two buttons, class is added to submit.
    yield [
      [
        'actions' => [
          'submit' => [
            '#type' => 'submit',
          ],
          'reset' => [
            '#type' => 'button',
          ],
        ],
      ],
      [
        'actions' => [
          'submit' => [
            '#type' => 'submit',
            '#attributes' => [
              'class' => ['button--primary'],
            ],
          ],
          'reset' => [
            '#type' => 'button',
          ],
        ],
      ],
    ];

    // If three buttons, skipped since it cannot be determined.
    yield [
      [
        'actions' => [
          'submit' => [
            '#type' => 'submit',
          ],
          'reset' => [
            '#type' => 'button',
          ],
          'other_button' => [
            '#type' => 'button',
          ],
        ],
      ],
      [
        'actions' => [
          'submit' => [
            '#type' => 'submit',
          ],
          'reset' => [
            '#type' => 'button',
          ],
          'other_button' => [
            '#type' => 'button',
          ],
        ],
      ],
    ];
    // Skipped if there is no actions element.
    yield [
      [
        'submit' => [
          '#type' => 'submit',
        ],
      ],
      [
        'submit' => [
          '#type' => 'submit',
        ],
      ],
    ];
    // Primary button class is assigned to the submit button, even if it has
    // a different key name. (Currently broken.)
    // @todo fix in https://www.drupal.org/project/drupal/issues/3206018
    yield [
      [
        'actions' => [
          'continue' => [
            '#type' => 'submit',
          ],
        ],
      ],
      [
        'actions' => [
          'continue' => [
            '#type' => 'submit',
          ],
          'submit' => [
            '#attributes' => [
              'class' => ['button--primary'],
            ],
          ],
        ],
      ],
    ];

    // Tests a form class which uses `actions` to track changes from an existing
    // test class to find any regressions outside of our mocks.
    $form = (new FormTestValidateRequiredForm())->buildForm([], new FormState());
    $expected_form = $form;
    $expected_form['actions']['submit']['#attributes']['class'][] = 'button--primary';
    yield [$form, $expected_form];

  }

}
