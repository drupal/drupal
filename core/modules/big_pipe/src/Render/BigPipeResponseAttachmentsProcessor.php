<?php

namespace Drupal\big_pipe\Render;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses with BigPipe enabled.
 *
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 * @see \Drupal\big_pipe\Render\BigPipe
 */
class BigPipeResponseAttachmentsProcessor extends HtmlResponseAttachmentsProcessor {

  /**
   * The HTML response attachments processor service.
   *
   * @var \Drupal\Core\Render\AttachmentsResponseProcessorInterface
   */
  protected $htmlResponseAttachmentsProcessor;

  /**
   * Constructs a BigPipeResponseAttachmentsProcessor object.
   *
   * @param \Drupal\Core\Render\AttachmentsResponseProcessorInterface $html_response_attachments_processor
   *   The HTML response attachments processor service.
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(AttachmentsResponseProcessorInterface $html_response_attachments_processor, AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer, ModuleHandlerInterface $module_handler) {
    $this->htmlResponseAttachmentsProcessor = $html_response_attachments_processor;
    parent::__construct($asset_resolver, $config_factory, $css_collection_renderer, $js_collection_renderer, $request_stack, $renderer, $module_handler);
  }

  /**
   * {@inheritdoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    assert($response instanceof HtmlResponse);

    // First, render the actual placeholders; this will cause the BigPipe
    // placeholder strategy to generate BigPipe placeholders. We need those to
    // exist already so that we can extract BigPipe placeholders. This is hence
    // a bit of unfortunate but necessary duplication.
    // @see \Drupal\big_pipe\Render\Placeholder\BigPipeStrategy
    // (Note this is copied verbatim from
    // \Drupal\Core\Render\HtmlResponseAttachmentsProcessor::processAttachments)
    try {
      $response = $this->renderPlaceholders($response);
    }
    catch (EnforcedResponseException $e) {
      return $e->getResponse();
    }

    // Extract BigPipe placeholders; HtmlResponseAttachmentsProcessor does not
    // know (nor need to know) how to process those.
    $attachments = $response->getAttachments();
    $big_pipe_placeholders = [];
    $big_pipe_nojs_placeholders = [];
    if (isset($attachments['big_pipe_placeholders'])) {
      $big_pipe_placeholders = $attachments['big_pipe_placeholders'];
      unset($attachments['big_pipe_placeholders']);
    }
    if (isset($attachments['big_pipe_nojs_placeholders'])) {
      $big_pipe_nojs_placeholders = $attachments['big_pipe_nojs_placeholders'];
      unset($attachments['big_pipe_nojs_placeholders']);
    }
    $html_response = clone $response;
    $html_response->setAttachments($attachments);

    // Call HtmlResponseAttachmentsProcessor to process all other attachments.
    $processed_html_response = $this->htmlResponseAttachmentsProcessor->processAttachments($html_response);

    // Restore BigPipe placeholders.
    $attachments = $processed_html_response->getAttachments();
    $big_pipe_response = clone $processed_html_response;
    if (count($big_pipe_placeholders)) {
      $attachments['big_pipe_placeholders'] = $big_pipe_placeholders;
    }
    if (count($big_pipe_nojs_placeholders)) {
      $attachments['big_pipe_nojs_placeholders'] = $big_pipe_nojs_placeholders;
    }
    $big_pipe_response->setAttachments($attachments);

    return $big_pipe_response;
  }

}
