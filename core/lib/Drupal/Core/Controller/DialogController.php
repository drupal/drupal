<?php

/**
 * @file
 * Contains \Drupal\Core\Controller\DialogController.
 */

namespace Drupal\Core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Defines a default controller for dialog requests.
 */
class DialogController {

  /**
   * The HttpKernel object to use for subrequests.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a new HtmlPageController.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   */
  public function __construct(HttpKernelInterface $kernel) {
    $this->httpKernel = $kernel;
  }

  /**
   * Forwards request to a subrequest.
   *
   * @param \Symfony\Component\HttpFoundation\RequestRequest $request
   *   The request object.
   * @param callable $content
   *   The body content callable that contains the body region of this page.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  protected function forward(Request $request, $content) {
    // @todo When we have a Generator, we can replace the forward() call with
    // a render() call, which would handle ESI and hInclude as well.  That will
    // require an _internal route.  For examples, see:
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Resources/config/routing/internal.xml
    // https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Controller/InternalController.php
    $attributes = clone $request->attributes;
    // We need to clean up the derived information and such so that the
    // subrequest can be processed properly without leaking data through.
    $attributes->remove('system_path');

    // Remove the accept header so the subrequest does not end up back in this
    // controller.
    $request->headers->remove('accept');

    return $this->httpKernel->forward($content, $attributes->all(), $request->query->all());
  }

  /**
   * Displays content in a modal dialog.
   *
   * @param \Symfony\Component\HttpFoundation\RequestRequest $request
   *   The request object.
   * @param callable $_content
   *   The body content callable that contains the body region of this page.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse to return the content wrapper in a modal dialog.
   */
  public function modal(Request $request, $_content) {
    return $this->dialog($request, $_content, TRUE);
  }

  /**
   * Displays content in a dialog.
   *
   * @param \Symfony\Component\HttpFoundation\RequestRequest $request
   *   The request object.
   * @param callable $_content
   *   The body content callable that contains the body region of this page.
   * @param bool $modal
   *   (optional) TRUE to render a modal dialog. Defaults to FALSE.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse to return the content wrapper in a dialog.
   */
  public function dialog(Request $request, $_content, $modal = FALSE) {
    $subrequest = $this->forward($request, $_content);
    if ($subrequest->isOk()) {
      $content = $subrequest->getContent();
      // @todo Remove use of drupal_get_title() when
      //  http://drupal.org/node/1871596 is in.
      $title = drupal_get_title();
      $response = new AjaxResponse();
      // Fetch any modal options passed in from data-dialog-options.
      if (!($options = $request->request->get('dialogOptions'))) {
        $options = array();
      }
      // Set modal flag and re-use the modal ID.
      if ($modal) {
        $options['modal'] = TRUE;
        $target = '#drupal-modal';
      }
      else {
        // Generate the target wrapper for the dialog.
        if (isset($options['target'])) {
          // If the target was nominated in the incoming options, use that.
          $target = $options['target'];
          // Ensure the target includes the #.
          if (substr($target, 0, 1) != '#') {
            $target = '#' . $target;
          }
          // This shouldn't be passed on to jQuery.ui.dialog.
          unset($options['target']);
        }
        else {
          // Generate a target based on the controller.
          $target = '#drupal-dialog-' . drupal_html_id(drupal_clean_css_identifier(drupal_strtolower($_content)));
        }
      }
      $response->addCommand(new OpenDialogCommand($target, $title, $content, $options));
      return $response;
    }
    // An error occurred in the subrequest, return that.
    return $subrequest;
  }
}
