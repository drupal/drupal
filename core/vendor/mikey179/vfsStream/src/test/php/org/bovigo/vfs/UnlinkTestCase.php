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
 * Test for unlink() functionality.
 *
 * @group  unlink
 */
class UnlinkTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group  issue_51
     */
    public function canRemoveNonWritableFileFromWritableDirectory()
    {
        $structure = array('test_directory' => array('test.file' => ''));
        $root = vfsStream::setup('root', null, $structure);
        $root->getChild('test_directory')->chmod(0777);
        $root->getChild('test_directory')->getChild('test.file')->chmod(0444);
        $this->assertTrue(@unlink(vfsStream::url('root/test_directory/test.file')));
    }

    /**
     * @test
     * @group  issue_51
     */
    public function canNotRemoveWritableFileFromNonWritableDirectory()
    {
        $structure = array('test_directory' => array('test.file' => ''));
        $root = vfsStream::setup('root', null, $structure);
        $root->getChild('test_directory')->chmod(0444);
        $root->getChild('test_directory')->getChild('test.file')->chmod(0777);
        $this->assertFalse(@unlink(vfsStream::url('root/test_directory/test.file')));
    }
}
?>