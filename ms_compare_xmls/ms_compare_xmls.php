<?php
if(AJAX){
    parse_str($protectedPost['ocs']['0'], $params);
    $protectedPost+=$params;
    ob_start();
    $ajax = true;
}
else{
    $ajax=false;
}

echo "<link rel='stylesheet' href='/style.css'>";

include_once('Difference_Class.php');
include_once('ArrayFromXML_Class.php');
include_once('Table_Class.php');

/* 
User chooses main device to compare against first then every other device(s) to 
compare with
First value of xmls array is considered main device
*/


// --------------------------------------------- TEST -------------------------------------------
// array of xmls files
$xmls = array("xmls_tests/test.xml", "xmls_tests/test2.xml", "xmls_tests/test3.xml", "xmls_tests/test4.xml");
// $xmls = array("xmls/lea-ThinkPad-E570-2020-09-24-11-21-01.xml", "xmls/OCS-SRV-STAGE-LEA-2020-10-01-08-37-40.xml");


// loop other xmls to get arrays as objects
$i = 0;
foreach ($xmls as $xml) {
    $arr = new ArrayFromXML($xml);
    $devices[$i]= $arr;
    $i++;
}


// first array is main device to compare with
$mainDevice =  array_shift($devices);
$mainDevice = $mainDevice->array;


// loop over array of other devices to get differences
foreach ($devices as $key => $value) {
	$diffsV1 = new Difference();
	$array = $value->array;
	$diffs[$key] = $diffsV1->getDifferencesV1($mainDevice, $array);
}
// var_dump($diffs);


// COMMENTS
echo "<h2> First table below displays characteristics of main device used for comparison </h2>";
echo "<p> Other tables only display differences between main device and other selected devices </p>";


// show main device array
$mainDeviceTable = new Tabletizer();
echo $mainDeviceTable->fromArray($mainDevice);


// create tables for each array of differences (?)
foreach ($diffs as $array) {
	$table = new Tabletizer();
	echo $table->fromArray($array);
}


echo "<br><br><span> Array of differences : </span><br>";
var_dump($diffs);

?>