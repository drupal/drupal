<?php

namespace Drupal\Tests\config_translation\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the user profile field instance migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileFieldInstanceTranslationTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'locale',
    'language',
    'field',
  ];

  /**
   * Tests migration of translated user profile fields.
   */
  public function testUserProfileFields() {
    $this->executeMigrations([
      'user_profile_field',
      'user_profile_field_instance',
      'd6_user_profile_field_instance_translation',
    ]);
    $language_manager = $this->container->get('language_manager');

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_really_really_love_mig');
    $this->assertSame("J'aime les migrations", $config_translation->get('label'));
    $this->assertSame("Si vous cochez cette case, vous aimez les migrations.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_color');
    $this->assertSame('fr - Favorite color', $config_translation->get('label'));
    $this->assertSame('Inscrivez votre couleur préférée', $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_biography');
    $this->assertSame('fr - Biography', $config_translation->get('label'));
    $this->assertSame('fr - Tell people a little bit about yourself', $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_sell_address');
    $this->assertSame('fr - Sell your email address?', $config_translation->get('label'));
    $this->assertSame("fr - If you check this box, we'll sell your address to spammers to help line the pockets of our shareholders. Thanks!", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_sold_to');
    $this->assertSame('fr - Sales Category', $config_translation->get('label'));
    $this->assertSame("fr - Select the sales categories to which this user's address was sold.", $config_translation->get('description'));
    $this->assertSame('fr - Pill spammers Fitness spammers Back\slash Forward/slash Dot.in.the.middle', $config_translation->get('options'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_bands');
    $this->assertSame('Mes groupes préférés', $config_translation->get('label'));
    $this->assertSame("fr - Enter your favorite bands. When you've saved your profile, you'll be able to find other people with the same favorites.", $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_birthdate');
    $this->assertSame('fr - Birthdate', $config_translation->get('label'));
    $this->assertSame('fr - Enter your birth date and we\'ll send you a coupon.', $config_translation->get('description'));

    $config_translation = $language_manager->getLanguageConfigOverride('fr', 'field.field.user.user.profile_blog');
    $this->assertSame('fr - Blog', $config_translation->get('label'));
    $this->assertSame('fr - Paste the full URL, including http://, of your personal blog.', $config_translation->get('description'));
  }

}
