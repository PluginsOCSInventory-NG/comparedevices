<?php 

/*
Load xml using simplexml, encode it to json then decode it into array
 */

class ArrayFromXML {
    function __construct($xmlFile) {  
        $this->xmlFile = $xmlFile;
        // load xml file 
        $xml = simplexml_load_file($this->xmlFile);
        // encode Xml file into Json
        $json = json_encode($xml);
        // decode ...
        $this->array = json_decode($json, TRUE);
    }  
}

?>