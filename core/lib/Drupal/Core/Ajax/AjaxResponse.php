<?php

/**
 * @file
 * Definition of Drupal\Core\Ajax\AjaxResponse.
 */

namespace Drupal\Core\Ajax;

use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Render\Renderer;
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
   * The attachments for this Ajax response.
   *
   * @var array
   */
  protected $attachments = [
    'library' => [],
    'drupalSettings' => [],
  ];

  /**
   * Sets attachments for this Ajax response.
   *
   * When this Ajax response is rendered, it will take care of generating the
   * necessary Ajax commands, if any.
   *
   * @param array $attachments
   *   An #attached array.
   *
   * @return $this
   */
  public function setAttachments(array $attachments) {
    $this->attachments = $attachments;
    return $this;
  }

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
    if ($command instanceof CommandWithAttachedAssetsInterface) {
      $assets = $command->getAttachedAssets();
      $attachments = [
        'library' => $assets->getLibraries(),
        'drupalSettings' => $assets->getSettings(),
      ];
      $attachments = Renderer::mergeAttachments($this->attachments, $attachments);
      $this->setAttachments($attachments);
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
    $ajax_page_state = $request->request->get('ajax_page_state');

    // Aggregate CSS/JS if necessary, but only during normal site operation.
    $config = \Drupal::config('system.performance');
    $optimize_css = !defined('MAINTENANCE_MODE') && $config->get('css.preprocess');
    $optimize_js = !defined('MAINTENANCE_MODE') && $config->get('js.preprocess');

    // Resolve the attached libraries into asset collections.
    $assets = new AttachedAssets();
    $assets->setLibraries(isset($this->attachments['library']) ? $this->attachments['library'] : [])
      ->setAlreadyLoadedLibraries(isset($ajax_page_state) ? explode(',', $ajax_page_state['libraries']) : [])
      ->setSettings(isset($this->attachments['drupalSettings']) ? $this->attachments['drupalSettings'] : []);
    $asset_resolver = \Drupal::service('asset.resolver');
    $css_assets = $asset_resolver->getCssAssets($assets, $optimize_css);
    list($js_assets_header, $js_assets_footer) = $asset_resolver->getJsAssets($assets, $optimize_js);

    // Render the HTML to load these files, and add AJAX commands to insert this
    // HTML in the page. Settings are handled separately, afterwards.
    $settings = [];
    if (isset($js_assets_header['drupalSettings'])) {
      $settings = $js_assets_header['drupalSettings']['data'];
      unset($js_assets_header['drupalSettings']);
    }
    if (isset($js_assets_footer['drupalSettings'])) {
      $settings = $js_assets_footer['drupalSettings']['data'];
      unset($js_assets_footer['drupalSettings']);
    }

    // Prepend commands to add the assets, preserving their relative order.
    $resource_commands = array();
    $renderer = \Drupal::service('renderer');
    if (!empty($css_assets)) {
      $css_render_array = \Drupal::service('asset.css.collection_renderer')->render($css_assets);
      $resource_commands[] = new AddCssCommand($renderer->render($css_render_array));
    }
    if (!empty($js_assets_header)) {
      $js_header_render_array = \Drupal::service('asset.js.collection_renderer')->render($js_assets_header);
      $resource_commands[] = new PrependCommand('head', $renderer->render($js_header_render_array));
    }
    if (!empty($js_assets_footer)) {
      $js_footer_render_array = \Drupal::service('asset.js.collection_renderer')->render($js_assets_footer);
      $resource_commands[] = new AppendCommand('body', $renderer->render($js_footer_render_array));
    }
    foreach (array_reverse($resource_commands) as $resource_command) {
      $this->addCommand($resource_command, TRUE);
    }

    // Prepend a command to merge changes and additions to drupalSettings.
    if (!empty($settings)) {
      // During Ajax requests basic path-specific settings are excluded from
      // new drupalSettings values. The original page where this request comes
      // from already has the right values. An Ajax request would update them
      // with values for the Ajax request and incorrectly override the page's
      // values.
      // @see system_js_settings_alter()
      unset($settings['path']);
      $this->addCommand(new SettingsCommand($settings, TRUE), TRUE);
    }

    $commands = $this->commands;
    \Drupal::moduleHandler()->alter('ajax_render', $commands);

    return $commands;
  }

}
