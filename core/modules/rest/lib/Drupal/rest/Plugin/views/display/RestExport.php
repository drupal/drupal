<?php

/**
 * @file
 * Contains \Drupal\rest\Plugin\views\display\RestExport.
 */

namespace Drupal\rest\Plugin\views\display;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\PathPluginBase;

/**
 * The plugin that handles Data response callbacks for REST resources.
 *
 * @ingroup views_display_plugins
 *
 * @Plugin(
 *   id = "rest_export",
 *   module = "rest",
 *   title = @Translation("REST export"),
 *   help = @Translation("Create a REST export resource."),
 *   uses_route = TRUE,
 *   admin = @Translation("REST export")
 * )
 */
class RestExport extends PathPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAJAX.
   */
  protected $usesAJAX = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesPager.
   */
  protected $usesPager = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesMore.
   */
  protected $usesMore = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAreas.
   */
  protected $usesAreas = FALSE;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::$usesAreas.
   */
  protected $usesOptions = FALSE;

  /**
   * Overrides the content type of the data response, if needed.
   *
   * @var string
   */
  protected $contentType = 'json';

  /**
   * The mime type for the response.
   *
   * @var string
   */
  protected $mimeType;

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::initDisplay().
   */
  public function initDisplay(ViewExecutable $view, array &$display, array &$options = NULL) {
    parent::initDisplay($view, $display, $options);

    $container = drupal_container();
    $negotiation = $container->get('content_negotiation');
    $request = $container->get('request');

    $request_content_type = $negotiation->getContentType($request);
    // Only use the requested content type if it's not 'html'. If it is then
    // default to 'json' to aid debugging.
    if ($request_content_type !== 'html') {
      $this->setContentType($request_content_type);
    }

    $this->setMimeType($request->getMimeType($this->contentType));
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::getType().
   */
  protected function getType() {
    return 'data';
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::usesExposed().
   */
  public function usesExposed() {
    return FALSE;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::displaysExposed().
   */
  public function displaysExposed() {
    return FALSE;
  }

  /**
   * Sets the request content type.
   *
   * @param string $mime_type
   *   The response mime type. E.g. 'application/json'.
   */
  public function setMimeType($mime_type) {
    $this->mimeType = $mime_type;
  }

  /**
   * Gets the mime type.
   *
   * This will return any overriden mime type, otherwise returns the mime type
   * from the request.
   *
   * @return string
   *   The response mime type. E.g. 'application/json'.
   */
  public function getMimeType() {
    return $this->mimeType;
  }

  /**
   * Sets the content type.
   *
   * @param string $content_type
   *   The content type machine name. E.g. 'json'.
   */
  public function setContentType($content_type) {
    $this->contentType = $content_type;
  }

  /**
   * Gets the content type.
   *
   * @return string
   *   The content type machine name. E.g. 'json'.
   */
  public function getContentType() {
    return $this->contentType;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Set the default style plugin to 'json'.
    $options['style']['contains']['type']['default'] = 'serializer';
    $options['row']['contains']['type']['default'] = 'data_entity';
    $options['defaults']['default']['style'] = FALSE;
    $options['defaults']['default']['row'] = FALSE;

    // Remove css/exposed form settings, as they are not used for the data display.
    unset($options['exposed_form']);
    unset($options['exposed_block']);
    unset($options['css_class']);

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    unset($categories['page'], $categories['exposed']);
    // Hide some settings, as they aren't useful for pure data output.
    unset($options['show_admin_links'], $options['analyze-theme']);

    $categories['path'] = array(
      'title' => t('Path settings'),
      'column' => 'second',
      'build' => array(
        '#weight' => -10,
      ),
    );

    $options['path']['category'] = 'path';
    $options['path']['title'] = t('Path');

    // Remove css/exposed form settings, as they are not used for the data
    // display.
    unset($options['exposed_form']);
    unset($options['exposed_block']);
    unset($options['css_class']);
  }



  /**
   * Overrides \Drupal\views\Plugin\views\display\PathPluginBase::execute().
   */
  public function execute() {
    parent::execute();

    $output = $this->view->render();
    return new Response(drupal_render($output), 200, array('Content-type' => $this->getMimeType()));
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::render().
   */
  public function render() {
    $build = array();
    $build['#markup'] = $this->view->style_plugin->render();

    // Wrap the output in a pre tag if this is for a live preview.
    if (!empty($this->view->live_preview)) {
      $build['#prefix'] = '<pre>';
      $build['#markup'] = check_plain($build['#markup']);
      $build['#suffix'] = '</pre>';
    }

    return $build;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::preview().
   *
   * The DisplayPluginBase preview method assumes we will be returning a render
   * array. The data plugin will already return the serialized string.
   */
  public function preview() {
    return $this->view->render();
  }

}
