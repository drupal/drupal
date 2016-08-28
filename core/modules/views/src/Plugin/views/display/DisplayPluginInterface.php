<?php

namespace Drupal\views\Plugin\views\display;

use Drupal\views\ViewExecutable;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * @defgroup views_display_plugins Views display plugins
 * @{
 * Plugins to handle the overall display of views.
 *
 * Display plugins are responsible for controlling where a view is rendered;
 * that is, how it is exposed to other parts of Drupal. 'Page' and 'block' are
 * the most commonly used display plugins. Each view also has a 'master' (or
 * 'default') display that includes information shared between all its
 * displays (see \Drupal\views\Plugin\views\display\DefaultDisplay).
 *
 * Display plugins extend \Drupal\views\Plugin\views\display\DisplayPluginBase.
 * They must be annotated with \Drupal\views\Annotation\ViewsDisplay
 * annotation, and they must be in namespace directory Plugin\views\display.
 *
 * @ingroup views_plugins
 *
 * @see plugin_api
 * @see views_display_extender_plugins
 */

/**
 * Provides an interface for Views display plugins.
 */
interface DisplayPluginInterface {

  /**
   * Initializes the display plugin.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The views executable.
   * @param array $display
   *   The display that will be populated and attached to the view.
   * @param array $options
   *   (optional) The options for the display plugin. Defaults to NULL.
   */
  public function initDisplay(ViewExecutable $view, array &$display, array &$options = NULL);

  /**
   * Destroys the display's components and the display itself.
   */
  public function destroy();

  /**
   * Determines if this display is the 'default' display.
   *
   * 'Default' display contains fallback settings.
   */
  public function isDefaultDisplay();

  /**
   * Determines if this display uses exposed filters.
   */
  public function usesExposed();

  /**
   * Determines if this display should display the exposed filters widgets.
   *
   * Regardless of what this function
   * returns, exposed filters will not be used nor
   * displayed unless usesExposed() returns TRUE.
   */
  public function displaysExposed();

  /**
   * Whether the display allows the use of AJAX or not.
   *
   * @return bool
   */
  public function usesAJAX();

  /**
   * Whether the display is actually using AJAX or not.
   *
   * @return bool
   */
  public function ajaxEnabled();

  /**
   * Whether the display is enabled.
   *
   * @return bool
   *   Returns TRUE if the display is marked as enabled, else FALSE.
   */
  public function isEnabled();

  /**
   * Whether the display allows the use of a pager or not.
   *
   * @return bool
   */
  public function usesPager();

  /**
   * Whether the display is using a pager or not.
   *
   * @return bool
   */
  public function isPagerEnabled();

  /**
   * Whether the display allows the use of a 'more' link or not.
   *
   * @return bool
   */
  public function usesMore();

  /**
   * Whether the display is using the 'more' link or not.
   *
   * @return bool
   */
  public function isMoreEnabled();

  /**
   * Does the display have groupby enabled?
   */
  public function useGroupBy();

  /**
   * Should the enabled display more link be shown when no more items?
   */
  public function useMoreAlways();

  /**
   * Does the display have custom link text?
   */
  public function useMoreText();

  /**
   * Determines whether this display can use attachments.
   *
   * @return bool
   */
  public function acceptAttachments();

  /**
   * Returns whether the display can use attachments.
   *
   * @return bool
   */
  public function usesAttachments();

  /**
   * Returns whether the display can use areas.
   *
   * @return bool
   *   TRUE if the display can use areas, or FALSE otherwise.
   */
  public function usesAreas();

  /**
   * Allows displays to attach to other views.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The views executable.
   * @param string $display_id
   *   The display to attach to.
   * @param array $build
   *   The parent view render array.
   */
  public function attachTo(ViewExecutable $view, $display_id, array &$build);

  /**
   * Lists the 'defaultable' sections and what items each section contains.
   */
  public function defaultableSections($section = NULL);

  /**
   * Checks to see if the display has a 'path' field.
   *
   * This is a pure function and not just a setting on the definition
   * because some displays (such as a panel pane) may have a path based
   * upon configuration.
   *
   * By default, displays do not have a path.
   */
  public function hasPath();

  /**
   * Checks to see if the display has some need to link to another display.
   *
   * For the most part, displays without a path will use a link display.
   * However, sometimes displays that have a path might also need to link to
   * another display. This is true for feeds.
   */
  public function usesLinkDisplay();

  /**
   * Checks to see if the display can put the exposed form in a block.
   *
   * By default, displays that do not have a path cannot disconnect
   * the exposed form and put it in a block, because the form has no
   * place to go and Views really wants the forms to go to a specific
   * page.
   */
  public function usesExposedFormInBlock();

  /**
   * Find out all displays which are attached to this display.
   *
   * The method is just using the pure storage object to avoid loading of the
   * sub displays which would kill lazy loading.
   */
  public function getAttachedDisplays();

  /**
   * Returns the ID of the display to use when making links.
   */
  public function getLinkDisplay();

  /**
   * Returns the base path to use for this display.
   *
   * This can be overridden for displays that do strange things
   * with the path.
   */
  public function getPath();

  /**
   * Points to the display which can be linked by this display.
   *
   * If the display has route information, the display itself is returned.
   * Otherwise, the configured linked display is returned. For example, if a
   * block display links to a page display, the page display will be returned
   * in both cases.
   *
   * @return \Drupal\views\Plugin\views\display\DisplayRouterInterface|null
   */
  public function getRoutedDisplay();

  /**
   * Returns a URL to $this display or its configured linked display.
   *
   * @return \Drupal\Core\Url|null
   */
  public function getUrl();

  /**
   * Determines if an option is set to use the default or current display.
   *
   * @return
   *   TRUE for the default display.
   */
  public function isDefaulted($option);

  /**
   * Gets an option, from this display or the default display.
   */
  public function getOption($option);

  /**
   * Determines if the display's style uses fields.
   *
   * @return bool
   */
  public function usesFields();

  /**
   * Get the instance of a plugin, for example style or row.
   *
   * @param string $type
   *   The type of the plugin.
   *
   * @return \Drupal\views\Plugin\views\ViewsPluginInterface
   */
  public function getPlugin($type);

  /**
   * Get the handler object for a single handler.
   */
  public function getHandler($type, $id);

  /**
   * Get a full array of handlers for $type. This caches them.
   *
   * @return \Drupal\views\Plugin\views\ViewsHandlerInterface[]
   */
  public function getHandlers($type);

  /**
   * Retrieves a list of fields for the current display.
   *
   * This also takes into account any associated relationships, if they exist.
   *
   * @param bool $groupable_only
   *   (optional) TRUE to only return an array of field labels from handlers
   *   that support the useStringGroupBy method, defaults to FALSE.
   *
   * @return array
   *   An array of applicable field options, keyed by ID.
   */
  public function getFieldLabels($groupable_only = FALSE);

  /**
   * Sets an option, on this display or the default display.
   */
  public function setOption($option, $value);

  /**
   * Set an option and force it to be an override.
   */
  public function overrideOption($option, $value);

  /**
   * Returns a link to a section of a form.
   *
   * Because forms may be split up into sections, this provides an easy URL
   * to exactly the right section. Don't override this.
   */
  public function optionLink($text, $section, $class = '', $title = '');

  /**
   * Returns to tokens for arguments.
   *
   * This function is similar to views_handler_field::getRenderTokens()
   * but without fields tokens.
   */
  public function getArgumentsTokens();

  /**
   * Provides the default summary for options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options);

  /**
   * Provides the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * Validates the options form.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * Performs any necessary changes to the form values prior to storage.
   *
   * There is no need for this function to actually store the data.
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state);

  /**
   * If override/revert was clicked, perform the proper toggle.
   */
  public function optionsOverride($form, FormStateInterface $form_state);

  /**
   * Flip the override setting for the given section.
   *
   * @param string $section
   *   Which option should be marked as overridden, for example "filters".
   * @param bool $new_state
   *   Select the new state of the option:
   *   - TRUE: Revert new state option to default.
   *   - FALSE: Mark it as overridden.
   */
  public function setOverride($section, $new_state = NULL);

  /**
   * Injects anything into the query that the display handler needs.
   */
  public function query();

  /**
   * Does nothing (obsolete function).
   *
   * @todo This function no longer seems to be used.
   */
  public function renderFilters();

  /**
   * Checks to see if the display plugins support pager rendering.
   */
  public function renderPager();

  /**
   * Renders the 'more' link.
   */
  public function renderMoreLink();

  /**
   * Renders this display.
   */
  public function render();

  /**
   * #pre_render callback for view display rendering.
   *
   * @see self::render()
   *
   * @param array $element
   *   The element to #pre_render
   *
   * @return array
   *   The processed element.
   */
  public function elementPreRender(array $element);

  /**
   * Renders one of the available areas.
   *
   * @param string $area
   *   Identifier of the specific area to render.
   * @param bool $empty
   *   (optional) Indicator whether or not the view result is empty. Defaults to
   *   FALSE
   *
   * @return array
   *   A render array for the given area.
   */
  public function renderArea($area, $empty = FALSE);

  /**
   * Determines if the user has access to this display of the view.
   */
  public function access(AccountInterface $account = NULL);

  /**
   * Sets up any variables on the view prior to execution.
   *
   * These are separated from execute because they are extremely common
   * and unlikely to be overridden on an individual display.
   */
  public function preExecute();

  /**
   * Calculates the display's cache metadata by inspecting each handler/plugin.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cache metadata.
   */
  public function calculateCacheMetadata();

  /**
   * Gets the cache metadata.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   The cache metadata.
   */
  public function getCacheMetadata();

  /**
   * Executes the view and returns data in the format required.
   *
   * The base class cannot be executed.
   */
  public function execute();


  /**
   * Builds a basic render array which can be properly render cached.
   *
   * In order to be rendered cached, it includes cache keys as well as the data
   * required to load the view on cache misses.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   * @param array $args
   *   (optional) The view arguments.
   *
   * @return array
   *   The view render array.
   */
  public static function buildBasicRenderable($view_id, $display_id, array $args = []);

  /**
   * Builds a renderable array of the view.
   *
   * Note: This does not yet contain the executed view, but just the loaded view
   * executable.
   *
   * @param array $args
   *   (optional) Arguments of the view.
   * @param bool $cache
   *   (optional) Specify FALSE in order to opt out of render caching.
   *
   * @return array
   *   The render array of a view.
   */
  public function buildRenderable(array $args = [], $cache = TRUE);

  /**
   * Renders the display for the purposes of a live preview.
   *
   * Also might be used for some other AJAXy reason.
   */
  function preview();

  /**
   * Returns the display type that this display requires.
   *
   * This can be used for filtering views plugins. E.g. if a plugin category of
   * 'foo' is specified, only plugins with no 'types' declared or 'types'
   * containing 'foo'. If you have a type of bar, this plugin will not be used.
   * This is applicable for style, row, access, cache, and exposed_form plugins.
   *
   * @return string
   *   The required display type. Defaults to 'normal'.
   *
   * @see \Drupal\views\Views::fetchPluginNames()
   */
  public function getType();

  /**
   * Make sure the display and all associated handlers are valid.
   *
   * @return
   *   Empty array if the display is valid; an array of error strings if it is
   *   not.
   */
  public function validate();

  /**
   * Reacts on adding a display.
   *
   * @see \Drupal\views\Entity\View::newDisplay()
   */
  public function newDisplay();

  /**
   * Reacts on deleting a display.
   */
  public function remove();

  /**
   * Checks if the provided identifier is unique.
   *
   * @param string $id
   *   The id of the handler which is checked.
   * @param string $identifier
   *   The actual get identifier configured in the exposed settings.
   *
   * @return bool
   *   Returns whether the identifier is unique on all handlers.
   */
  public function isIdentifierUnique($id, $identifier);

  /**
   * Is the output of the view empty.
   *
   * If a view has no result and neither the empty, nor the footer nor the header
   * does show anything return FALSE.
   *
   * @return bool
   *   Returns TRUE if the output is empty, else FALSE.
   */
  public function outputIsEmpty();

  /**
   * Provides the block system with any exposed widget blocks for this display.
   */
  public function getSpecialBlocks();

  /**
   * Renders the exposed form as block.
   *
   * @return string|null
   *   The rendered exposed form as string or NULL otherwise.
   */
  public function viewExposedFormBlocks();

  /**
   * Provides help text for the arguments.
   *
   * @return array
   *   Returns an array which contains text for the argument fieldset:
   *   - filter value present: The title of the fieldset in the argument
   *     where you can configure what should be done with a given argument.
   *   - filter value not present: The title of the fieldset in the argument
   *     where you can configure what should be done if the argument does not
   *     exist.
   *   - description: A description about how arguments are passed
   *     to the display. For example blocks can't get arguments from url.
   */
  public function getArgumentText();

  /**
   * Provides help text for pagers.
   *
   * @return array
   *   Returns an array which contains text for the items_per_page form
   *   element:
   *   - items per page title: The title text for the items_per_page form
   *     element.
   *   - items per page description: The description text for the
   *     items_per_page form element.
   */
  public function getPagerText();

  /**
   * Merges default values for all plugin types.
   */
  public function mergeDefaults();

  /**
   * Gets the display extenders.
   *
   * @return \Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase[]
   */
  public function getExtenders();

}
