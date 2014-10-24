<?php

/**
 * @file
 * Contains \Drupal\Core\Ajax\AjaxResponseRenderer.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Page\HtmlFragment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts a controller result into an Ajax response object.
 */
class AjaxResponseRenderer {

  /**
   * Converts the output of a controller into an Ajax response object.
   *
   * @var mixed $content
   *   The return value of a controller, for example a string, a render array, a
   *   HtmlFragment object, a Response object or even an AjaxResponse itself.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response containing the controller result.
   */
  public function render($content) {
    // If there is already a Response object, return it without manipulation.
    if ($content instanceof Response && $content->isOk()) {
      return $content;
    }

    // Allow controllers to return an HtmlFragment directly.
    if ($content instanceof HtmlFragment) {
      $content = $content->getContent();
    }
    // Most controllers return a render array, but some return a string.
    if (!is_array($content)) {
      $content = array(
        '#markup' => $content,
      );
    }

    $response = new AjaxResponse();

    if (isset($content['#type']) && ($content['#type'] == 'ajax')) {
      // Complex Ajax callbacks can return a result that contains an error
      // message or a specific set of commands to send to the browser.
      $content += $this->elementInfo('ajax');
      $error = $content['#error'];
      if (!empty($error)) {
        // Fall back to some default message otherwise use the specific one.
        if (!is_string($error)) {
          $error = 'An error occurred while handling the request: The server received invalid input.';
        }
        $response->addCommand(new AlertCommand($error));
      }
    }

    $html = $this->drupalRenderRoot($content);

    // The selector for the insert command is NULL as the new content will
    // replace the element making the Ajax call. The default 'replaceWith'
    // behavior can be changed with #ajax['method'].
    $response->addCommand(new InsertCommand(NULL, $html));
    $status_messages = array('#theme' => 'status_messages');
    $output = $this->drupalRenderRoot($status_messages);
    if (!empty($output)) {
      $response->addCommand(new PrependCommand(NULL, $output));
    }
    return $response;
  }

  /**
   * Wraps drupal_render_root().
   *
   * @todo: Remove as part of https://drupal.org/node/2182149
   */
  protected function drupalRenderRoot(&$elements) {
    $output = drupal_render_root($elements);
    drupal_process_attached($elements);
    return $output;
  }

  /**
   * Wraps element_info().
   */
  protected function elementInfo($type) {
    return element_info($type);
  }

}
