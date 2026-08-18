<?php

namespace Drupal\big_pipe\Render;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Processes attachments of HTML responses with BigPipe enabled.
 *
 * @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
 * @see \Drupal\big_pipe\Render\BigPipe
 */
class BigPipeResponseAttachmentsProcessor extends HtmlResponseAttachmentsProcessor {

  public function __construct(
    protected AttachmentsResponseProcessorInterface $htmlResponseAttachmentsProcessor,
    AssetResolverInterface $asset_resolver,
    ConfigFactoryInterface $config_factory,
    #[Autowire(service: 'asset.css.collection_renderer')]
    AssetCollectionRendererInterface $css_collection_renderer,
    #[Autowire(service: 'asset.js.collection_renderer')]
    AssetCollectionRendererInterface $js_collection_renderer,
    RequestStack $request_stack,
    RendererInterface $renderer,
    ModuleHandlerInterface $module_handler,
    LanguageManagerInterface $language_manager,
    ?FileUrlGeneratorInterface $file_url_generator = NULL,
  ) {
    if (!isset($file_url_generator)) {
      $file_url_generator = \Drupal::service('file_url_generator');
      @trigger_error('Constructing BigPipeResponseAttachmentsProcessor without a file url generator is deprecated in drupal:11.4.0 and the argument will be required in drupal:12.0.0. See https://www.drupal.org/node/3585574', E_USER_DEPRECATED);
    }

    parent::__construct($asset_resolver, $config_factory, $css_collection_renderer, $js_collection_renderer, $request_stack, $renderer, $module_handler, $language_manager, $file_url_generator);
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
