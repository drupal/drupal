<?php

namespace Drupal\ajax_test\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides content for dialog tests.
 */
class AjaxTestController {

  /**
   * Example content for dialog testing.
   *
   * @return array
   *   Renderable array of AJAX dialog contents.
   */
  public static function dialogContents() {
    // This is a regular render array; the keys do not have special meaning.
    $content = [
      '#title' => '<em>AJAX Dialog & contents</em>',
      'content' => [
        '#markup' => 'Example message',
      ],
      'cancel' => [
        '#type' => 'link',
        '#title' => 'Cancel',
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => [
          // This is a special class to which JavaScript assigns dialog closing
          // behavior.
          'class' => ['dialog-cancel'],
        ],
      ],
    ];

    return $content;
  }

  /**
   * Example content for testing the wrapper of the response.
   *
   * @param string $type
   *   Type of response.
   *
   * @return array
   *   Renderable array of AJAX response contents.
   */
  public function renderTypes($type) {
    return [
      '#title' => '<em>AJAX Dialog & contents</em>',
      'content' => [
        '#type' => 'inline_template',
        '#template' => $this->getRenderTypes()[$type]['render'],
      ],
    ];
  }

  /**
   * Returns a render array of links that directly Drupal.ajax().
   *
   * @return array
   *   Renderable array of AJAX response contents.
   */
  public function insertLinksBlockWrapper() {
    $methods = [
      'html',
      'replaceWith',
    ];

    $build['links'] = [
      'ajax_target' => [
        '#markup' => '<div class="ajax-target-wrapper"><div id="ajax-target">Target</div></div>',
      ],
      'links' => [
        '#theme' => 'links',
        '#attached' => ['library' => ['ajax_test/ajax_insert']],
      ],
    ];
    foreach ($methods as $method) {
      foreach ($this->getRenderTypes() as $type => $item) {
        $class = 'ajax-insert';
        $build['links']['links']['#links']["$method-$type"] = [
          'title' => "Link $method $type",
          'url' => Url::fromRoute('ajax_test.ajax_render_types', ['type' => $type]),
          'attributes' => [
            'class' => [$class],
            'data-method' => $method,
            'data-effect' => $item['effect'],
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * Returns a render array of links that directly Drupal.ajax().
   *
   * @return array
   *   Renderable array of AJAX response contents.
   */
  public function insertLinksInlineWrapper() {
    $methods = [
      'html',
      'replaceWith',
    ];

    $build['links'] = [
      'ajax_target' => [
        '#markup' => '<div class="ajax-target-wrapper"><span id="ajax-target-inline">Target inline</span></div>',
      ],
      'links' => [
        '#theme' => 'links',
        '#attached' => ['library' => ['ajax_test/ajax_insert']],
      ],
    ];
    foreach ($methods as $method) {
      foreach ($this->getRenderTypes() as $type => $item) {
        $class = 'ajax-insert-inline';
        $build['links']['links']['#links']["$method-$type"] = [
          'title' => "Link $method $type",
          'url' => Url::fromRoute('ajax_test.ajax_render_types', ['type' => $type]),
          'attributes' => [
            'class' => [$class],
            'data-method' => $method,
            'data-effect' => $item['effect'],
          ],
        ];
      }
    }
    return $build;
  }

  /**
   * Returns a render array that will be rendered by AjaxRenderer.
   *
   * Verifies that the response incorporates JavaScript settings generated
   * during the page request by adding a dummy setting.
   */
  public function render() {
    return [
      '#attached' => [
        'library' => [
          'core/drupalSettings',
        ],
        'drupalSettings' => [
          'ajax' => 'test',
        ],
      ],
    ];
  }

  /**
   * Returns the used theme.
   */
  public function theme() {
    return [
      '#markup' => 'Current theme: ' . \Drupal::theme()->getActiveTheme()->getName(),
    ];
  }

  /**
   * Returns an AjaxResponse; settings command set last.
   *
   * Helps verifying AjaxResponse reorders commands to ensure correct execution.
   */
  public function order() {
    $response = new AjaxResponse();
    // HTML insertion command.
    $response->addCommand(new HtmlCommand('body', 'Hello, world!'));
    $build['#attached']['library'][] = 'ajax_test/order';
    $response->setAttachments($build['#attached']);

    return $response;
  }

  /**
   * Returns an AjaxResponse with alert command.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The JSON response object.
   */
  public function renderError(Request $request) {
    $message = '';
    $query = $request->query;
    if ($query->has('message')) {
      $message = $query->get('message');
    }
    $response = new AjaxResponse();
    $response->addCommand(new AlertCommand($message));
    return $response;
  }

  /**
   * Returns a render array of form elements and links for dialog.
   */
  public function dialog() {
    // Add two wrapper elements for testing non-modal dialogs. Modal dialogs use
    // the global drupal-modal wrapper by default.
    $build['dialog_wrappers'] = ['#markup' => '<div id="ajax-test-dialog-wrapper-1"></div><div id="ajax-test-dialog-wrapper-2"></div>'];

    // Dialog behavior applied to a button.
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\ajax_test\Form\AjaxTestDialogForm');

    // Dialog behavior applied to a #type => 'link'.
    $build['link'] = [
      '#type' => 'link',
      '#title' => 'Link 1 (modal)',
      '#url' => Url::fromRoute('ajax_test.dialog_contents'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
      ],
    ];

    // Dialog behavior applied to links rendered by links.html.twig.
    $build['links'] = [
      '#theme' => 'links',
      '#links' => [
        'link2' => [
          'title' => 'Link 2 (modal)',
          'url' => Url::fromRoute('ajax_test.dialog_contents'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode([
              'width' => 400,
            ]),
          ],
        ],
        'link3' => [
          'title' => 'Link 3 (non-modal)',
          'url' => Url::fromRoute('ajax_test.dialog_contents'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'dialog',
            'data-dialog-options' => json_encode([
              'target' => 'ajax-test-dialog-wrapper-1',
              'width' => 800,
            ]),
          ],
        ],
        'link4' => [
          'title' => 'Link 4 (close non-modal if open)',
          'url' => Url::fromRoute('ajax_test.dialog_close'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ],
        'link5' => [
          'title' => 'Link 5 (form)',
          'url' => Url::fromRoute('ajax_test.dialog_form'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
          ],
        ],
        'link6' => [
          'title' => 'Link 6 (entity form)',
          'url' => Url::fromRoute('contact.form_add'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode([
              'width' => 800,
              'height' => 500,
            ]),
          ],
        ],
        'link7' => [
          'title' => 'Link 7 (non-modal, no target)',
          'url' => Url::fromRoute('ajax_test.dialog_contents'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'dialog',
            'data-dialog-options' => json_encode([
              'width' => 800,
            ]),
          ],
        ],
        'link8' => [
          'title' => 'Link 8 (ajax)',
          'url' => Url::fromRoute('ajax_test.admin.theme'),
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode([
              'width' => 400,
            ]),
          ],
        ],
      ],
    ];

    return $build;
  }

  /**
   * Returns an AjaxResponse with command to close dialog.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The JSON response object.
   */
  public function dialogClose() {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand('#ajax-test-dialog-wrapper-1'));
    return $response;
  }

  /**
   * Render types.
   *
   * @return array
   *   Render types.
   */
  protected function getRenderTypes() {
    $render_single_root = [
      'pre-wrapped-div' => '<div class="pre-wrapped">pre-wrapped<script> var test;</script></div>',
      'pre-wrapped-span' => '<span class="pre-wrapped">pre-wrapped<script> var test;</script></span>',
      'pre-wrapped-whitespace' => ' <div class="pre-wrapped-whitespace">pre-wrapped-whitespace</div>' . "\r\n",
      'not-wrapped' => 'not-wrapped',
      'comment-string-not-wrapped' => '<!-- COMMENT -->comment-string-not-wrapped',
      'comment-not-wrapped' => '<!-- COMMENT --><div class="comment-not-wrapped">comment-not-wrapped</div>',
      'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><rect x="0" y="0" height="10" width="10" fill="green"></rect></svg>',
      'empty' => '',
    ];
    $render_multiple_root = [
      'mixed' => ' foo <!-- COMMENT -->  foo bar<div class="a class"><p>some string</p></div> additional not wrapped strings, <!-- ANOTHER COMMENT --> <p>final string</p>',
      'top-level-only' => '<div>element #1</div><div>element #2</div>',
      'top-level-only-pre-whitespace' => ' <div>element #1</div><div>element #2</div> ',
      'top-level-only-middle-whitespace-span' => '<span>element #1</span> <span>element #2</span>',
      'top-level-only-middle-whitespace-div' => '<div>element #1</div> <div>element #2</div>',
    ];

    $render_info = [];
    foreach ($render_single_root as $key => $render) {
      $render_info[$key] = ['render' => $render, 'effect' => 'fade'];
    }
    foreach ($render_multiple_root as $key => $render) {
      $render_info[$key] = ['render' => $render, 'effect' => 'none'];
      $render_info["$key--effect"] = ['render' => $render, 'effect' => 'fade'];
    }

    return $render_info;
  }

  /**
   * Returns a page from which to test Ajax global events.
   *
   * @return array
   *   The render array.
   */
  public function globalEvents() {
    return [
      '#attached' => [
        'library' => [
          'ajax_test/global_events',
        ],
      ],
      '#markup' => implode('', [
        '<div id="test_global_events_log"></div>',
        '<a id="test_global_events_drupal_ajax_link" class="use-ajax" href="' . Url::fromRoute('ajax_test.global_events_clear_log')->toString() . '">Drupal Ajax</a>',
        '<div id="test_global_events_log2"></div>',
      ]),
    ];
  }

  /**
   * Returns an AjaxResponse with command to clear the 'test_global_events_log'.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The JSON response object.
   */
  public function globalEventsClearLog() {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#test_global_events_log', ''));
    return $response;
  }

  /**
   * Callback to provide an exception via Ajax.
   *
   * @throws \Exception
   *   The expected exception.
   */
  public function throwException() {
    throw new \Exception('This is an exception.');
  }

  /**
   * Provides an Ajax link for the exception.
   *
   * @return array
   *   The Ajax link.
   */
  public function exceptionLink() {
    return [
      '#type' => 'link',
      '#url' => Url::fromRoute('ajax_test.throw_exception'),
      '#title' => 'Ajax Exception',
      '#attributes' => [
        'class' => ['use-ajax'],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.ajax',
        ],
      ],
    ];
  }

  /**
   * Provides an Ajax link used with different HTTP methods.
   *
   * @return array
   *   The AJAX link.
   */
  public function httpMethods(): array {
    return [
      '#type' => 'link',
      '#title' => 'Link',
      '#url' => Url::fromRoute('ajax_test.http_methods.dialog'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 800]),
        // Use this state var to change the HTTP method in tests.
        // @see \Drupal\FunctionalJavascriptTests\Ajax\DialogTest::testHttpMethod()
        'data-ajax-http-method' => \Drupal::state()->get('ajax_test.http_method', 'POST'),
      ],
      '#attached' => [
        'library' => [
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
  }

  /**
   * Provides a modal dialog to test links with different HTTP methods.
   *
   * @return array
   *   The render array.
   */
  public function httpMethodsDialog(): array {
    return ['#markup' => 'Modal dialog contents'];
  }

}
