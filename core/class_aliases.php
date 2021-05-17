<?php

/**
 * @file
 * Contains class_aliases that will be added by the autoloader.
 *
 * @see core/composer.json
 */

// @todo https://www.drupal.org/project/drupal/issues/3197482 Remove this class
//   alias once Drupal is running Symfony 5.3 or higher.
class_alias('Drupal\Core\Http\KernelEvent', 'Symfony\Component\HttpKernel\Event\KernelEvent', TRUE);
