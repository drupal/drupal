<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\AjaxResponse.
 */

namespace Drupal\Core\Ajax;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * JSON response object for AJAX requests.
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
   * @param object $command
   *   An AJAX command object implementing CommandInterface.
   * @param boolean $prepend
   *   A boolean which determines whether the new command should be executed
   *   before previously added commands. Defaults to FALSE.
   *
   * @return AjaxResponse
   *   The current AjaxResponse.
   */
  public function addCommand($command, $prepend = FALSE) {
    if ($prepend) {
      array_unshift($this->commands, $command->render());
    }
    else {
      $this->commands[] = $command->render();
    }

    return $this;
  }

  /**
   * Sets the response's data to be the array of AJAX commands.
   *
   * @param Request $request
   *   A request object.
   *
   * @return Response
   *   The current response.
   */
  public function prepare(Request $request) {
    $this->setData($this->ajaxRender($request));
    return parent::prepare($request);
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
    // Ajax responses aren't rendered with html.tpl.php, so we have to call
    // drupal_get_css() and drupal_get_js() here, in order to have new files
    // added during this request to be loaded by the page. We only want to send
    // back files that the page hasn't already loaded, so we implement simple
    // diffing logic using array_diff_key().
    $ajax_page_state = $request->request->get('ajax_page_state');
    foreach (array('css', 'js') as $type) {
      // It is highly suspicious if $_POST['ajax_page_state'][$type] is empty,
      // since the base page ought to have at least one JS file and one CSS file
      // loaded. It probably indicates an error, and rather than making the page
      // reload all of the files, instead we return no new files.
      if (empty($ajax_page_state[$type])) {
        $items[$type] = array();
      }
      else {
        $function = 'drupal_add_' . $type;
        $items[$type] = $function();
        drupal_alter($type, $items[$type]);
        // @todo Inline CSS and JS items are indexed numerically. These can't be
        //   reliably diffed with array_diff_key(), since the number can change
        //   due to factors unrelated to the inline content, so for now, we
        //   strip the inline items from Ajax responses, and can add support for
        //   them when drupal_add_css() and drupal_add_js() are changed to use
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

    // Prepend a command to merge changes and additions to Drupal.settings.
    $scripts = drupal_add_js();
    if (!empty($scripts['settings'])) {
      $settings = drupal_merge_js_settings($scripts['settings']['data']);
      $this->addCommand(new SettingsCommand($settings, TRUE), TRUE);
    }

    $commands = $this->commands;
    drupal_alter('ajax_render', $commands);

    return $commands;
  }

}
