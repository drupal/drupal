<?php

/**
 * @file
 */

namespace Drupal\big_pipe_test;

use Drupal\big_pipe\Render\BigPipeMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BigPipe placeholder test cases for use in both unit and integration tests.
 *
 * - Unit test:
 *   \Drupal\Tests\big_pipe\Unit\Render\Placeholder\BigPipeStrategyTest
 * - Integration test for BigPipe with JS on:
 *   \Drupal\Tests\big_pipe\Functional\BigPipeTest::testBigPipe()
 * - Integration test for BigPipe with JS off:
 *   \Drupal\Tests\big_pipe\Functional\BigPipeTest::testBigPipeNoJs()
 */
class BigPipePlaceholderTestCases {

  /**
   * Gets all BigPipe placeholder test cases.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
   *   Optional. Necessary to get the embedded AJAX/HTML responses.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   Optional. Necessary to get the embedded AJAX/HTML responses.
   *
   * @return \Drupal\big_pipe_test\BigPipePlaceholderTestCase[]
   */
  public static function cases(ContainerInterface $container = NULL, AccountInterface $user = NULL) {
    // Define the two types of cacheability that we expect to see. These will be
    // used in the expectations.
    $cacheability_depends_on_session_only = [
      'max-age' => 0,
      'contexts' => ['session.exists'],
    ];
    $cacheability_depends_on_session_and_nojs_cookie = [
      'max-age' => 0,
      'contexts' => ['session.exists', 'cookies:big_pipe_nojs'],
    ];


    // 1. Real-world example of HTML placeholder.
    $status_messages = new BigPipePlaceholderTestCase(
      ['#type' => 'status_messages'],
      '<drupal-render-placeholder callback="Drupal\Core\Render\Element\StatusMessages::renderMessages" arguments="0" token="_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></drupal-render-placeholder>',
      [
        '#lazy_builder' => [
          'Drupal\Core\Render\Element\StatusMessages::renderMessages',
          [NULL]
        ],
      ]
    );
    $status_messages->bigPipePlaceholderId = 'callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA';
    $status_messages->bigPipePlaceholderRenderArray = [
      '#markup' => '<span data-big-pipe-placeholder-id="callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></span>',
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'library' => ['big_pipe/big_pipe'],
        'drupalSettings' => [
          'bigPipePlaceholderIds' => [
            'callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&args%5B0%5D&token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA' => TRUE,
          ],
        ],
        'big_pipe_placeholders' => [
          'callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA' => $status_messages->placeholderRenderArray,
        ],
      ],
    ];
    $status_messages->bigPipeNoJsPlaceholder = '<span data-big-pipe-nojs-placeholder-id="callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></span>';
    $status_messages->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => '<span data-big-pipe-nojs-placeholder-id="callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></span>',
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          '<span data-big-pipe-nojs-placeholder-id="callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&amp;args%5B0%5D&amp;token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"></span>' => $status_messages->placeholderRenderArray,
        ],
      ],
    ];
    if ($container && $user) {
      $status_messages->embeddedAjaxResponseCommands = [
        [
          'command' => 'insert',
          'method' => 'replaceWith',
          'selector' => '[data-big-pipe-placeholder-id="callback=Drupal%5CCore%5CRender%5CElement%5CStatusMessages%3A%3ArenderMessages&args%5B0%5D&token=_HAdUpwWmet0TOTe2PSiJuMntExoshbm1kh2wQzzzAA"]',
          'data' => ' <div role="contentinfo" aria-label="Status message" class="messages messages--status">' . "\n" . ' <h2 class="visually-hidden">Status message</h2>' . "\n" . ' Hello from BigPipe!' . "\n" . ' </div>' . "\n ",
          'settings' => NULL,
        ],
      ];
      $status_messages->embeddedHtmlResponse = '<div role="contentinfo" aria-label="Status message" class="messages messages--status">' . "\n" . '                  <h2 class="visually-hidden">Status message</h2>' . "\n" . '                    Hello from BigPipe!' . "\n" . '            </div>' . "\n    \n";
    }


    // 2. Real-world example of HTML attribute value placeholder: form action.
    $form_action = new BigPipePlaceholderTestCase(
      $container ? $container->get('form_builder')->getForm('Drupal\big_pipe_test\Form\BigPipeTestForm') : [],
      'form_action_cc611e1d',
      [
        '#lazy_builder' => ['form_builder:renderPlaceholderFormAction', []],
      ]
    );
    $form_action->bigPipeNoJsPlaceholder = 'big_pipe_nojs_placeholder_attribute_safe:form_action_cc611e1d';
    $form_action->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => 'big_pipe_nojs_placeholder_attribute_safe:form_action_cc611e1d',
      '#cache' => $cacheability_depends_on_session_only,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          'big_pipe_nojs_placeholder_attribute_safe:form_action_cc611e1d' => $form_action->placeholderRenderArray,
        ],
      ],
    ];
    if ($container) {
      $form_action->embeddedHtmlResponse = '<form class="big-pipe-test-form" data-drupal-selector="big-pipe-test-form" action="' . base_path() . 'big_pipe_test"';
    }


    // 3. Real-world example of HTML attribute value subset placeholder: CSRF
    // token in link.
    $csrf_token = new BigPipePlaceholderTestCase(
      [
        '#title' => 'Link with CSRF token',
        '#type' => 'link',
        '#url' => Url::fromRoute('system.theme_set_default'),
      ],
      'e88b559cce72c80b687d56b0e2a3a5ae4b66bc0e',
      [
        '#lazy_builder' => [
          'route_processor_csrf:renderPlaceholderCsrfToken',
          ['admin/config/user-interface/shortcut/manage/default/add-link-inline']
        ],
      ]
    );
    $csrf_token->bigPipeNoJsPlaceholder = 'big_pipe_nojs_placeholder_attribute_safe:e88b559cce72c80b687d56b0e2a3a5ae4b66bc0e';
    $csrf_token->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => 'big_pipe_nojs_placeholder_attribute_safe:e88b559cce72c80b687d56b0e2a3a5ae4b66bc0e',
      '#cache' => $cacheability_depends_on_session_only,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          'big_pipe_nojs_placeholder_attribute_safe:e88b559cce72c80b687d56b0e2a3a5ae4b66bc0e' => $csrf_token->placeholderRenderArray,
        ],
      ],
    ];
    if ($container) {
      $csrf_token->embeddedHtmlResponse = $container->get('csrf_token')->get('admin/appearance/default');
    }


    // 4. Edge case: custom string to be considered as a placeholder that
    // happens to not be valid HTML.
    $hello = new BigPipePlaceholderTestCase(
      [
        '#markup' => BigPipeMarkup::create('<hello'),
        '#attached' => [
          'placeholders' => [
            '<hello' => ['#lazy_builder' => ['\Drupal\big_pipe_test\BigPipeTestController::helloOrYarhar', []]],
          ]
        ],
      ],
      '<hello',
      [
        '#lazy_builder' => [
          'hello_or_yarhar',
          []
        ],
      ]
    );
    $hello->bigPipeNoJsPlaceholder = 'big_pipe_nojs_placeholder_attribute_safe:&lt;hello';
    $hello->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => 'big_pipe_nojs_placeholder_attribute_safe:&lt;hello',
      '#cache' => $cacheability_depends_on_session_only,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          'big_pipe_nojs_placeholder_attribute_safe:&lt;hello' => $hello->placeholderRenderArray,
        ],
      ],
    ];
    $hello->embeddedHtmlResponse = '<marquee>Yarhar llamas forever!</marquee>';


    // 5. Edge case: non-#lazy_builder placeholder.
    $current_time = new BigPipePlaceholderTestCase(
      [
        '#markup' => BigPipeMarkup::create('<time>CURRENT TIME</time>'),
        '#attached' => [
          'placeholders' => [
            '<time>CURRENT TIME</time>' => [
              '#pre_render' => [
                '\Drupal\big_pipe_test\BigPipeTestController::currentTime',
              ],
            ]
          ]
        ]
      ],
      '<time>CURRENT TIME</time>',
      [
        '#pre_render' => ['current_time'],
      ]
    );
    $current_time->bigPipePlaceholderId = 'timecurrent-timetime';
    $current_time->bigPipePlaceholderRenderArray = [
      '#markup' => '<span data-big-pipe-placeholder-id="timecurrent-timetime"></span>',
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'library' => ['big_pipe/big_pipe'],
        'drupalSettings' => [
          'bigPipePlaceholderIds' => [
            'timecurrent-timetime' => TRUE,
          ],
        ],
        'big_pipe_placeholders' => [
          'timecurrent-timetime' => $current_time->placeholderRenderArray,
        ],
      ],
    ];
    $current_time->embeddedAjaxResponseCommands = [
      [
        'command' => 'insert',
        'method' => 'replaceWith',
        'selector' => '[data-big-pipe-placeholder-id="timecurrent-timetime"]',
        'data' => '<time datetime="1991-03-14"></time>',
        'settings' => NULL,
      ],
    ];
    $current_time->bigPipeNoJsPlaceholder = '<span data-big-pipe-nojs-placeholder-id="timecurrent-timetime"></span>';
    $current_time->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => '<span data-big-pipe-nojs-placeholder-id="timecurrent-timetime"></span>',
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          '<span data-big-pipe-nojs-placeholder-id="timecurrent-timetime"></span>' => $current_time->placeholderRenderArray,
        ],
      ],
    ];
    $current_time->embeddedHtmlResponse = '<time datetime="1991-03-14"></time>';


    // 6. Edge case: #lazy_builder that throws an exception.
    $exception = new BigPipePlaceholderTestCase(
      [
        '#lazy_builder' => ['\Drupal\big_pipe_test\BigPipeTestController::exception', ['llamas', 'suck']],
        '#create_placeholder' => TRUE,
      ],
      '<drupal-render-placeholder callback="\Drupal\big_pipe_test\BigPipeTestController::exception" arguments="0=llamas&amp;1=suck" token="uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU"></drupal-render-placeholder>',
      [
        '#lazy_builder' => ['\Drupal\big_pipe_test\BigPipeTestController::exception', ['llamas', 'suck']],
      ]
    );
    $exception->bigPipePlaceholderId = 'callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3Aexception&amp;args%5B0%5D=llamas&amp;args%5B1%5D=suck&amp;token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU';
    $exception->bigPipePlaceholderRenderArray = [
      '#markup' => '<span data-big-pipe-placeholder-id="callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3Aexception&amp;args%5B0%5D=llamas&amp;args%5B1%5D=suck&amp;token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU"></span>',
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'library' => ['big_pipe/big_pipe'],
        'drupalSettings' => [
          'bigPipePlaceholderIds' => [
            'callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3Aexception&args%5B0%5D=llamas&args%5B1%5D=suck&token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU' => TRUE,
          ],
        ],
        'big_pipe_placeholders' => [
          'callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3Aexception&amp;args%5B0%5D=llamas&amp;args%5B1%5D=suck&amp;token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU' => $exception->placeholderRenderArray,
        ],
      ],
    ];
    $exception->embeddedAjaxResponseCommands = NULL;
    $exception->bigPipeNoJsPlaceholder = '<span data-big-pipe-nojs-placeholder-id="callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3Aexception&amp;args%5B0%5D=llamas&amp;args%5B1%5D=suck&amp;token=uhKFNfT4eF449_W-kDQX8E5z4yHyt0-nSHUlwaGAQeU"></span>';
    $exception->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => $exception->bigPipeNoJsPlaceholder,
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          $exception->bigPipeNoJsPlaceholder => $exception->placeholderRenderArray,
        ],
      ],
    ];
    $exception->embeddedHtmlResponse = NULL;

    // 7. Edge case: response filter throwing an exception for this placeholder.
    $embedded_response_exception = new BigPipePlaceholderTestCase(
      [
        '#lazy_builder' => ['\Drupal\big_pipe_test\BigPipeTestController::responseException', []],
        '#create_placeholder' => TRUE,
      ],
      '<drupal-render-placeholder callback="\Drupal\big_pipe_test\BigPipeTestController::responseException" arguments="" token="PxOHfS_QL-T01NjBgu7Z7I04tIwMp6La5vM-mVxezbU"></drupal-render-placeholder>',
      [
        '#lazy_builder' => ['\Drupal\big_pipe_test\BigPipeTestController::responseException', []],
      ]
    );
    $embedded_response_exception->bigPipePlaceholderId = 'callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3AresponseException&amp;&amp;token=PxOHfS_QL-T01NjBgu7Z7I04tIwMp6La5vM-mVxezbU';
    $embedded_response_exception->bigPipePlaceholderRenderArray = [
      '#markup' => '<span data-big-pipe-placeholder-id="callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3AresponseException&amp;&amp;token=PxOHfS_QL-T01NjBgu7Z7I04tIwMp6La5vM-mVxezbU"></span>',
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'library' => ['big_pipe/big_pipe'],
        'drupalSettings' => [
          'bigPipePlaceholderIds' => [
            'callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3AresponseException&&token=PxOHfS_QL-T01NjBgu7Z7I04tIwMp6La5vM-mVxezbU' => TRUE,
          ],
        ],
        'big_pipe_placeholders' => [
          'callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3AresponseException&amp;&amp;token=PxOHfS_QL-T01NjBgu7Z7I04tIwMp6La5vM-mVxezbU' => $embedded_response_exception->placeholderRenderArray,
        ],
      ],
    ];
    $embedded_response_exception->embeddedAjaxResponseCommands = NULL;
    $embedded_response_exception->bigPipeNoJsPlaceholder = '<span data-big-pipe-nojs-placeholder-id="callback=%5CDrupal%5Cbig_pipe_test%5CBigPipeTestController%3A%3AresponseException&amp;&amp;token=PxOHfS_QL-T01NjBgu7Z7I04tIwMp6La5vM-mVxezbU"></span>';
    $embedded_response_exception->bigPipeNoJsPlaceholderRenderArray = [
      '#markup' => $embedded_response_exception->bigPipeNoJsPlaceholder,
      '#cache' => $cacheability_depends_on_session_and_nojs_cookie,
      '#attached' => [
        'big_pipe_nojs_placeholders' => [
          $embedded_response_exception->bigPipeNoJsPlaceholder => $embedded_response_exception->placeholderRenderArray,
        ],
      ],
    ];
    $exception->embeddedHtmlResponse = NULL;

    return [
      'html' => $status_messages,
      'html_attribute_value' => $form_action,
      'html_attribute_value_subset' => $csrf_token,
      'edge_case__invalid_html' => $hello,
      'edge_case__html_non_lazy_builder' => $current_time,
      'exception__lazy_builder' => $exception,
      'exception__embedded_response' => $embedded_response_exception,
    ];
  }

}

class BigPipePlaceholderTestCase {

  /**
   * The original render array.
   *
   * @var array
   */
  public $renderArray;

  /**
   * The expected corresponding placeholder string.
   *
   * @var string
   */
  public $placeholder;

  /**
   * The expected corresponding placeholder render array.
   *
   * @var array
   */
  public $placeholderRenderArray;

  /**
   * The expected BigPipe placeholder ID.
   *
   * (Only possible for HTML placeholders.)
   *
   * @var null|string
   */
  public $bigPipePlaceholderId = NULL;

  /**
   * The corresponding expected BigPipe placeholder render array.
   *
   * @var null|array
   */
  public $bigPipePlaceholderRenderArray = NULL;

  /**
   * The corresponding expected embedded AJAX response.
   *
   * @var null|array
   */
  public $embeddedAjaxResponseCommands = NULL;


  /**
   * The expected BigPipe no-JS placeholder.
   *
   * (Possible for all placeholders, HTML or non-HTML.)
   *
   * @var string
   */
  public $bigPipeNoJsPlaceholder;

  /**
   * The corresponding expected BigPipe no-JS placeholder render array.
   *
   * @var array
   */
  public $bigPipeNoJsPlaceholderRenderArray;

  /**
   * The corresponding expected embedded HTML response.
   *
   * @var string
   */
  public $embeddedHtmlResponse;

  public function __construct(array $render_array, $placeholder, array $placeholder_render_array) {
    $this->renderArray = $render_array;
    $this->placeholder = $placeholder;
    $this->placeholderRenderArray = $placeholder_render_array;
  }

}
