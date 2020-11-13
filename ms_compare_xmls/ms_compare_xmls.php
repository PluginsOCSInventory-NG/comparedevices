<?php
/*
 * Copyright 2005-2016 OCSInventory-NG/OCSInventory-ocsreports contributors.
 * See the Contributors file for more details about them.
 *
 * This file is part of OCSInventory-NG/OCSInventory-ocsreports.
 *
 * OCSInventory-NG/OCSInventory-ocsreports is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 2 of the License,
 * or (at your option) any later version.
 *
 * OCSInventory-NG/OCSInventory-ocsreports is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OCSInventory-NG/OCSInventory-ocsreports. if not, write to the
 * Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */
if (AJAX) {
    parse_str($protectedPost['ocs']['0'], $params);
    $protectedPost += $params;

    ob_start();
}

include_once('Difference_Class.php');
include_once('ArrayFromXML_Class.php');
include_once('Table_Class.php');
require_once('create_XML.php'); 

include ('/usr/share/ocsinventory-reports/ocsreports/vendor/jfcherng/php-diff/example/demo_base.php');

use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Factory\RendererFactory;

echo "
	<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/prism/1.22.0/themes/prism-okaidia.min.css' />
			<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/prism/1.22.0/plugins/line-numbers/prism-line-numbers.min.css' />

			<style type='text/css'>
				html {
					font-size: 13px;
				}
				.token.coord {
					color: #6cf;
				}
				.token.diff.bold {
					color: #fb0;
					font-weight: normal;
				}";

echo DiffHelper::getStyleSheet();
echo "</style><br><br>";

/*
    /!\ WARNING /!\
    Make sure https://github.com/jfcherng/php-diff is installed on server, 
    otherwise differences won't display as the extension requires this 
    third party library

    TODO : 
    - improve selectors (what if 10+ devices)
    - table isnt displaying selected devices (?)

    THINK HARDER :
    - autocompleted inputs vs dropdown selectors ?
    in case 1 vs 15 > cant display 15 selectors but two autocompleted inputs could do

    - php-diff only displays diff 1vs1 
    - php-diff table are not (yet) collapsable
 */


 // printEnTete($l->g(9000));
// temporary header :
echo "<h2> Compare devices : </h2>";

$form_name = 'compare_devices';
$tab_options = $protectedPost;
$table_name = $form_name;
$tab_options['form_name'] = $form_name;
$tab_options['table_name'] = $table_name;


echo open_form($form_name, '', 'enctype="multipart/form-data"', 'form-horizontal');
echo '<div class="col col-md-10" >';

if (isset($protectedPost['SUP_PROF']) && $protectedPost['SUP_PROF'] != "") {
    // TODO : Remove device from table
    $result_remove = 0;
    unset($protectedPost['SUP_PROF']);
    if ($result_remove == true) {
        msg_success($l->g(572));
    } else {
        msg_error($l->g(573));
    }
}

// IF DEVICE IS ADDED
if (isset($protectedPost['add_device'])) {
    var_dump($protectedPost['add_device']);
    /*  if($result == true){
        msg_success($l->g(572));
    }else{
        msg_error($l->g(573));
    }
    unset($protectedPost['add_device']); */
}

// req to select from 
$link = $_SESSION['OCS']["readServer"];
$result = mysql2_query_secure("SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_'", $link);
$result = mysqli_fetch_all($result, MYSQLI_ASSOC);

// prepare array to display during selection
foreach ($result as $key => $value) {
    $display[$key] = $value['DEVICEID'];
}

echo "<div>
        <div>";

// select main device and other devices
formGroup('select', 'main_device', 'Main device to compare :', '', '', '', '', $result, $display, "required");
// echo "<input type='submit' name='add_main_device' id='add_main_device' class='btn btn-success' value='Add'><br><br>";

// TODO : hide other device if main device not selected yet + remove already selected devices from list
formGroup('select', 'other_device[]', 'Other devices to compare :', '', '', '', '', $result, $display, "required");
// echo "<input type='submit' name='add_other_device' id='add_other_device' class='btn btn-success' value='Add'>";


// use code below to display multiple selectors (bs)
/* if (isset($protectedPost['add_other_device'])) {
    formGroup('select', 'other_device', 'Other devices to compare :', '', '', '', '', $result, $display, "required");
} 
echo "<input type='submit' name='add_device' id='add_device' class='btn btn-success' value='Add device to comparison'><br><br>";
*/


echo "</div></div></br></br></br></br>";

// quick check 
// var_dump($result);
$m_device = $result[$protectedPost['main_device']]['ID'];
$o_device = $result[$protectedPost['other_device'][0]]['ID'];
echo "<br>comparing $m_device with $o_device";


// Display table of selected devices
$list_fields = array(
    'ID' => 'ID',
    'DEVICE ID' => 'DEVICEID',
);

$list_fields['SUP'] = 'ID';
$tab_options['LBL_POPUP']['SUP'] = 'TYPE_NAME';

$default_fields = $list_fields;
$list_col_cant_del = $list_fields;

$devices = array($m_device, $o_device);
$in = implode(",", $devices);
$queryDetails = "SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_' AND id IN ($in)";

ajaxtab_entete_fixe($list_fields, $default_fields, $tab_options, $list_col_cant_del);
echo "<input type='submit' name='compare' id='compare' class='btn btn-success' value='Compare devices'>";
echo "</div></div>";
echo close_form();


// TRAITEMENT XML -------------------------------------------------
$xml = new DeviceXML();

$oldString = $xml->createXML($m_device);
$newString = $xml->createXML($o_device);

// demo the no-inline-detail diff
$inlineResult = DiffHelper::calculate(
    $oldString,
    $newString,
    // options : Unified, Combined, SideBySide, Inline
    'SideBySide',
    $diffOptions,
    // detail levels : word, line, char, none
    ['detailLevel' => 'none'] + $rendererOptions
);

echo "<br><br>$inlineResult";

// old table display below

/* function getArrayFromXml($xml, $elem) {
    $xml_device = $xml->createXML($elem);
    print_r($xml_device, true);
    $xml_md = simplexml_load_string($xml_device);
    // encode Xml file into Json
    $json = json_encode($xml_md);
    // decode ...
    $array_md = json_decode($json, TRUE);
    return $array_md;
}

$array = getArrayFromXml($xml, $m_device);
// var_dump($array);
echo "<br><br>";
$o_array = getArrayFromXml($xml, $o_device);
// var_dump($o_device);

echo "<br><br> DIFFERENCES ";
$diffs = new Difference();
$diffsV1 = $diffs->getDifferencesV1($array, $o_array);
// var_dump($diffsV1);

// show main device array
$mainDeviceTable = new Tabletizer();
echo $mainDeviceTable->fromArray($array);


// create table
$table = new Tabletizer();
echo $table->fromArray($diffsV1); */

// -----------------------------------------------------------------

if (AJAX) {
    ob_end_clean();
    tab_req($list_fields, $default_fields, $list_col_cant_del, $queryDetails, $tab_options);
}