<?php

/**
 * @file
 * Contains \Drupal\ajax_test\Controller\AjaxTestController.
 */

namespace Drupal\ajax_test\Controller;

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
   * @todo Remove ajax_test_render().
   */
  public function render() {
    return ajax_test_render();
  }

  /**
   * @todo Remove ajax_test_order().
   */
  public function order() {
    return ajax_test_order();
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
      '#href' => 'ajax-test/dialog-contents',
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
          'href' => 'ajax-test/dialog-contents',
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
          'href' => 'ajax-test/dialog-contents',
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
          'href' => 'ajax-test/dialog-close',
          'attributes' => array(
            'class' => array('use-ajax'),
          ),
        ),
        'link5' => array(
          'title' => 'Link 5 (form)',
          'href' => 'ajax-test/dialog-form',
          'attributes' => array(
            'class' => array('use-ajax'),
            'data-accepts' => 'application/vnd.drupal-modal',
          ),
        ),
        'link6' => array(
          'title' => 'Link 6 (entity form)',
          'href' => 'admin/structure/contact/add',
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
          'href' => 'ajax-test/dialog-contents',
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
