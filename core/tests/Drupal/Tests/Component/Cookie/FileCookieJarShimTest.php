<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Cookie;

use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Cookie\SetCookie;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

// cspell:ignore phpggc

/**
 * Tests the hardened backport of \GuzzleHttp\Cookie\FileCookieJar.
 *
 * The shim keeps full FileCookieJar functionality but ports the Guzzle 8.0
 * hardening (an $autoSave flag that __wakeup() disables and __destruct()
 * checks) so an instance restored from attacker-controlled serialized data
 * cannot be used as a file-write gadget.
 *
 * @see core/includes/guzzle_file_cookie_jar_shim.php
 * @see https://www.drupal.org/project/drupal/issues/3524971
 * @see https://github.com/guzzle/guzzle/pull/3334
 */
#[Group('Cookie')]
class FileCookieJarShimTest extends TestCase {

  /**
   * The path used for cookie storage during a test.
   *
   * @var string
   */
  protected string $file;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->file = tempnam(sys_get_temp_dir(), 'drupal_file_cookie_jar_');
    // Start from a non-existent file so the constructor does not load.
    unlink($this->file);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (file_exists($this->file)) {
      unlink($this->file);
    }
    parent::tearDown();
  }

  /**
   * Confirms the hardened replacement (with __wakeup) is the class in use.
   */
  public function testHardenedClassIsLoaded(): void {
    $reflection = new \ReflectionClass(FileCookieJar::class);
    $this->assertTrue(
      $reflection->hasMethod('__wakeup'),
      'The hardened FileCookieJar must define __wakeup(); its presence indicates the core backport is loaded instead of the unhardened Guzzle 7 class.'
    );
  }

  /**
   * The jar must retain full functionality: auto-save on destruction.
   */
  public function testLegitimateInstanceAutoSavesOnDestruction(): void {
    $jar = new FileCookieJar($this->file, TRUE);
    $jar->setCookie(new SetCookie([
      'Name' => 'sid',
      'Value' => 'abc',
      'Domain' => 'example.com',
      'Expires' => time() + 3600,
    ]));
    // Destroying a normally-constructed instance should persist cookies.
    unset($jar);
    gc_collect_cycles();

    $this->assertFileExists($this->file, 'A normally-constructed FileCookieJar auto-saves on destruction.');

    $reloaded = new FileCookieJar($this->file, TRUE);
    $this->assertCount(1, $reloaded, 'Persisted cookies are reloaded by the constructor.');
  }

  /**
   * Saved files must JSON-escape tag characters (defense in depth).
   */
  public function testSaveEscapesTagCharacters(): void {
    $jar = new FileCookieJar($this->file, TRUE);
    $jar->setCookie(new SetCookie([
      'Name' => 'x',
      'Value' => '<?php phpinfo(); ?>',
      'Domain' => 'example.com',
      'Expires' => time() + 3600,
    ]));
    $jar->save($this->file);

    $contents = file_get_contents($this->file);
    $this->assertStringNotContainsString('<?php', $contents, 'A literal PHP open tag must never be written to the cookie file.');
    $this->assertStringContainsString('\u003C', $contents, 'Tag characters must be hex-escaped via JSON_HEX_TAG.');
  }

  /**
   * An unserialized jar must not auto-save (the gadget chain sink).
   *
   * This is the key security assertion: __wakeup() disables auto-save so that
   * destroying an unserialized FileCookieJar with an attacker-controlled
   * filename performs no file write.
   */
  public function testUnserializedInstanceDoesNotAutoSave(): void {
    // Payload uses similar technique to phpggc's --public-properties to avoid
    // null bytes.
    $payload = sprintf(
      'O:%d:"%s":2:{s:8:"filename";s:%d:"%s";s:19:"storeSessionCookies";b:1;}',
      strlen(FileCookieJar::class),
      FileCookieJar::class,
      strlen($this->file),
      $this->file,
    );

    $object = unserialize($payload);

    $this->assertInstanceOf(FileCookieJar::class, $object);

    $reflection = new \ReflectionObject($object);

    // Confirm the payload actually populated the private $filename, i.e. the
    // gadget is armed and would write to $this->file if not for __wakeup().
    $this->assertSame(
      $this->file,
      $reflection->getProperty('filename')->getValue($object),
      'The payload set the private $filename — the gadget is armed.'
    );
    $this->assertFalse(
      $reflection->getProperty('autoSave')->getValue($object),
      '__wakeup() must disable auto-save on unserialized instances.'
    );

    // Force destruction.
    unset($object);
    gc_collect_cycles();

    $this->assertFileDoesNotExist(
      $this->file,
      'Destroying an unserialized FileCookieJar must not write to the attacker-controlled path.'
    );
  }

}
