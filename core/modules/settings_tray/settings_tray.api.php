<?php

/**
 * @file
 * Documentation for Settings Tray API.
 */

/**
 * @defgroup settings_tray Settings Tray API
 * @{
 * Settings Tray API
 *
 * @section sec_overview Overview and terminology
 *
 * The Settings Tray module allows blocks to be configured in a sidebar form
 * without leaving the page. For example:
 *
 * - For every block, one can configure whether to display the block title or
 *   not, and optionally override it (block configuration).
 * - For menu blocks, one can configure which menu levels to display (block
 *   configuration) but also the menu itself (menu configuration).
 * - For the site branding block, one can change which branding elements to
 *   display (block configuration), but also the site name and slogan (simple
 *   configuration).
 *
 * Block visibility conditions are not included the sidebar form.
 *
 * @section sec_api The API: the form in the Settings Tray
 *
 * By default, the Settings Tray shows any block's built-in form in the
 * off-canvas dialog.
 *
 * @see core/misc/dialog/off-canvas.es6.js
 *
 * However, many blocks would benefit from a tailored form which either:
 * - limits the form items displayed in the Settings Tray to only items that
 *   affect the content of the rendered block
 * - adds additional form items to edit configuration that is rendered by the
 *   block. See \Drupal\settings_tray\Form\SystemBrandingOffCanvasForm which
 *   adds site name and slogan configuration.
 *
 * These can be used to provide a better experience, so that the Settings Tray
 * only displays what the user will expect to change when editing the block.
 *
 * Each block plugin can specify which form to use in the Settings Tray dialog
 * in its plugin annotation:
 * @code
 * forms = {
 *   "settings_tray" = "\Drupal\some_module\Form\MyBlockOffCanvasForm",
 * },
 * @endcode
 *
 * In some cases, a block's content is not configurable (for example, the title,
 * main content, and help blocks). Such blocks can opt out of providing a
 * settings_tray form:
 * @code
 * forms = {
 *   "settings_tray" = FALSE,
 * },
 * @endcode
 *
 * Finally, blocks that do not specify a settings_tray form using the annotation
 * above will automatically have it set to their plugin class. For example, the
 * "Powered by Drupal" block plugin
 * (\Drupal\system\Plugin\Block\SystemPoweredByBlock) automatically gets this
 * added to its annotation:
 * @code
 * forms = {
 *   "settings_tray" = "\Drupal\system\Plugin\Block\SystemPoweredByBlock",
 * },
 * @endcode
 *
 * Therefore, the entire Settings Tray API is just this annotation: it controls
 * what the Settings Tray does for a given block.
 *
 * @see settings_tray_block_alter()
 * @see \Drupal\Tests\settings_tray\Functional\SettingsTrayBlockTest::testPossibleAnnotations()
 *
 * @}
 */
