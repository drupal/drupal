<?php

/**
 * @file
 * Contains Drupal\language\HttpKernel\PathProcessorLanguage.
 */

namespace Drupal\language\HttpKernel;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessorLanguage implements InboundPathProcessorInterface {

  protected $moduleHandler;

  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processInbound().
   */
  public function processInbound($path, Request $request) {
    include_once DRUPAL_ROOT . '/core/includes/language.inc';
    $this->moduleHandler->loadInclude('language', 'inc', 'language.negotiation');
    $languages = language_list();
    list($language, $path) = language_url_split_prefix($path, $languages);
    return $path;
  }

}
