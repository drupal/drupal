<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\File\MimeTypeGuesserTest.
 */

namespace Drupal\Tests\Core\File;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\MimeType\MimeTypeGuesser;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser as SymfonyMimeTypeGuesser;

/**
 * @coversDefaultClass \Drupal\Core\File\MimeType\MimeTypeGuesser
 * @group DrupalKernel
 */
class MimeTypeGuesserTest extends UnitTestCase {

  /**
   * @covers ::registerWithSymfonyGuesser
   *
   * @see Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser
   */
  public function testSymfonyGuesserRegistration() {
    // Make the guessers property accessible on Symfony's MimeTypeGuesser.
    $symfony_guesser = SymfonyMimeTypeGuesser::getInstance();
    // Test that the Drupal mime type guess is not being used before the
    // override method is called. It is possible that the test environment does
    // not support the default guessers.
    $guessers = $this->readAttribute($symfony_guesser, 'guessers');
    if (count($guessers)) {
      $this->assertNotInstanceOf('Drupal\Core\File\MimeType\MimeTypeGuesser', $guessers[0]);
    }
    $container = new ContainerBuilder();
    $container->set('file.mime_type.guesser', new MimeTypeGuesser());
    MimeTypeGuesser::registerWithSymfonyGuesser($container);
    $guessers = $this->readAttribute($symfony_guesser, 'guessers');
    $this->assertInstanceOf('Drupal\Core\File\MimeType\MimeTypeGuesser', $guessers[0]);
    $count = count($guessers);

    $container = new ContainerBuilder();
    $container->set('file.mime_type.guesser', new MimeTypeGuesser());
    MimeTypeGuesser::registerWithSymfonyGuesser($container);
    $guessers = $this->readAttribute($symfony_guesser, 'guessers');
    $this->assertInstanceOf('Drupal\Core\File\MimeType\MimeTypeGuesser', $guessers[0]);
    $new_count = count($guessers);
    $this->assertEquals($count, $new_count, 'The count of mime type guessers remains the same after container re-init.');
  }

}
