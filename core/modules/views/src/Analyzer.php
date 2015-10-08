<?php

/**
 * @file
 * Contains \Drupal\views\Analyzer.
 */

namespace Drupal\views;

use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * This tool is a small plugin manager to perform analysis on a view and
 * report results to the user. This tool is meant to let modules that
 * provide data to Views also help users properly use that data by
 * detecting invalid configurations. Views itself comes with only a
 * small amount of analysis tools, but more could easily be added either
 * by modules or as patches to Views itself.
 */
class Analyzer {

  /**
   * A module handler that invokes the 'views_analyze' hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs an Analyzer object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler that invokes the 'views_analyze' hook.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }


  /**
   * Analyzes a review and return the results.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view to analyze.
   *
   * @return array
   *   An array of analyze results organized into arrays keyed by 'ok',
   *   'warning' and 'error'.
   */
  public function getMessages(ViewExecutable $view) {
    $view->initDisplay();
    $messages = $this->moduleHandler->invokeAll('views_analyze', array($view));

    return $messages;
  }

  /**
   * Formats the analyze result into a message string.
   *
   * This is based upon the format of drupal_set_message which uses separate
   * boxes for "ok", "warning" and "error".
   */
  public function formatMessages(array $messages) {
    if (empty($messages)) {
      $messages = array(static::formatMessage(t('View analysis can find nothing to report.'), 'ok'));
    }

    $types = array('ok' => array(), 'warning' => array(), 'error' => array());
    foreach ($messages as $message) {
      if (empty($types[$message['type']])) {
        $types[$message['type']] = array();
      }
      $types[$message['type']][] = $message['message'];
    }

    $output = '';
    foreach ($types as $type => $messages) {
      $type .= ' messages';
      $message = '';
      if (count($messages) > 1) {
        $item_list = array(
          '#theme' => 'item_list',
          '#items' => $messages,
        );
        $message = drupal_render($item_list);
      }
      elseif ($messages) {
        $message = array_shift($messages);
      }

      if ($message) {
        $output .= "<div class=\"$type\">$message</div>";
      }
    }

    return $output;
  }

  /**
   * Formats an analysis message.
   *
   * This tool should be called by any module responding to the analyze hook
   * to properly format the message. It is usually used in the form:
   * @code
   *   $ret[] = Analyzer::formatMessage(t('This is the message'), 'ok');
   * @endcode
   *
   * The 'ok' status should be used to provide information about things
   * that are acceptable. In general analysis isn't interested in 'ok'
   * messages, but instead the 'warning', which is a category for items
   * that may be broken unless the user knows what he or she is doing,
   * and 'error' for items that are definitely broken are much more useful.
   *
   * @param string $message
   * @param string $type
   *   The type of message. This should be "ok", "warning" or "error". Other
   *   values can be used but how they are treated by the output routine
   *   is undefined.
   *
   * @return array
   *   A single formatted message, consisting of a key message and a key type.
   */
  static function formatMessage($message, $type = 'error') {
    return array('message' => $message, 'type' => $type);
  }

}
