<?php

namespace Drupal\quickedit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an InPlaceEditor annotation object.
 *
 * Plugin Namespace: Plugin\InPlaceEditor
 *
 * For a working example, see \Drupal\quickedit\Plugin\InPlaceEditor\PlainTextEditor
 *
 * @see \Drupal\quickedit\Plugin\InPlaceEditorBase
 * @see \Drupal\quickedit\Plugin\InPlaceEditorInterface
 * @see \Drupal\quickedit\Plugin\InPlaceEditorManager
 * @see plugin_api
 *
 * @Annotation
 */
class InPlaceEditor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * An array of in-place editors plugin IDs that have registered themselves as
   * alternatives to this in-place editor.
   *
   * @var array
   */
  public $alternativeTo;

  /**
   * The name of the module providing the in-place editor plugin.
   *
   * @var string
   */
  public $module;

}
