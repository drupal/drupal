<?php

/**
 * @file
 * Contains \Drupal\ajax_test\Controller\AjaxTestController.
 */

namespace Drupal\ajax_test\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;

/**
 * Provides content for dialog tests.
 */
class AjaxTestController {

  /**
   * Returns example content for dialog testing.
   */
  public function dialogContents() {
    // Re-use the utility method that returns the example content.
    return ajax_test_dialog_contents();
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
   * @todo Remove ajax_test_error().
   */
  public function renderError() {
    return ajax_test_error();
  }

  /**
   * @todo Remove ajax_test_dialog().
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
   * @todo Remove ajax_test_dialog_close().
   */
  public function dialogClose() {
    return ajax_test_dialog_close();
  }

}
