<?php

class Drupal_Sniffs_Classes_UnusedUseStatementUnitTest extends CoderSniffUnitTest
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
        return array(
                5 => 1,
                6 => 1,
                7 => 1,
                10 => 1,
                11 => 1,
                12 => 1,
                14 => 1,
                16 => 1,
                17 => 1,
               );

    }//end getWarningList()


}//end class
