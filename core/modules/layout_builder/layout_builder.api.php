<?php

/**
 * @file
 * Hooks provided by the Layout Builder module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows customization of the Layout Builder UI for per-entity overrides.
 *
 * The Layout Builder widget will be added with a weight of -10 after this hook
 * is invoked.
 *
 * @see hook_entity_form_display_alter()
 * @see \Drupal\layout_builder\Form\OverridesEntityForm::init()
 */
function hook_layout_builder_overrides_entity_form_display_alter(\Drupal\Core\Entity\Display\EntityFormDisplayInterface $display) {
  $display->setComponent('moderation_state', [
    'type' => 'moderation_state_default',
    'weight' => 2,
    'settings' => [],
  ]);
}

/**
 * @} End of "addtogroup hooks".
 */
