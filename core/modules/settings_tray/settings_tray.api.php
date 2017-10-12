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
 * @section sec_api The API: the form in the Settings Tray
 *
 * By default, every block will show its built-in form in the Settings Tray.
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
 * main content, and help blocks). Such blocks can opt out of providing an
 * off-canvas form:
 * @code
 * forms = {
 *   "settings_tray" = FALSE,
 * },
 * @endcode
 *
 * Finally, blocks that do not specify an off-canvas form using the annotation
 * above will automatically have it set to their plugin class. For example, the
 * "Powered by Drupal" block plugin
 * (\Drupal\system\Plugin\Block\SystemPoweredByBlock) automatically gets
 * this added to its annotation:
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
