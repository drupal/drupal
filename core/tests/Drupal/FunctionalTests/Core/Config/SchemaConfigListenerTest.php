<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Core\Config;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\Config\SchemaConfigListenerTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the functionality of ConfigSchemaChecker in BrowserTestBase tests.
 */
#[Group('config')]
class SchemaConfigListenerTest extends BrowserTestBase {

  use SchemaConfigListenerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
