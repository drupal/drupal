<?php

declare(strict_types=1);

namespace Drupal\Core\Form;

/**
 * Defines an interface for forms that are safe to be submitted in a workspace.
 *
 * A form is considered workspace-safe if its submission has no impact on the
 * Live site.
 *
 * @see \Drupal\Core\Form\WorkspaceDynamicSafeFormInterface
 */
interface WorkspaceSafeFormInterface {}
