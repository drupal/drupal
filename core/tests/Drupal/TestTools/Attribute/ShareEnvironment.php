<?php

declare(strict_types=1);

namespace Drupal\TestTools\Attribute;

/**
 * Shares the environment of a test class between its test methods.
 *
 * A test class normally builds its environment again for every test method. A
 * test class with this attribute builds its environment once. All of its test
 * methods then use that environment.
 *
 * Subclasses do not inherit the attribute. Every test class declares it, as it
 * already does for the PHPUnit attributes. Pass FALSE to state that a test
 * class needs an environment of its own.
 *
 * A test method of a class with this attribute does not start from a clean
 * environment. It starts from the environment that "::setUpEnvironment()"
 * built, plus the changes that earlier test methods made. A test method must
 * make no assumptions about the environment, except that it is suitable for
 * the test to run in. The author must write each test method to be independent
 * of the environment:
 * - Do not assert on absolute identifiers or row counts.
 * - Read identifiers from the entities under test.
 * - Assert on the changes that the test method causes.
 * A test method written this way does not depend on the test methods that ran
 * before it. It gives the same result when run alone and when run as part of
 * the whole class.
 *
 * Additionally:
 * - A test method must not change or drop the schema of a table of the
 *   environment. It may create a table, and must then remove it in
 *   "::resetEnvironment". A test method that leaves a table behind fails.
 *   Dropping a table does not undo the configuration and the key value data
 *   that described it, so the cleanup goes through the same API that created
 *   it. A test class that cannot remove a table, because Drupal creates it on
 *   its own, redeclares "KernelTestBase::INFRASTRUCTURE_TABLES" instead.
 * - "::setUpEnvironment()" must be deterministic. Random values in the
 *   environment may cause issues.
 * - Currently, the test methods share only database state. Every test method
 *   runs in its own process with its own virtual site directory, so a file
 *   written during "::setUpEnvironment()" does not exist when the next test
 *   method runs. A test class that depends on such files cannot share its
 *   environment.
 *
 * @see \Drupal\KernelTests\KernelTestBase::setUpEnvironment()
 * @see \Drupal\KernelTests\KernelTestBase::sharesEnvironment()
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ShareEnvironment {

  /**
   * Constructs a ShareEnvironment object.
   *
   * @param bool $enabled
   *   (optional) Whether the test class shares its environment between its test
   *   methods.
   */
  public function __construct(public readonly bool $enabled = TRUE) {}

  /**
   * Returns whether a class shares its environment between its test methods.
   *
   * The attribute is deliberately not inherited: every test class declares for
   * itself, as it already does for the PHPUnit attributes it has to repeat.
   *
   * @param string $class
   *   The name of the class to check.
   *
   * @return bool
   *   TRUE if the test methods of the class share one environment.
   */
  public static function isEnabledFor(string $class): bool {
    $attributes = new \ReflectionClass($class)->getAttributes(self::class);
    // A class that declares nothing does not share its environment.
    return $attributes === [] ? FALSE : $attributes[0]->newInstance()->enabled;
  }

}
