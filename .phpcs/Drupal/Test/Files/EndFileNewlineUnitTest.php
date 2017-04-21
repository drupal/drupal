<?php

class Drupal_Sniffs_Files_EndFileNewlineUnitTest extends CoderSniffUnitTest
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
        // All the good files have no error.
        if (strpos($testFile, 'good') !== false) {
            return array();
        } else {
            // All other files have one error on line one (they have all just one
            // code line in them).
            return array(
                    1 => 1,
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
