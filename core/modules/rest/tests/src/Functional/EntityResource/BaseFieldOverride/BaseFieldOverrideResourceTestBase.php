<?php

namespace Drupal\Tests\rest\Functional\EntityResource\BaseFieldOverride;

use Drupal\FunctionalTests\Rest\BaseFieldOverrideResourceTestBase as BaseFieldOverrideResourceTestBaseReal;

/**
 * Class for backward compatibility. It is deprecated in Drupal 8.6.x.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class BaseFieldOverrideResourceTestBase extends BaseFieldOverrideResourceTestBaseReal {
}
