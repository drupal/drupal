<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity display repository.
 */
interface EntityDisplayRepositoryInterface {

  /**
   * The default display mode ID.
   *
   * @var string
   */
  const DEFAULT_DISPLAY_MODE = 'default';

  /**
   * Gets the entity view mode info for all entity types.
   *
   * @return array
   *   The view mode info for all entity types.
   */
  public function getAllViewModes();

  /**
   * Gets the entity view mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode info should be returned.
   *
   * @return array
   *   The view mode info for a specific entity type.
   */
  public function getViewModes($entity_type_id);

  /**
   * Gets the entity form mode info for all entity types.
   *
   * @return array
   *   The form mode info for all entity types.
   */
  public function getAllFormModes();

  /**
   * Gets the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode info should be returned.
   *
   * @return array
   *   The form mode info for a specific entity type.
   */
  public function getFormModes($entity_type_id);

  /**
   * Gets an array of view mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptions($entity_type_id);

  /**
   * Gets an array of form mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptions($entity_type_id);

  /**
   * Returns an array of enabled view mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle);

  /**
   * Returns an array of enabled form mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle);

  /**
   * Clears the gathered display mode info.
   *
   * @return $this
   */
  public function clearDisplayModeInfo();

  /**
   * Returns the entity view display associated with a bundle and view mode.
   *
   * Use this function when assigning suggested display options for a component
   * in a given view mode. Note that they will only be actually used at render
   * time if the view mode itself is configured to use dedicated display
   * settings for the bundle; if not, the 'default' display is used instead.
   *
   * The function reads the entity view display from the current configuration,
   * or returns a ready-to-use empty one if configuration entry exists yet for
   * this bundle and view mode. This streamlines manipulation of display objects
   * by always returning a consistent object that reflects the current state of
   * the configuration.
   *
   * Example usage:
   * - Set the 'body' field to be displayed and the 'field_image' field to be
   *   hidden on article nodes in the 'default' display.
   * @code
   * \Drupal::service('entity_display.repository')
   *   ->getViewDisplay('node', 'article', 'default')
   *   ->setComponent('body', [
   *     'type' => 'text_summary_or_trimmed',
   *     'settings' => ['trim_length' => '200'],
   *     'weight' => 1,
   *   ])
   *   ->removeComponent('field_image')
   *   ->save();
   * @endcode
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   (optional) The view mode. Defaults to self::DEFAULT_DISPLAY_MODE.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The entity view display associated with the view mode.
   */
  public function getViewDisplay($entity_type, $bundle, $view_mode = self::DEFAULT_DISPLAY_MODE);

  /**
   * Returns the entity form display associated with a bundle and form mode.
   *
   * The function reads the entity form display object from the current
   * configuration, or returns a ready-to-use empty one if no configuration
   * entry exists yet for this bundle and form mode. This streamlines
   * manipulation of entity form displays by always returning a consistent
   * object that reflects the current state of the configuration.
   *
   * Example usage:
   * - Set the 'body' field to be displayed with the
   *   'text_textarea_with_summary' widget and the 'field_image' field to be
   *   hidden on article nodes in the 'default' form mode.
   * @code
   * \Drupal::service('entity_display.repository')
   *   ->getFormDisplay('node', 'article', 'default')
   *   ->setComponent('body', [
   *     'type' => 'text_textarea_with_summary',
   *     'weight' => 1,
   *   ])
   *   ->setComponent('field_image', [
   *     'region' => 'hidden',
   *   ])
   *   ->save();
   * @endcode
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $form_mode
   *   (optional) The form mode. Defaults to self::DEFAULT_DISPLAY_MODE.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The entity form display associated with the given form mode.
   *
   * @see \Drupal\Core\Entity\EntityStorageInterface::create()
   * @see \Drupal\Core\Entity\EntityStorageInterface::load()
   */
  public function getFormDisplay($entity_type, $bundle, $form_mode = self::DEFAULT_DISPLAY_MODE);

}
