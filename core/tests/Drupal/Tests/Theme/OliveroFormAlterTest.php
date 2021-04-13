<?php

namespace Drupal\Tests\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\form_test\Form\FormTestValidateRequiredForm;
use Drupal\search\Form\SearchBlockForm;
use Drupal\search\Form\SearchPageForm;
use Drupal\search\Plugin\SearchInterface;
use Drupal\search\SearchPageInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Olivero theme's hook_form_alter.
 *
 * @group olivero
 */
final class OliveroFormAlterTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $string_translation = $this->prophesize(TranslationInterface::class)->reveal();
    $url_generator = $this->prophesize(UrlGeneratorInterface::class)->reveal();
    $container = new ContainerBuilder();
    $container->set('url_generator', $url_generator);
    $container->set('string_translation', $string_translation);
    \Drupal::setContainer($container);

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

  /**
   * Tests specific alters for the `search_block_form` form.
   */
  public function testSearchBlockFormAlter() {
    $form_state = new FormState();
    $search_block_form = new SearchBlockForm(
      $this->prophesize(SearchPageRepositoryInterface::class)->reveal(),
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $this->prophesize(RendererInterface::class)->reveal()
    );
    $form = $search_block_form->buildForm([], $form_state, '123');
    olivero_form_alter($form, $form_state, $search_block_form->getFormId());

    self::assertEquals(t('Search by keyword or phrase.'), $form['keys']['#attributes']['placeholder']);
  }

  /**
   * Tests specific alters for the `search_form` form.
   */
  public function testSearchPageFormAlter() {
    $form_state = new FormState();
    $search_block_form = new SearchPageForm();
    $search_page = $this->prophesize(SearchPageInterface::class);
    $search_page->id()->willReturn(12345);
    $search_page->getPlugin()->willReturn(
      $this->prophesize(SearchInterface::class)->reveal()
    );
    $form = $search_block_form->buildForm([], $form_state, $search_page->reveal());
    olivero_form_alter($form, $form_state, $search_block_form->getFormId());

    self::assertEquals(t('Search by keyword or phrase.'), $form['basic']['keys']['#attributes']['placeholder']);
    self::assertContains('button--primary', $form['basic']['submit']['#attributes']['class']);
    self::assertContains('button--primary', $form['advanced']['submit']['#attributes']['class']);
  }

  /**
   * Data provider to test classes added to the submit action of a form.
   */
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
