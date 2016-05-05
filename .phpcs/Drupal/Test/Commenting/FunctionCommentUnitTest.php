<?php

class Drupal_Sniffs_Commenting_FunctionCommentUnitTest extends CoderSniffUnitTest
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
        return array(
                12 => 1,
                14 => 1,
                33 => 1,
                43 => 1,
                53 => 1,
                62 => 1,
                71 => 1,
                78 => 1,
                87 => 1,
                92 => 1,
                101 => 1,
                113 => 1,
                126 => 2,
                147 => 1,
                148 => 3,
                180 => 1,
                187 => 1,
               );

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
