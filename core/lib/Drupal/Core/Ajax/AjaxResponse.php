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
   *
   * @return AjaxResponse
   *   The current AjaxResponse.
   */
  public function addCommand($command) {
    $this->commands[] = $command->render();
    return $this;
  }

  /**
   * Sets the response's data to be the array of AJAX commands.
   *
   * @param
   *   $request A request object.
   *
   * @return
   *   Response The current response.
   */
  public function prepare(Request $request) {

    parent::setData($this->ajaxRender($request));
    return parent::prepare($request);
  }

  /**
   * Prepares the AJAX commands for sending back to the client.
   *
   * @param Request
   *   The request object that the AJAX is responding to.
   *
   * @return array
   *   An array of commands ready to be returned as JSON.
   */
  protected function ajaxRender($request) {
    // Ajax responses aren't rendered with html.tpl.php, so we have to call
    // drupal_get_css() and drupal_get_js() here, in order to have new files
    // added during this request to be loaded by the page. We only want to send
    // back files that the page hasn't already loaded, so we implement simple
    // diffing logic using array_diff_key().
    foreach (array('css', 'js') as $type) {
      // It is highly suspicious if $_POST['ajax_page_state'][$type] is empty,
      // since the base page ought to have at least one JS file and one CSS file
      // loaded. It probably indicates an error, and rather than making the page
      // reload all of the files, instead we return no new files.
      if (empty($request->parameters['ajax_page_state'][$type])) {
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
        //   them when drupal_add_css() and drupal_add_js() are changed to using
        //   md5() or some other hash of the inline content.
        foreach ($items[$type] as $key => $item) {
          if (is_numeric($key)) {
            unset($items[$type][$key]);
          }
        }
        // Ensure that the page doesn't reload what it already has.
        $items[$type] = array_diff_key($items[$type], $request->parameters['ajax_page_state'][$type]);
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
    $scripts_footer = drupal_get_js('footer', $items['js'], TRUE);
    $scripts_header = drupal_get_js('header', $items['js'], TRUE);

    if (!empty($styles)) {
      $this->addCommand(new AddCssCommand($styles));
    }
    if (!empty($scripts_header)) {
      $this->addCommand(new PrependCommand('head', $scripts_header));
    }
    if (!empty($scripts_footer)) {
      $this->addCommand(new AppendCommand('body', $scripts_footer));
    }

    // Now add a command to merge changes and additions to Drupal.settings.
    $scripts = drupal_add_js();
    if (!empty($scripts['settings'])) {
      $settings = $scripts['settings'];
      $this->addCommand(new SettingsCommand(call_user_func_array('array_merge_recursive', $settings['data']), TRUE));
    }

    $commands = $this->commands;
    drupal_alter('ajax_render', $commands);

    return $commands;
  }

}
