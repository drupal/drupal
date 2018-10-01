<?php

namespace Drupal\views\Tests\Plugin;

@trigger_error(__NAMESPACE__ . '\PluginKernelTestBase is deprecated for removal before Drupal 9.0.0. Use \Drupal\Tests\views\Kernel\ViewsKernelTestBase instead.', E_USER_DEPRECATED);

use Drupal\views\Tests\ViewKernelTestBase;

/**
 * Base test class for views plugin unit tests.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0.
 *   Use \Drupal\Tests\views\Kernel\ViewsKernelTestBase instead.
 */
abstract class PluginKernelTestBase extends ViewKernelTestBase {

}
