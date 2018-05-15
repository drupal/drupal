<?php

namespace Drupal\Tests\rest\Functional\EntityResource\User;

use Drupal\Tests\user\Functional\Rest\UserResourceTestBase as UserResourceTestBaseReal;

/**
 * Class for backward compatibility. It is deprecated in Drupal 8.6.x.
 *
 * @see https://www.drupal.org/node/2971931
 */
abstract class UserResourceTestBase extends UserResourceTestBaseReal {
}
