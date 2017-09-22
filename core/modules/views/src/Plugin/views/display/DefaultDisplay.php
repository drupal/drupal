<?php

namespace Drupal\views\Plugin\views\display;

/**
 * A plugin to handle defaults on a view.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "default",
 *   title = @Translation("Master"),
 *   help = @Translation("Default settings for this view."),
 *   theme = "views_view",
 *   no_ui = TRUE
 * )
 */
class DefaultDisplay extends DisplayPluginBase {

  /**
   * Whether the display allows attachments.
   *
   * @var bool
   */
  protected $usesAttachments = TRUE;

  /**
   * Determine if this display is the 'default' display which contains
   * fallback settings
   */
  public function isDefaultDisplay() {
    return TRUE;
  }

  /**
   * The default execute handler fully renders the view.
   *
   * For the simplest use:
   * @code
   *   $output = $view->executeDisplay('default', $args);
   * @endcode
   *
   * For more complex usages, a view can be partially built:
   * @code
   *   $view->setArguments($args);
   *   $view->build('default'); // Build the query
   *   $view->preExecute(); // Pre-execute the query.
   *   $view->execute(); // Run the query
   *   $output = $view->render(); // Render the view
   * @endcode
   *
   * If short circuited at any point, look in $view->build_info for
   * information about the query. After execute, look in $view->result
   * for the array of objects returned from db_query.
   *
   * You can also do:
   * @code
   *   $view->setArguments($args);
   *   $output = $view->render('default'); // Render the view
   * @endcode
   *
   * This illustrates that render is smart enough to call build and execute
   * if these items have not already been accomplished.
   *
   * Note that execute also must accomplish other tasks, such as setting page
   * titles, and generating exposed filter data if necessary.
   */
  public function execute() {
    return $this->view->render($this->display['id']);
  }

}
