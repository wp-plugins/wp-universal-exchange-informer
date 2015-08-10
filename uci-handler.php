<?php

include("../../../wp-config.php");
if(!current_user_can('level_2')) die("Access denied");

if($_POST['act']=="add" && $_POST['title']!="" && $_POST['bank']!="" && $_POST['currency']!="") {
	$table_name=$wpdb->prefix."uci_widgets";
	$wpdb->insert($table_name,array(
		'name'=>addslashes($_POST['title']),
		'bank'=>addslashes($_POST['bank']),
		'currency'=>addslashes($_POST['currency'])
	));
	$id=$wpdb->insert_id;
	echo $id;
} elseif($_POST['act']=="del" && $_POST['id']!="") {
	$table_name=$wpdb->prefix."uci_widgets";
	$wpdb->delete($table_name,array('id'=>addslashes($_POST['id'])));
}

?>