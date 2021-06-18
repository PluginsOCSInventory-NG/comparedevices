<?php
/*
Generate xml structured string of a given device characteristics
Returns a string
*/
class DeviceXML {
    function createXML($id) {
        $sql = "SELECT * FROM `hardware` where `ID`=%s";
        $arg = $id;
        $res = mysql2_query_secure($sql, $_SESSION['OCS']["readServer"], $arg);
        $item_hardware = null;

        if($res) {
            $item_hardware = mysqli_fetch_object($res);
        }
        
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
        $table_not_use = array('accountinfo', 'groups_cache', 'download_history', 'devices');
        $xml .= "<REQUEST>\n";
        $xml .= "\t<DEVICEID>" . $item_hardware->DEVICEID . "</DEVICEID>\n";
        $xml .= "\t<CONTENT>\n";
        foreach ($_SESSION['OCS']['SQL_TABLE_HARDWARE_ID'] as $tablename) {
            if (!in_array($tablename, $table_not_use)) {
                $sql = "SELECT * FROM `%s` WHERE `HARDWARE_ID`=%s";
                $arg = array($tablename, $id);

                $res = mysql2_query_secure($sql, $_SESSION['OCS']["readServer"], $arg);

                if($res) {
                    while ($item = mysqli_fetch_object($res)) {
                        $xml .= "\t\t<" . mb_strtoupper($tablename) . ">\n";
                        foreach ($_SESSION['OCS']['SQL_TABLE'][$tablename] as $field_name => $field_type) {
                            if ($field_name != 'HARDWARE_ID') {
                                if (replace_entity_xml($item->$field_name) != '') {
                                    $xml .= "\t\t\t<" . $field_name . ">";
                                    $xml .= replace_entity_xml($item->$field_name);
                                    $xml .= "</" . $field_name . ">\n";
                                } else {
                                    $xml .= "\t\t\t<" . $field_name . " />\n";
                                }
                            }
                        }
                        $xml .= "\t\t</" . mb_strtoupper($tablename) . ">\n";
                    }
                } 
            }
        }
        //HARDWARE INFO
        $xml .= "\t\t<HARDWARE>\n";
        foreach ($_SESSION['OCS']['SQL_TABLE']['hardware'] as $field_name => $field_type) {
            if ($field_name != 'ID' && $field_name != 'DEVICEID') {
                if (replace_entity_xml($item_hardware->$field_name) != '') {
                    $xml .= "\t\t\t<" . $field_name . ">";
                    $xml .= replace_entity_xml($item_hardware->$field_name);
                    $xml .= "</" . $field_name . ">\n";
                } else {
                    $xml .= "\t\t\t<" . $field_name . " />\n";
                }
            }
        }
        $xml .= "\t\t</HARDWARE>\n";

        //ACCOUNTINFO VALUES
        $sql = "SELECT * FROM `accountinfo` WHERE `HARDWARE_ID`=%s";
        $arg = $id;
        $res = mysql2_query_secure($sql, $_SESSION['OCS']["readServer"], $arg);
        $item_accountinfo = null;

        if($res) {
            $item_accountinfo = mysqli_fetch_object($res);
        }
        
        foreach ($_SESSION['OCS']['SQL_TABLE']['accountinfo'] as $field_name => $field_type) {
            if ($field_name != 'HARDWARE_ID') {
                $xml .= "\t\t<ACCOUNTINFO>\n";
                $xml .= "\t\t\t<KEYNAME>" . $field_name . "</KEYNAME>\n";
                if (replace_entity_xml($item_accountinfo->$field_name) != '') {
                    $xml .= "\t\t\t<KEYVALUE>" . replace_entity_xml($item_accountinfo->$field_name) . "</KEYVALUE>\n";
                } else {
                    $xml .= "\t\t\t<KEYVALUE />\n";
                }
                $xml .= "\t\t</ACCOUNTINFO>\n";
            }
        }

        $xml .= "\t</CONTENT>\n";
        $xml .= "\t<QUERY>INVENTORY</QUERY>\n";
        $xml .= "</REQUEST>\n";
        return $xml;
    }
}
?>