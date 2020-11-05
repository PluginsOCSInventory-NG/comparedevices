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

// printEnTete($l->g(9000));
// temp header
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
$link=$_SESSION['OCS']["readServer"];
$result = mysql2_query_secure("SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_'", $link);
$result = mysqli_fetch_all($result, MYSQLI_ASSOC);
var_dump($result);

// prepare array to display during selection
foreach ($result as $key => $value) {
    $display[$key] = $value['DEVICEID'];
}


echo "<div>
        <div>";

// select main device and other devices
formGroup('select', 'main_device', 'Main device to compare :', '', '', '', '', $result, $display, "required");
echo "<input type='submit' name='add_main_device' id='add_main_device' class='btn btn-success' value='Add'><br><br>";
// TODO : hide other device if main device not selected yet + remove already selected devices from list
formGroup('select', 'other_device', 'Other devices to compare :', '', '', '', '', $result, $display, "required");
echo "<input type='submit' name='add_other_device' id='add_other_device' class='btn btn-success' value='Add'>";
echo "</div></div></br></br></br></br>";

// Display table of all type created 
$list_fields = array(
    'ID' => 'ID',
    'DEVICE ID' => 'DEVICEID',
);

$list_fields['SUP'] = 'ID';
$tab_options['LBL_POPUP']['SUP'] = 'TYPE_NAME';

$default_fields = $list_fields;
$list_col_cant_del = $list_fields;

// TODO : display only selected devices (main and others)
$queryDetails = "SELECT ID, DEVICEID FROM hardware WHERE deviceid <> '_SYSTEMGROUP_' AND deviceid <>'_DOWNLOADGROUP_'";

ajaxtab_entete_fixe($list_fields, $default_fields, $tab_options, $list_col_cant_del);
echo "</div></div>";
echo close_form();


if (AJAX) {
    ob_end_clean();
    tab_req($list_fields, $default_fields, $list_col_cant_del, $queryDetails, $tab_options);
}
