<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\AjaxResponse.
 */

namespace Drupal\Core\Ajax;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON response object for AJAX requests.
 *
 * @ingroup ajax
 */
class AjaxResponse extends JsonResponse {

  /**
   * The array of ajax commands.
   *
   * @var array
   */
  protected $commands = array();

  /**
   * Add an AJAX command to the response.
   *
   * @param \Drupal\Core\Ajax\CommandInterface $command
   *   An AJAX command object implementing CommandInterface.
   * @param boolean $prepend
   *   A boolean which determines whether the new command should be executed
   *   before previously added commands. Defaults to FALSE.
   *
   * @return AjaxResponse
   *   The current AjaxResponse.
   */
  public function addCommand(CommandInterface $command, $prepend = FALSE) {
    if ($prepend) {
      array_unshift($this->commands, $command->render());
    }
    else {
      $this->commands[] = $command->render();
    }

    return $this;
  }

  /**
   * Gets all AJAX commands.
   *
   * @return \Drupal\Core\Ajax\CommandInterface[]
   *   Returns all previously added AJAX commands.
   */
  public function &getCommands() {
    return $this->commands;
  }

  /**
   * {@inheritdoc}
   *
   * Sets the response's data to be the array of AJAX commands.
   */
  public function prepare(Request $request) {
    $this->prepareResponse($request);
    return $this;
  }

  /**
   * Sets the rendered AJAX right before the response is prepared.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function prepareResponse(Request $request) {
    if ($this->data == '{}') {
      $this->setData($this->ajaxRender($request));
    }
  }

  /**
   * Prepares the AJAX commands for sending back to the client.
   *
   * @param Request $request
   *   The request object that the AJAX is responding to.
   *
   * @return array
   *   An array of commands ready to be returned as JSON.
   */
  protected function ajaxRender(Request $request) {
    // Ajax responses aren't rendered with html.html.twig, so we have to call
    // drupal_get_css() and drupal_get_js() here, in order to have new files
    // added during this request to be loaded by the page. We only want to send
    // back files that the page hasn't already loaded, so we implement simple
    // diffing logic using array_diff_key().
    $ajax_page_state = $request->request->get('ajax_page_state');
    foreach (array('css', 'js') as $type) {
      // It is highly suspicious if
      // $request->request->get("ajax_page_state[$type]") is empty, since the
      // base page ought to have at least one JS file and one CSS file loaded.
      // It probably indicates an error, and rather than making the page reload
      // all of the files, instead we return no new files.
      if (empty($ajax_page_state[$type])) {
        $items[$type] = array();
      }
      else {
        $function = '_drupal_add_' . $type;
        $items[$type] = $function();
        \Drupal::moduleHandler()->alter($type, $items[$type]);
        // @todo Inline CSS and JS items are indexed numerically. These can't be
        //   reliably diffed with array_diff_key(), since the number can change
        //   due to factors unrelated to the inline content, so for now, we
        //   strip the inline items from Ajax responses, and can add support for
        //   them when _drupal_add_css() and _drupal_add_js() are changed to use
        //   a hash of the inline content as the array key.
        foreach ($items[$type] as $key => $item) {
          if (is_numeric($key)) {
            unset($items[$type][$key]);
          }
        }
        // Ensure that the page doesn't reload what it already has.
        $items[$type] = array_diff_key($items[$type], $ajax_page_state[$type]);
      }
    }

    // Render the HTML to load these files, and add AJAX commands to insert this
    // HTML in the page. We pass TRUE as the $skip_alter argument to prevent the
    // data from being altered again, as we already altered it above. Settings
    // are handled separately, afterwards.
    if (isset($items['js']['settings'])) {
      unset($items['js']['settings']);
    }
    $styles = drupal_get_css($items['css'], TRUE);
    $scripts_footer = drupal_get_js('footer', $items['js'], TRUE, TRUE);
    $scripts_header = drupal_get_js('header', $items['js'], TRUE, TRUE);

    // Prepend commands to add the resources, preserving their relative order.
    $resource_commands = array();
    if (!empty($styles)) {
      $resource_commands[] = new AddCssCommand($styles);
    }
    if (!empty($scripts_header)) {
      $resource_commands[] = new PrependCommand('head', $scripts_header);
    }
    if (!empty($scripts_footer)) {
      $resource_commands[] = new AppendCommand('body', $scripts_footer);
    }
    foreach (array_reverse($resource_commands) as $resource_command) {
      $this->addCommand($resource_command, TRUE);
    }

    // Prepend a command to merge changes and additions to drupalSettings.
    $scripts = _drupal_add_js();
    if (!empty($scripts['settings'])) {
      $settings = drupal_merge_js_settings($scripts['settings']['data']);
      // During Ajax requests basic path-specific settings are excluded from
      // new drupalSettings values. The original page where this request comes
      // from already has the right values for the keys below. An Ajax request
      // would update them with values for the Ajax request and incorrectly
      // override the page's values.
      // @see _drupal_add_js()
      foreach (array('basePath', 'currentPath', 'scriptPath', 'pathPrefix') as $item) {
        unset($settings[$item]);
      }
      $this->addCommand(new SettingsCommand($settings, TRUE), TRUE);
    }

    $commands = $this->commands;
    \Drupal::moduleHandler()->alter('ajax_render', $commands);

    return $commands;
  }

}
