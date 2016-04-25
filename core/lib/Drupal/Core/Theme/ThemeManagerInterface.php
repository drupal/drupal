<?php

namespace Drupal\Core\Theme;

/**
 * Provides a high level access to the active theme and methods to use it.
 *
 * Beside the active theme it provides a wrapper around _theme as well as the
 * alter functionality for themes.
 */
interface ThemeManagerInterface {

  /**
   * Generates themed output.
   *
   * See the @link themeable Default theme implementations topic @endlink for
   * details.
   *
   * @param string $hook
   *   The name of the theme hook to call.
   * @param array $variables
   *   An associative array of theme variables.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   The rendered output, or a Markup object.
   */
  public function render($hook, array $variables);

  /**
   * Returns the active theme object.
   *
   * @return \Drupal\Core\Theme\ActiveTheme
   */
  public function getActiveTheme();

  /**
   * Determines whether there is an active theme.
   *
   * @return bool
   */
  public function hasActiveTheme();

  /**
   * Resets the current active theme.
   *
   * Note: This method should not be used in common cases, just in special cases
   * like tests.
   *
   * @return $this
   */
  public function resetActiveTheme();

  /**
   * Sets the current active theme manually.
   *
   * Note: This method should not be used in common cases, just in special cases
   * like tests.
   *
   * @param \Drupal\Core\Theme\ActiveTheme $active_theme
   *   The new active theme.
   * @return $this
   */
  public function setActiveTheme(ActiveTheme $active_theme);

  /**
   * Passes alterable variables to specific $theme_TYPE_alter() implementations.
   *
   * It also invokes alter hooks for all base themes.
   *
   * $theme specifies the theme name of the active theme and all its base
   * themes.
   *
   * This dispatch function hands off the passed-in variables to type-specific
   * $theme_TYPE_alter() implementations in the active theme. It ensures a
   * consistent interface for all altering operations.
   *
   * A maximum of 2 alterable arguments is supported. In case more arguments
   * need to be passed and alterable, modules provide additional variables
   * assigned by reference in the last $context argument:
   * @code
   *   $context = array(
   *     'alterable' => &$alterable,
   *     'unalterable' => $unalterable,
   *     'foo' => 'bar',
   *   );
   *   $this->alter('mymodule_data', $alterable1, $alterable2, $context);
   * @endcode
   *
   * Note that objects are always passed by reference in PHP5. If it is
   * absolutely required that no implementation alters a passed object in
   * $context, then an object needs to be cloned:
   * @code
   *   $context = array(
   *     'unalterable_object' => clone $object,
   *   );
   *   $this->alter('mymodule_data', $data, $context);
   * @endcode
   *
   * @param string|array $type
   *   A string describing the type of the alterable $data. 'form', 'links',
   *   'node_content', and so on are several examples. Alternatively can be an
   *   array, in which case $theme_TYPE_alter() is invoked for each value in the
   *   array. When Form API is using $this->alter() to
   *   execute both $theme_form_alter() and $theme_form_FORM_ID_alter()
   *   implementations, it passes array('form', 'form_' . $form_id) for $type.
   * @param mixed $data
   *   The variable that will be passed to $theme_TYPE_alter() implementations
   *   to be altered. The type of this variable depends on the value of the
   *   $type argument. For example, when altering a 'form', $data will be a
   *   structured array. When altering a 'profile', $data will be an object.
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference. If more
   *   context needs to be provided to implementations, then this should be an
   *   associative array as described above.
   * Execute the alter hook on the current theme.
   *
   * @see \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL);

  /**
   * Provides an alter hook for a specific theme.
   *
   * Similar to ::alter, it also invokes the alter hooks for the base themes.
   *
   * @param \Drupal\Core\Theme\ActiveTheme $theme
   *   A manually specified theme.
   * @param string|array $type
   *   A string describing the type of the alterable $data.
   * @param mixed $data
   *   The variable that will be passed to $theme_TYPE_alter() implementations
   * @param mixed $context1
   *   (optional) An additional variable that is passed by reference.
   * @param mixed $context2
   *   (optional) An additional variable that is passed by reference.
   *
   * @see ::alter
   */
  public function alterForTheme(ActiveTheme $theme, $type, &$data, &$context1 = NULL, &$context2 = NULL);

}
