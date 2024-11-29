<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\Icon\IconDefinitionInterface;

/**
 * Interface for icon pack manager.
 *
 * @internal
 *   This API is experimental.
 */
interface IconPackManagerInterface extends PluginManagerInterface {

  /**
   * Get a list of all the icons within definitions.
   *
   * @param array $allowed_icon_pack
   *   Limit the icons to some definition id.
   *
   * @return array
   *   Gets a list of icons index by id with `source` and `group`.
   */
  public function getIcons(array $allowed_icon_pack = []): array;

  /**
   * Get definition of a specific icon.
   *
   * @param string $icon_full_id
   *   The ID of the icon to retrieve, include pack id.
   *
   * @return \Drupal\Core\Theme\Icon\IconDefinitionInterface|null
   *   Icon definition.
   */
  public function getIcon(string $icon_full_id): ?IconDefinitionInterface;

  /**
   * Populates a key-value pair of available icon pack.
   *
   * @param bool $include_description
   *   Include Pack description if set, default to not include.
   *
   * @return array
   *   An array of translated icon pack labels, keyed by ID.
   */
  public function listIconPackOptions(bool $include_description = FALSE): array;

  /**
   * Retrieve extractor forms based on the provided icon set limit.
   *
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $default_settings
   *   The settings for the forms (optional).
   * @param array $allowed_icon_packs
   *   The list of icon packs (optional).
   * @param bool $wrap_details
   *   Wrap each form in details (optional).
   */
  public function getExtractorPluginForms(array &$form, FormStateInterface $form_state, array $default_settings = [], array $allowed_icon_packs = [], bool $wrap_details = FALSE): void;

  /**
   * Retrieve extractor default options.
   *
   * @param string $pack_id
   *   The icon pack to look for.
   *
   * @return array
   *   The extractor defaults options.
   */
  public function getExtractorFormDefaults(string $pack_id): array;

}
