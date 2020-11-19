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


printEnTete($l->g(23150));
$form_name = 'compare_devices';
$tab_options = $protectedPost;
$table_name = $form_name;
$tab_options['form_name'] = $form_name;
$tab_options['table_name'] = $table_name;


echo open_form($form_name, '', 'enctype="multipart/form-data"', 'form-horizontal');
echo '<div class="col col-md-10" >';

// req to select from 
$link = $_SESSION['OCS']["readServer"];
$result = mysql2_query_secure("SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_'", $link);
$result = mysqli_fetch_all($result, MYSQLI_ASSOC);

// prepare array for selection
foreach ($result as $key => $value) {
    $display[$key] = $value['DEVICEID'];
}

echo "<div>
        <div>";
// select main device and other devices
formGroup('select', 'main_device', 'Main device to compare :', '', '', '', '', $result, $display, "required");
formGroup('select', 'other_device', 'Other device to compare :', '', '', '', '', $result, $display, "required");
// submit values
echo "<input type='submit' name='compare' id='compare' class='btn btn-success' value='Compare devices'>";
echo "</div>
    </div>";


// Display table of selected devices
$list_fields = array(
    'ID' => 'ID',
    'DEVICE ID' => 'DEVICEID',
);
$list_fields['SUP'] = 'ID';
$tab_options['LBL_POPUP']['SUP'] = 'TYPE_NAME';
$default_fields = $list_fields;
$list_col_cant_del = $list_fields;

$devices = array($result[$protectedPost['main_device']]['ID'], $result[$protectedPost['other_device']]['ID']);
$in = implode(",", $devices);
// query for table display
$queryDetails = "SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_' AND id IN (".$in.")";
ajaxtab_entete_fixe($list_fields, $default_fields, $tab_options, $list_col_cant_del);

echo "</div>
    </div>";
echo close_form();


// TRAITEMENT XML -------------------------------------------------
$xml = new DeviceXML();

// createXML acutally only creates xml structure in a string
$main_device = $xml->createXML($result[$protectedPost['main_device']]['ID']);
$other_device = $xml->createXML($result[$protectedPost['other_device']]['ID']);

// demo the no-inline-detail diff
$inlineResult = DiffHelper::calculate(
    $main_device,
    $other_device,
    // options : Unified, Combined, SideBySide, Inline
    'SideBySide',
    $diffOptions,
    // detail levels : word, line, char, none
    ['detailLevel' => 'none'] + $rendererOptions
);
echo "<div class='col col-md-10' style='overflow-y: auto; height:300px;'><br><br>$inlineResult</div>";
// -----------------------------------------------------------------

if (AJAX) {
    ob_end_clean();
    tab_req($list_fields, $default_fields, $list_col_cant_del, $queryDetails, $tab_options);
}