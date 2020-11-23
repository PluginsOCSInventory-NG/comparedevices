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
    - allow multiple comparison
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
// get custom css style sheet (columns width set to 50%)
include '/usr/share/ocsinventory-reports/ocsreports/extensions/compare_devices/table_css/diff_table.css';
echo "</style>";


printEnTete($l->g(23150));
$form_name = 'compare_devices';
$tab_options = $protectedPost;
$table_name = $form_name;
$tab_options['form_name'] = $form_name;
$tab_options['table_name'] = $table_name;


echo open_form($form_name, '', 'enctype="multipart/form-data"', 'form-horizontal');
echo '<div class="col col-md-12" >';

// get selection of devices available for comparison
$link = $_SESSION['OCS']["readServer"];
$result = mysql2_query_secure("SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_'", $link);
$result = mysqli_fetch_all($result, MYSQLI_ASSOC);

// deviceid will be displayed by selectors
foreach ($result as $key => $value) {
    $display[$key] = $value['DEVICEID'];
}

echo "<div>
        <div>";
// selectors for main device and other devices
formGroup('select', 'main_device', $l->g(23153), '', '', (int)$protectedPost['main_device'], '', $result, $display, "required");
formGroup('select', 'other_device', $l->g(23154), '', '', (int)$protectedPost['other_device'], '', $result, $display, "required");

$time_delay_text = $l->g(23152);
$button_text = $l->g(23151);
echo "<br><p>$time_delay_text</p><br>";
// submit values
echo "<input type='submit' name='compare' id='compare' class='btn btn-success' value='$button_text'>";
echo "</div>
    </div>";
echo close_form();


// TRAITEMENT XML -------------------------------------------------
$xml = new DeviceXML();

// get main device and other device as xml structured STRINGS
$main_device = $xml->createXML($result[$protectedPost['main_device']]['ID']);
$other_device = $xml->createXML($result[$protectedPost['other_device']]['ID']);

// get differences between previously generated strings
$inlineResult = DiffHelper::calculate(
    $main_device,
    $other_device,
    // options : Unified, Combined, SideBySide, Inline
    'SideBySide',
    $diffOptions,
    // detail levels : word, line, char, none
    ['detailLevel' => 'none'] + $rendererOptions
);
echo "<div class='col col-md-12'><br><br>$inlineResult</div>";
// -----------------------------------------------------------------

if (AJAX) {
    ob_end_clean();
    tab_req($list_fields, $default_fields, $list_col_cant_del, $queryDetails, $tab_options);
}