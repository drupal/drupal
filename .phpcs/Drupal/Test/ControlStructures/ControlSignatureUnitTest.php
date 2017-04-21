<?php

/**
 * Class Drupal_Sniffs_ControlStructures_ControlSignatureUnitTest
 */
class Drupal_Sniffs_ControlStructures_ControlSignatureUnitTest extends CoderSniffUnitTest
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
        switch ($testFile) {
            case 'ControlSignatureUnitTest.js':
                return array(
                          1 => 1,
                          4 => 1,
                          6 => 3,
                       );
            case 'ControlSignatureUnitTest.inc':
                return array(
                          6 => 1,
                          8 => 1,
                       );
        }

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


}//end class
