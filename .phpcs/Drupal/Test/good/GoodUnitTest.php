<?php
/**
 * Unit test class for all good files that must not throw errors/warnings.
 */

/**
 * Unit test class for all good files that must not throw errors/warnings.
 */
class Drupal_GoodUnitTest extends CoderSniffUnitTest
{


    /**
     * Returns the lines where errors should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of errors that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getErrorList($testFile)
    {
        return array();

    }//end getErrorList()


    /**
     * Returns the lines where warnings should occur.
     *
     * The key of the array should represent the line number and the value
     * should represent the number of warnings that should occur on that line.
     *
     * @return array(int => int)
     */
    public function getWarningList($testFile)
    {
        return array();

    }//end getWarningList()

    /**
     * Returns a list of test files that should be checked.
     *
     * @return array The list of test files.
     */
    protected function getTestFiles() {
        $dir = dirname(__FILE__);
        $di  = new DirectoryIterator($dir);

        foreach ($di as $file) {
            $path = $file->getPathname();
            if ($path !== __FILE__ && $file->isFile()) {
                $testFiles[] = $path;
            }
        }

        // Get them in order.
        sort($testFiles);
        return $testFiles;
    }

    /**
     * Returns a list of sniff codes that should be checked in this test.
     *
     * @return array The list of sniff codes.
     */
    protected function getSniffCodes() {
        // We want to test all sniffs defined in the standard.
        return array();
    }


}//end class

?>
