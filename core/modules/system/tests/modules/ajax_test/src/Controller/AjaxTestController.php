<?php

/**
 * @file
 * Contains \Drupal\ajax_test\Controller\AjaxTestController.
 */

namespace Drupal\ajax_test\Controller;

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
    $content = array(
      '#title' => 'AJAX Dialog contents',
      'content' => array(
        '#markup' => 'Example message',
      ),
      'cancel' => array(
        '#type' => 'link',
        '#title' => 'Cancel',
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => array(
          // This is a special class to which JavaScript assigns dialog closing
          // behavior.
          'class' => array('dialog-cancel'),
        ),
      ),
    );

    return $content;
  }

  /**
   * Returns a render array that will be rendered by AjaxRenderer.
   *
   * Ensures that \Drupal\Core\Ajax\AjaxResponse::ajaxRender()
   * incorporates JavaScript settings generated during the page request by
   * adding a dummy setting.
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
    $build['dialog_wrappers'] = array('#markup' => '<div id="ajax-test-dialog-wrapper-1"></div><div id="ajax-test-dialog-wrapper-2"></div>');

    // Dialog behavior applied to a button.
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\ajax_test\Form\AjaxTestDialogForm');

    // Dialog behavior applied to a #type => 'link'.
    $build['link'] = array(
      '#type' => 'link',
      '#title' => 'Link 1 (modal)',
      '#url' => Url::fromRoute('ajax_test.dialog_contents'),
      '#attributes' => array(
        'class' => array('use-ajax'),
        'data-accepts' => 'application/vnd.drupal-modal',
      ),
    );

    // Dialog behavior applied to links rendered by links.html.twig.
    $build['links'] = array(
      '#theme' => 'links',
      '#links' => array(
        'link2' => array(
          'title' => 'Link 2 (modal)',
          'url' => Url::fromRoute('ajax_test.dialog_contents'),
          'attributes' => array(
            'class' => array('use-ajax'),
            'data-accepts' => 'application/vnd.drupal-modal',
            'data-dialog-options' => json_encode(array(
              'width' => 400,
            ))
          ),
        ),
        'link3' => array(
          'title' => 'Link 3 (non-modal)',
          'url' => Url::fromRoute('ajax_test.dialog_contents'),
          'attributes' => array(
            'class' => array('use-ajax'),
            'data-accepts' => 'application/vnd.drupal-dialog',
            'data-dialog-options' => json_encode(array(
              'target' => 'ajax-test-dialog-wrapper-1',
              'width' => 800,
            ))
          ),
        ),
        'link4' => array(
          'title' => 'Link 4 (close non-modal if open)',
          'url' => Url::fromRoute('ajax_test.dialog_close'),
          'attributes' => array(
            'class' => array('use-ajax'),
          ),
        ),
        'link5' => array(
          'title' => 'Link 5 (form)',
          'url' => Url::fromRoute('ajax_test.dialog_form'),
          'attributes' => array(
            'class' => array('use-ajax'),
            'data-accepts' => 'application/vnd.drupal-modal',
          ),
        ),
        'link6' => array(
          'title' => 'Link 6 (entity form)',
          'url' => Url::fromRoute('contact.form_add'),
          'attributes' => array(
            'class' => array('use-ajax'),
            'data-accepts' => 'application/vnd.drupal-modal',
            'data-dialog-options' => json_encode(array(
              'width' => 800,
              'height' => 500,
            ))
          ),
        ),
        'link7' => array(
          'title' => 'Link 7 (non-modal, no target)',
          'url' => Url::fromRoute('ajax_test.dialog_contents'),
          'attributes' => array(
            'class' => array('use-ajax'),
            'data-accepts' => 'application/vnd.drupal-dialog',
            'data-dialog-options' => json_encode(array(
              'width' => 800,
            ))
          ),
        ),
      ),
    );

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

}
