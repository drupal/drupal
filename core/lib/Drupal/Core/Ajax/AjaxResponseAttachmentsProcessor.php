<?php

namespace Drupal\Core\Ajax;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of AJAX responses.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse
 * @see \Drupal\Core\Render\MainContent\AjaxRenderer
 */
class AjaxResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs an AjaxResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Asset\AssetResolverInterface $assetResolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $cssCollectionRenderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $jsCollectionRenderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    protected AssetResolverInterface $assetResolver,
    protected ConfigFactoryInterface $config_factory,
    protected AssetCollectionRendererInterface $cssCollectionRenderer,
    protected AssetCollectionRendererInterface $jsCollectionRenderer,
    protected RequestStack $requestStack,
    protected RendererInterface $renderer,
    protected ModuleHandlerInterface $moduleHandler,
    protected LanguageManagerInterface $languageManager,
  ) {
    $this->config = $config_factory->get('system.performance');
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    assert($response instanceof AjaxResponse, '\Drupal\Core\Ajax\AjaxResponse instance expected.');

    $request = $this->requestStack->getCurrentRequest();

    if ($response->getContent() == '{}') {
      $response->setData($this->buildAttachmentsCommands($response, $request));
    }

    return $response;
  }

  /**
   * Prepares the AJAX commands to attach assets.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response to update.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object that the AJAX is responding to.
   *
   * @return array
   *   An array of commands ready to be returned as JSON.
   */
  protected function buildAttachmentsCommands(AjaxResponse $response, Request $request) {
    $ajax_page_state = $request->get('ajax_page_state');
    $maintenance_mode = defined('MAINTENANCE_MODE') || \Drupal::state()->get('system.maintenance_mode');

    // Aggregate CSS/JS if necessary, but only during normal site operation.
    $optimize_css = !$maintenance_mode && $this->config->get('css.preprocess');
    $optimize_js = !$maintenance_mode && $this->config->get('js.preprocess');

    $attachments = $response->getAttachments();

    // Resolve the attached libraries into asset collections.
    $assets = new AttachedAssets();
    $assets->setLibraries($attachments['library'] ?? [])
      ->setAlreadyLoadedLibraries(isset($ajax_page_state['libraries']) ? explode(',', $ajax_page_state['libraries']) : [])
      ->setSettings($attachments['drupalSettings'] ?? []);
    $css_assets = $this->assetResolver->getCssAssets($assets, $optimize_css, $this->languageManager->getCurrentLanguage());
    [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($assets, $optimize_js, $this->languageManager->getCurrentLanguage());

    // First, AttachedAssets::setLibraries() ensures duplicate libraries are
    // removed: it converts it to a set of libraries if necessary. Second,
    // AssetResolver::getJsSettings() ensures $assets contains the final set of
    // JavaScript settings. AttachmentsResponseProcessorInterface also mandates
    // that the response it processes contains the final attachment values, so
    // update both the 'library' and 'drupalSettings' attachments accordingly.
    $attachments['library'] = $assets->getLibraries();
    $attachments['drupalSettings'] = $assets->getSettings();
    $response->setAttachments($attachments);

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
    $resource_commands = [];
    if ($css_assets) {
      $css_render_array = $this->cssCollectionRenderer->render($css_assets);
      $resource_commands[] = new AddCssCommand(array_column($css_render_array, '#attributes'));
    }
    if ($js_assets_header) {
      $js_header_render_array = $this->jsCollectionRenderer->render($js_assets_header);
      $resource_commands[] = new AddJsCommand(array_column($js_header_render_array, '#attributes'), 'head');
    }
    if ($js_assets_footer) {
      $js_footer_render_array = $this->jsCollectionRenderer->render($js_assets_footer);
      $resource_commands[] = new AddJsCommand(array_column($js_footer_render_array, '#attributes'));
    }
    foreach (array_reverse($resource_commands) as $resource_command) {
      $response->addCommand($resource_command, TRUE);
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
      $response->addCommand(new SettingsCommand($settings, TRUE), TRUE);
    }

    $commands = $response->getCommands();
    $this->moduleHandler->alter('ajax_render', $commands);

    return $commands;
  }

}
