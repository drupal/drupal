<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Defines interface for workspace forms so they can be easily distinguished.
 *
 * @internal
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 *   \Drupal\Core\Form\WorkspaceSafeFormInterface or
 *   \Drupal\Core\Form\WorkspaceDynamicSafeFormInterface instead.
 *
 * @see https://www.drupal.org/node/3229111
 */
interface WorkspaceFormInterface extends FormInterface {}
