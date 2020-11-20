<?php
/**
 * This file is part of vfsStream.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  org\bovigo\vfs
 */
namespace org\bovigo\vfs;
/**
 * Test for flock() implementation.
 *
 * @package     bovigo_vfs
 * @subpackage  test
 * @since       0.10.0
 * @see         https://github.com/mikey179/vfsStream/issues/6
 * @group       issue_6
 */
class vfsStreamWrapperFlockTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * root directory
     *
     * @var  vfsStreamContainer
     */
    protected $root;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->root = vfsStream::setup();
    }

    /**
     * @test
     */
    public function fileIsNotLockedByDefault()
    {
        $this->assertFalse(vfsStream::newFile('foo.txt')->isLocked());
    }

    /**
     * @test
     */
    public function streamIsNotLockedByDefault()
    {
        file_put_contents(vfsStream::url('root/foo.txt'), 'content');
        $this->assertFalse($this->root->getChild('foo.txt')->isLocked());
    }

    /**
     * @test
     */
    public function canAquireSharedLock()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $this->assertTrue(flock($fp, LOCK_SH));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp);

    }

    /**
     * @test
     */
    public function canAquireSharedLockWithNonBlockingFlockCall()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $this->assertTrue(flock($fp, LOCK_SH | LOCK_NB));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp);

    }

    /**
     * @test
     */
    public function canAquireEclusiveLock()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $this->assertTrue(flock($fp, LOCK_EX));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @test
     */
    public function canAquireEclusiveLockWithNonBlockingFlockCall()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $this->assertTrue(flock($fp, LOCK_EX | LOCK_NB));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @test
     */
    public function canRemoveLock()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_EX);
        $this->assertTrue(flock($fp, LOCK_UN));
        $this->assertFalse($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canRemoveLockWhenNotLocked()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $this->assertTrue(flock($fp, LOCK_UN));
        $this->assertFalse($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertFalse($file->hasSharedLock($fp));
        $this->assertFalse($file->hasExclusiveLock());
        $this->assertFalse($file->hasExclusiveLock($fp));
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canRemoveSharedLockWithoutRemovingSharedLockOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_SH);
        $file->lock($fp2, LOCK_SH);
        $this->assertTrue(flock($fp1, LOCK_UN));
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasSharedLock($fp1));
        $this->assertTrue($file->hasSharedLock($fp2));
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canNotRemoveSharedLockAcquiredOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_SH);
        $this->assertTrue(flock($fp2, LOCK_UN));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canNotRemoveExlusiveLockAcquiredOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_EX);
        $this->assertTrue(flock($fp2, LOCK_UN));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @test
     */
    public function canRemoveLockWithNonBlockingFlockCall()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_EX);
        $this->assertTrue(flock($fp, LOCK_UN | LOCK_NB));
        $this->assertFalse($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canNotAquireExclusiveLockIfAlreadyExclusivelyLockedOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_EX);
        $this->assertFalse(flock($fp2, LOCK_EX + LOCK_NB));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        $this->assertTrue($file->hasExclusiveLock($fp1));
        $this->assertFalse($file->hasExclusiveLock($fp2));
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canAquireExclusiveLockIfAlreadySelfExclusivelyLocked()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_EX);
        $this->assertTrue(flock($fp, LOCK_EX + LOCK_NB));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canNotAquireExclusiveLockIfAlreadySharedLockedOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_SH);
        $this->assertFalse(flock($fp2, LOCK_EX));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canAquireExclusiveLockIfAlreadySelfSharedLocked()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_SH);
        $this->assertTrue(flock($fp, LOCK_EX));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canNotAquireSharedLockIfAlreadyExclusivelyLockedOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_EX);
        $this->assertFalse(flock($fp2, LOCK_SH + LOCK_NB));
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canAquireSharedLockIfAlreadySelfExclusivelyLocked()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_EX);
        $this->assertTrue(flock($fp, LOCK_SH + LOCK_NB));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canAquireSharedLockIfAlreadySelfSharedLocked()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_SH);
        $this->assertTrue(flock($fp, LOCK_SH));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function canAquireSharedLockIfAlreadySharedLockedOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2   = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp1, LOCK_SH);
        $this->assertTrue(flock($fp2, LOCK_SH));
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertTrue($file->hasSharedLock($fp1));
        $this->assertTrue($file->hasSharedLock($fp2));
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp1);
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/31
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_31
     * @group  issue_40
     */
    public function removesExclusiveLockOnStreamClose()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_EX);
        fclose($fp);
        $this->assertFalse($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/31
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_31
     * @group  issue_40
     */
    public function removesSharedLockOnStreamClose()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp, LOCK_SH);
        fclose($fp);
        $this->assertFalse($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertFalse($file->hasExclusiveLock());
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function notRemovesExclusiveLockOnStreamCloseIfExclusiveLockAcquiredOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1 = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2 = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp2, LOCK_EX);
        fclose($fp1);
        $this->assertTrue($file->isLocked());
        $this->assertFalse($file->hasSharedLock());
        $this->assertTrue($file->hasExclusiveLock());
        $this->assertTrue($file->hasExclusiveLock($fp2));
        fclose($fp2);
    }

    /**
     * @see    https://github.com/mikey179/vfsStream/issues/40
     * @test
     * @group  issue_40
     */
    public function notRemovesSharedLockOnStreamCloseIfSharedLockAcquiredOnOtherFileHandler()
    {
        $file = vfsStream::newFile('foo.txt')->at($this->root);
        $fp1 = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $fp2 = fopen(vfsStream::url('root/foo.txt'), 'rb');
        $file->lock($fp2, LOCK_SH);
        fclose($fp1);
        $this->assertTrue($file->isLocked());
        $this->assertTrue($file->hasSharedLock());
        $this->assertTrue($file->hasSharedLock($fp2));
        $this->assertFalse($file->hasExclusiveLock());
        fclose($fp2);
    }
}
