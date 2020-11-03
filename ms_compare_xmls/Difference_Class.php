<?php

/* 
Compare two devices arrays
Returns array of differences btw main array and other array
*/
class Difference {
    // public $differences = array();

    function getDifferencesV1($mainDevice, $device) {
        $differences = array();
        foreach($device as $key => $value) {
            // key from $device exists in $mainDevice
            if (isset($mainDevice[$key])) {
                // if value is array, reuse function on it 
                if (is_array($value) && (!empty($value))) {
                    $differences[$key] = $this->getDifferencesV1($mainDevice[$key], $value);
                // values do not match = its a diff
                } elseif ($value != $mainDevice[$key]) {
                    $differences[$key] = $value;
                }
            // key doesnt exists in $mainDevice array = its a diff
            } else {
                $differences[$key] = $value;
            }
        }
        return $differences;
    }
}

?>