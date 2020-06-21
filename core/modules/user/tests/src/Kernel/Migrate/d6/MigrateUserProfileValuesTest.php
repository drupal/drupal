<?php

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\user\Entity\User;

/**
 * User profile values migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileValuesTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->executeMigrations([
      'language',
      'user_profile_field',
      'user_profile_field_instance',
      'user_profile_entity_display',
      'user_profile_entity_form_display',
    ]);
    $this->migrateUsers(FALSE);
    $this->executeMigration('d6_profile_values');
  }

  /**
   * Tests Drupal 6 profile values to Drupal 8 migration.
   */
  public function testUserProfileValues() {
    $user = User::load(2);
    $this->assertNotNull($user);
    $this->assertIdentical('red', $user->profile_color->value);
    // cSpell:disable
    $expected = <<<EOT
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam nulla sapien, congue nec risus ut, adipiscing aliquet felis. Maecenas quis justo vel nulla varius euismod. Quisque metus metus, cursus sit amet sem non, bibendum vehicula elit. Cras dui nisl, eleifend at iaculis vitae, lacinia ut felis. Nullam aliquam ligula volutpat nulla consectetur accumsan. Maecenas tincidunt molestie diam, a accumsan enim fringilla sit amet. Morbi a tincidunt tellus. Donec imperdiet scelerisque porta. Sed quis sem bibendum eros congue sodales. Vivamus vel fermentum est, at rutrum orci. Nunc consectetur purus ut dolor pulvinar, ut volutpat felis congue. Cras tincidunt odio sed neque sollicitudin, vehicula tempor metus scelerisque.
EOT;
    // cSpell:enable
    $this->assertIdentical($expected, $user->profile_biography->value);
    $this->assertIdentical('1', $user->profile_sell_address->value);
    $this->assertIdentical('Back\slash', $user->profile_sold_to->value);
    $this->assertIdentical('AC/DC', $user->profile_bands[0]->value);
    $this->assertIdentical('Eagles', $user->profile_bands[1]->value);
    $this->assertIdentical('Elton John', $user->profile_bands[2]->value);
    $this->assertIdentical('Lemonheads', $user->profile_bands[3]->value);
    $this->assertIdentical('Rolling Stones', $user->profile_bands[4]->value);
    $this->assertIdentical('Queen', $user->profile_bands[5]->value);
    $this->assertIdentical('The White Stripes', $user->profile_bands[6]->value);
    $this->assertIdentical('1974-06-02', $user->profile_birthdate->value);
    $this->assertIdentical('http://example.com/blog', $user->profile_blog->uri);
    $this->assertNull($user->profile_blog->title);
    $this->assertIdentical([], $user->profile_blog->options);
    $this->assertIdentical('http://example.com/blog', $user->profile_blog->uri);

    // Check that the source profile field names that are longer than 32
    // characters have been migrated.
    $this->assertNotNull($user->getFieldDefinition('profile_really_really_love_mig'));
    $this->assertNotNull($user->getFieldDefinition('profile_really_really_love_mig1'));
    $this->assertSame('1', $user->profile_really_really_love_mig->value);
    $this->assertNull($user->profile_really_really_love_mig1->value);

    $user = User::load(8);
    $this->assertIdentical('Forward/slash', $user->profile_sold_to->value);

    $user = User::load(15);
    $this->assertIdentical('Dot.in.the.middle', $user->profile_sold_to->value);
  }

}
