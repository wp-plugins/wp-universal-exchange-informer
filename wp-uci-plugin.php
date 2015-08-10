<?php

/*
Plugin Name: WP Universal Exchange Informer
Plugin URI: http://cyber-notes.net
Description: Exchange rate informer for Wordpress
Version: 0.4
Author: Santiaga
Author URI: http://cyber-notes.net
License: GPLv2 or later
*/

/*

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/* START DB INSTALL */
global $uci_db_version;
$uci_db_version="0.4";

register_activation_hook(__FILE__,'uci_db_create');

function uci_db_create(){
	global $wpdb;
	$table_name=$wpdb->prefix."uci_nbm_rates";
	$table_name2=$wpdb->prefix."uci_cbr_rates";
	$table_name3=$wpdb->prefix."uci_nbu_rates";
	$table_name4=$wpdb->prefix."uci_widgets";
	global $uci_db_version;
	$installed_ver=get_option("uci_db_version");
	$charset_collate=$wpdb->get_charset_collate();

	/* NBM Table */
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'")!=$table_name || $installed_ver!=$uci_db_version) {
		$sql="CREATE TABLE `".$table_name."` (
			`id` mediumint(3) NOT NULL AUTO_INCREMENT,
			`num` VARCHAR(3) DEFAULT '' NOT NULL,
			`char` VARCHAR(3) DEFAULT '' NOT NULL,
			`nominal` VARCHAR(6) DEFAULT '' NOT NULL,
			`value` VARCHAR(12) DEFAULT '' NOT NULL,
			`dif` VARCHAR(12) DEFAULT '' NOT NULL,
			UNIQUE KEY `id` (`id`)
		) ".$charset_collate.";";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		update_option("uci_db_version",$uci_db_version);
	}
	/* CBR Table */
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name2'")!=$table_name2 || $installed_ver!=$uci_db_version) {
		$sql2="CREATE TABLE `".$table_name2."` (
			`id` mediumint(3) NOT NULL AUTO_INCREMENT,
			`num` VARCHAR(3) DEFAULT '' NOT NULL,
			`char` VARCHAR(3) DEFAULT '' NOT NULL,
			`nominal` VARCHAR(6) DEFAULT '' NOT NULL,
			`value` VARCHAR(12) DEFAULT '' NOT NULL,
			`dif` VARCHAR(12) DEFAULT '' NOT NULL,
			UNIQUE KEY `id` (`id`)
		) ".$charset_collate.";";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql2);
		update_option("uci_db_version",$uci_db_version);
	}
	/* NBU Table */
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name3'")!=$table_name3 || $installed_ver!=$uci_db_version) {
		$sql3="CREATE TABLE `".$table_name3."` (
			`id` mediumint(3) NOT NULL AUTO_INCREMENT,
			`num` VARCHAR(3) DEFAULT '' NOT NULL,
			`char` VARCHAR(3) DEFAULT '' NOT NULL,
			`nominal` VARCHAR(6) DEFAULT '' NOT NULL,
			`value` VARCHAR(12) DEFAULT '' NOT NULL,
			`dif` VARCHAR(12) DEFAULT '' NOT NULL,
			UNIQUE KEY `id` (`id`)
		) ".$charset_collate.";";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql3);
		update_option("uci_db_version",$uci_db_version);
	}
	/* Widget Table */
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name4'")!=$table_name4 || $installed_ver!=$uci_db_version) {
		$sql4="CREATE TABLE `".$table_name4."` (
			`id` mediumint(3) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) DEFAULT '' NOT NULL,
			`bank` VARCHAR(3) DEFAULT '' NOT NULL,
			`currency` VARCHAR(255) DEFAULT '' NOT NULL,
			UNIQUE KEY `id` (`id`)
		) ".$charset_collate.";";
		require_once(ABSPATH.'wp-admin/includes/upgrade.php');
		dbDelta($sql4);
		update_option("uci_db_version",$uci_db_version);
	}
	add_option("uci_db_version",$uci_db_version);
}
/* END DB INSTALL */

/* Localization */
add_action('plugins_loaded','wpuci_text_domain',1);

function wpuci_text_domain() {
	load_plugin_textdomain('wp-universal-exchange-informer',false,dirname(plugin_basename(__FILE__)).'/lang/');
}

/* Admin Interface*/
if(is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
	add_action('admin_menu','wp_uci_options');

	function wp_uci_options() {
		/* Add new submenu */
		add_options_page('WP Universal Exchange Informer','WP Universal Exchange Informer','manage_options','wp_uci','uci_options');
		/* Add options */
		add_option('wp_uci_nbm_date','');
		add_option('wp_uci_cbr_date','');
		add_option('wp_uci_nbu_date','');
		add_option('wp_uci_plurl',plugins_url().'/'.basename(dirname(__FILE__)));
	}

	function uci_options() {
		global $wpdb;
		$banks=array(
			"cbr"=>"The Central Bank of the Russian Federation",
			"nbu"=>"National bank of Ukraine",
			"nbm"=>"National Bank of Moldova"
		);

		$currencies=array(
			"EUR"=>"978","USD"=>"840","RUB"=>"643","RON"=>"946","UAH"=>"980","MDL"=>"498","AED"=>"784","ALL"=>"008","AMD"=>"051","AUD"=>"036",
			"AZN"=>"944","BGN"=>"975","BYR"=>"974","CAD"=>"124","CHF"=>"756","CNY"=>"156","CZK"=>"203","DKK"=>"208","GBP"=>"826",
			"GEL"=>"981","HKD"=>"344","HRK"=>"191","HUF"=>"348","ILS"=>"376","INR"=>"356","ISK"=>"352","JPY"=>"392","KGS"=>"417",
			"KRW"=>"410","KWD"=>"414","KZT"=>"398","LTL"=>"440","LVL"=>"428","MKD"=>"807","MYR"=>"458","NOK"=>"578","NZD"=>"554",
			"PLN"=>"985","RSD"=>"941","SEK"=>"752","TJS"=>"972","TMT"=>"934","TRY"=>"949","UZS"=>"860","XDR"=>"960"
		);

		/* Start Options Form */
		echo "
			<span id=\"info_message_plug\"></span>
			<h3>".__('WP Universal Exchange Informer Settings','wp-universal-exchange-informer').":</h3>\n
		";
		/* Modal window */
		add_thickbox();
		echo "
			<div id=\"uci_add_box\" style=\"display:none;\">\n
			<h3>".__('New Informer Parameters','wp-universal-exchange-informer').":</h3>\n
			<span id=\"info_message\"></span>
		";
		/* Set title */
		echo "<h4>".__('Informer title','wp-universal-exchange-informer').":</h4><input type=\"text\" size=\"30\" id=\"uci_informer_title\" name=\"uci_informer_title\" value=\"".__('Set title for informer','wp-universal-exchange-informer')."...\" /><br>\n";
		/* Select Bank */
		echo "
			<h4>".__('Select bank to get exchange rates','wp-universal-exchange-informer').":</h4>\n
			<select id=\"wp_uci_bank\" name=\"wp_uci_bank\">\n
			<option>".__('Bank not selected','wp-universal-exchange-informer')."...</option>\n
		";
			foreach($banks as $k=>$v) {
				echo "<option value=\"".$k."\">".$v."</option>\n";
			}
		echo "
			</select>\n
			<br>\n
		";
		/* Select Currency */
		echo "
			<h4>".__('Select currency','wp-universal-exchange-informer').":</h4>\n
			<div id=\"currency_selectors\">\n
		";
		for($i=1;$i<=5;$i++) {
		echo "
			<label>Currency ".$i.": </label><select id=\"wp_uci_currency_".$i."\" name=\"wp_uci_currency_".$i."\">\n
			<option value=\"\">".__('Currency not selected','wp-universal-exchange-informer')."...</option>\n
		";
			foreach($currencies as $k=>$v) {
				echo "<option value=\"".$v."\">".$k."</option>\n";
			}
		echo "
			</select>\n
			<br>\n
		";
		}
		echo "
			</div>\n
			<a id=\"add_currency_selector_btn\" href=\"#\">+".__('Add currency selector','wp-universal-exchange-informer')."</a>\n
			<input type=\"hidden\" id=\"uci-plurl\" name=\"uci-plurl\" value=\"".get_option('wp_uci_plurl')."\">
			<p class=\"submit\"><input id=\"uci_informer_save\" class=\"button button-primary\" type=\"button\" value=\"".__('Save informer','wp-universal-exchange-informer')."\" name=\"save\"></p>\n
			</div>\n
			<p class=\"submit\">\n
			<a href=\"#TB_inline?width=600&height=550&inlineId=uci_add_box\" class=\"thickbox\"><input id=\"create\" class=\"button button-primary\" type=\"button\" value=\"".__('Create informer','wp-universal-exchange-informer')."\" name=\"create\"></a>\n
			</p>\n
		";
		/* Informers List */
		$table_name=$wpdb->prefix."uci_widgets";
		$informers=$wpdb->get_results("SELECT * FROM `".$table_name."`");
		echo "
			<table class=\"wp-list-table widefat fixed striped posts\">\n
			<thead>\n
			<tr>\n
				<th id=\"title\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Title','wp-universal-exchange-informer')."</th>\n
				<th id=\"bank\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Bank name','wp-universal-exchange-informer')."</th>\n
				<th id=\"currency\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Currency','wp-universal-exchange-informer')."</th>\n
				<th id=\"shortcode\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Shortcode','wp-universal-exchange-informer')."</th>\n
				<th id=\"buttons\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Options','wp-universal-exchange-informer')."</th>\n
			</tr>\n
			</thead>\n
			<tbody id=\"the-list\">\n
		";
		if($informers==NULL) {
			echo "
				<tr id=\"uci_noinf\" class=\"type-post format-standard\">\n
					<td colspan=\"4\" class=\"column-title\">".__('Informers not found','wp-universal-exchange-informer')."...</td>\n
				</tr>\n
			";
		} else {
			foreach($informers as $informer) {
				$bank_name=$banks[$informer->bank];
				$cur_flipped=array_flip($currencies);
				$cur_name=array();
				$cur_codes=explode(",",$informer->currency);
				foreach($cur_codes as $cur_code) {
					$cur_name[]=$cur_flipped[$cur_code];
				}
				$cur_name=implode(",",$cur_name);
				echo "
					<tr id=\"uci_informer_".$informer->id."\" class=\"type-post format-standard\">\n
						<td class=\"column-title\">".$informer->name."</td>\n
						<td class=\"column-title\">".$bank_name."</td>\n
						<td class=\"column-title\">".$cur_name."</td>\n
						<td class=\"column-title\">[excange-informer informer=\"".$informer->id."\"]</td>\n
						<td class=\"column-title\">\n
							<input id=\"uci_delete_".$informer->id."\" class=\"button button-primary\" type=\"button\" value=\"[X]\" name=\"delete\">\n
						</td>\n
					</tr>\n
				";
			}
		}
		echo "
			</tbody>\n
			<tfoot>\n
			<tr>\n
				<th id=\"title\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Title','wp-universal-exchange-informer')."</th>\n
				<th id=\"bank\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Bank name','wp-universal-exchange-informer')."</th>\n
				<th id=\"currency\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Currency','wp-universal-exchange-informer')."</th>\n
				<th id=\"shortcode\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Shortcode','wp-universal-exchange-informer')."</th>\n
				<th id=\"buttons\" class=\"manage-column\" style=\"\" scope=\"col\">".__('Options','wp-universal-exchange-informer')."</th>\n
			</tr>\n
			</tfoot>\n
			</table>\n
			";
	}
}

/* Register Widget */
function uci_register_widgets() {
	register_widget('uci_exchange_widget');
}
/* Include Widget */
add_action('widgets_init','uci_register_widgets');
require_once('widget/exchange-widget.php');

/* CSS */
if(!function_exists('uci_add_stylesheets')) {
	function uci_add_stylesheets() {
		wp_enqueue_style("uci_css",plugins_url()."/".basename(dirname(__FILE__))."/css/uci.css",array(), null);
	}
}
add_action('wp_enqueue_scripts','uci_add_stylesheets');
if(!function_exists('uci_add_admin_stylesheets')) {
	function uci_add_admin_stylesheets() {
		wp_enqueue_script("uci_js",plugins_url()."/".basename(dirname(__FILE__))."/js/admin.js",array('jquery'));
	}
}
add_action('admin_enqueue_scripts','uci_add_admin_stylesheets');

/* Get and update exchange rates */
function uci_get_rates() {
	global $wpdb;
	$current_date=date('d.m.Y',current_time('timestamp'));
	/* National Bank of Moldova */
	if(get_option('wp_uci_nbm_date')!=$current_date) {
		$today=date('d.m.Y',current_time('timestamp'));
		$yesterday=date("d.m.Y",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
		$get_xml_today=file_get_contents("http://www.bnm.md/ru/official_exchange_rates?get_xml=1&date=".$today,0);
		$get_xml_yesterday=file_get_contents("http://www.bnm.md/ru/official_exchange_rates?get_xml=1&date=".$yesterday,0);
		$xml_today=new SimplexmlElement($get_xml_today);
		$xml_yesterday=new SimplexmlElement($get_xml_yesterday);
		$xml_date=(string)$xml_today->attributes()->{'Date'};
		$table_name=$wpdb->prefix."uci_nbm_rates";
		if($xml_date==$today) {
			foreach($xml_today->Valute as $ind=>$item) {
				$rates_char=(string)$item->CharCode;
				$rates_num=(string)$item->NumCode;
				$rates_value=(string)$item->Value;
				$rates_nominal=(string)$item->Nominal;
				$val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
				if($val_exists==NULL) {
					$wpdb->insert($table_name,array(
						'num'=>$rates_num,
						'char'=>$rates_char,
						'nominal'=>$rates_nominal,
						'value'=>$rates_value
					));
				} else {
					$wpdb->update($table_name,array(
						'num'=>$rates_num,
						'char'=>$rates_char,
						'nominal'=>$rates_nominal,
						'value'=>$rates_value
					),
						array('num'=>$rates_num)
					);
				}
			}
			foreach($xml_yesterday->Valute as $item) {
				$today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
				$yesterday_value=(string)$item->Value;
				$difference=$today_value-$yesterday_value;
				$wpdb->update($table_name,array(
					'dif'=>round($difference,4)
				),
					array('num'=>(string)$item->NumCode)
				);
			}
			update_option("wp_uci_nbm_date",$current_date);
		}
	}
	/* Central Bank of Russia */
	$text_day=date('D');
	if(get_option('wp_uci_cbr_date')!=$current_date && $text_day!="Sun" && $text_day!="Mon") {
		$today=date('d.m.Y',current_time('timestamp'));
		$yesterday=date("d.m.Y",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
		$get_xml_today=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$today,0);
		$get_xml_yesterday=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$yesterday,0);
		$xml_today=new SimplexmlElement($get_xml_today);
		$xml_yesterday=new SimplexmlElement($get_xml_yesterday);
		$xml_date=(string)$xml_today->attributes()->{'Date'};
		$table_name=$wpdb->prefix."uci_cbr_rates";
		if($xml_date==$today) {
			foreach($xml_today->Valute as $ind=>$item) {
				$rates_char=(string)$item->CharCode;
				$rates_num=(string)$item->NumCode;
				$rates_value=(string)$item->Value;
				$rates_nominal=(string)$item->Nominal;
				$val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
				if($val_exists==NULL) {
					$wpdb->insert($table_name,array(
						'num'=>$rates_num,
						'char'=>$rates_char,
						'nominal'=>$rates_nominal,
						'value'=>str_replace(",",".",$rates_value)
					));
				} else {
					$wpdb->update($table_name,array(
						'num'=>$rates_num,
						'char'=>$rates_char,
						'nominal'=>$rates_nominal,
						'value'=>str_replace(",",".",$rates_value)
					),
						array('num'=>$rates_num)
					);
				}
			}
			foreach($xml_yesterday->Valute as $item) {
				$today_nominal=$wpdb->get_var("SELECT `nominal` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
				$today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
				if($today_nominal!=(string)$item->Nominal) {
					$yesterday_value=str_replace(",",".",(string)$item->Value)/(string)$item->Nominal*$today_nominal;
				} else {
					$yesterday_value=str_replace(",",".",(string)$item->Value);
				}
				$difference=$today_value-$yesterday_value;
				$wpdb->update($table_name,array(
					'dif'=>round($difference,4)
				),
					array('num'=>(string)$item->NumCode)
				);
			}
			update_option("wp_uci_cbr_date",$current_date);
		}
	}
	/* National Bank of Ukraine */
	if(get_option('wp_uci_nbu_date')!=$current_date) {
		$today=date('dmY',current_time('timestamp'));
		$today2=date('d.m.Y',current_time('timestamp'));
		$yesterday=date("d.m.Y",strtotime(date("d.m.Y",strtotime($today2))."-1 day"));
		$yesterday=str_replace(".","",$yesterday);
		$get_xml_today=file_get_contents("http://pf-soft.net/service/currency/?date=".$today,0);
		$get_xml_yesterday=file_get_contents("http://pf-soft.net/service/currency/?date=".$yesterday,0);
		$xml_today=new SimplexmlElement($get_xml_today);
		$xml_yesterday=new SimplexmlElement($get_xml_yesterday);
		$xml_date=str_replace("/",".",(string)$xml_today->attributes()->{'Date'});
		$table_name=$wpdb->prefix."uci_nbu_rates";
		if($xml_date==$today2) {
			foreach($xml_today->Valute as $ind=>$item) {
				$rates_char=(string)$item->CharCode;
				$rates_num=(string)$item->NumCode;
				$rates_value=(string)$item->Value;
				$rates_nominal=(string)$item->Nominal;
				$val_exists=$wpdb->get_var("SELECT `num` FROM `".$table_name."` WHERE `num`='".$rates_num."'");
				if($val_exists==NULL) {
					$wpdb->insert($table_name,array(
						'num'=>$rates_num,
						'char'=>$rates_char,
						'nominal'=>$rates_nominal,
						'value'=>str_replace(",",".",$rates_value)
					));
				} else {
					$wpdb->update($table_name,array(
						'num'=>$rates_num,
						'char'=>$rates_char,
						'nominal'=>$rates_nominal,
						'value'=>str_replace(",",".",$rates_value)
					),
						array('num'=>$rates_num)
					);
				}
			}
			foreach($xml_yesterday->Valute as $item) {
				$today_nominal=$wpdb->get_var("SELECT `nominal` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
				$today_value=$wpdb->get_var("SELECT `value` FROM `".$table_name."` WHERE `num`='".(string)$item->NumCode."'");
				if($today_nominal!=(string)$item->Nominal) {
					$yesterday_value=str_replace(",",".",(string)$item->Value)/(string)$item->Nominal*$today_nominal;
				} else {
					$yesterday_value=str_replace(",",".",(string)$item->Value);
				}
				$difference=$today_value-$yesterday_value;
				$wpdb->update($table_name,array(
					'dif'=>round($difference,4)
				),
					array('num'=>(string)$item->NumCode)
				);
			}
			update_option("wp_uci_nbu_date",$current_date);
		}
	}
}
uci_get_rates();

/* Generate informer */
function uci_generate_informer($id) {
	global $wpdb;
	$nominals=array(
		"978"=>"1","840"=>"1","643"=>"1","946"=>"1","980"=>"1","784"=>"1","008"=>"10","051"=>"10","036"=>"1","944"=>"1","975"=>"1","974"=>"100","124"=>"1","756"=>"1","156"=>"1",
		"203"=>"1","208"=>"1","826"=>"1","981"=>"1","344"=>"1","191"=>"1","348"=>"100","376"=>"1","356"=>"10","352"=>"10","392"=>"100","417"=>"10","410"=>"100","414"=>"1","398"=>"10",
		"440"=>"1","428"=>"1","807"=>"10","458"=>"1","578"=>"1","554"=>"1","985"=>"1","941"=>"100","752"=>"1","972"=>"1","934"=>"1","949"=>"1","860"=>"100","960"=>"1","498"=>"10",
	);
	$table_name=$wpdb->prefix."uci_widgets";
	$informer=$wpdb->get_row("SELECT * FROM `".$table_name."` WHERE `id`='".$id."'");
	if($informer!=NULL) {
		$curr_name="";
		if($informer->bank=="nbm") { $curr_name=__('Lei','wp-universal-exchange-informer'); }
		elseif($informer->bank=="nbu") { $curr_name=__('Grn','wp-universal-exchange-informer'); }
		elseif($informer->bank=="cbr") { $curr_name=__('Rub','wp-universal-exchange-informer'); }
		$date_str="wp_uci_".$informer->bank."_date";
		$table_rates="wp_uci_".$informer->bank."_rates";
		$date=get_option($date_str);
		$rate_codes=explode(",",$informer->currency);
		$informer_code="
			<table id=\"uci_table\">\n
			<tr><td id=\"uci_curr_title\" colspan=\"7\">".$date."</td></tr>\n
		";
		foreach($rate_codes as $rate_code) {
			$cur=$wpdb->get_row("SELECT * FROM `".$table_rates."` WHERE `num`='".$rate_code."'");
			if($nominals[$rate_code]!=$cur->nominal) {
				$rate=round($cur->value/$cur->nominal*$nominals[$rate_code],4);
				$dif=round($cur->dif/$cur->nominal*$nominals[$rate_code],4);
			} else {
				$rate=$cur->value;
				$dif=$cur->dif;
			}
			$informer_code.=
				"<tr id=\"uci_row\">\n
				<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/".$cur->char.".gif\" /></td>\n
				<td id=\"uci_curr_text\">".$nominals[$rate_code]."</td>\n
				<td id=\"uci_curr_text\">".$cur->char."</td>\n
				<td id=\"uci_curr_text\">".$rate."</td>\n
				<td id=\"uci_curr_text\">".$curr_name."</td>\n
			";
			if($dif>0) $informer_code.="<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/arrow_up.gif\" /></td>\n";
			elseif($dif<0) $informer_code.="<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/arrow_down.gif\" /></td>\n";
			else $informer_code.="<td id=\"uci_curr_text\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/point.gif\" /></td>\n";
			if($dif>0) $informer_code.= "<td id=\"uci_curr_text_green\">+".$dif."</td>\n";
			elseif($dif<0) $informer_code.="<td id=\"uci_curr_text_red\">".$dif."</td>\n";
			else $informer_code.="<td id=\"uci_curr_text\">0.0000</td>\n";
			echo "</tr>\n";
		}
		$informer_code.="</table>";
		return $informer_code;
	} else {
		return "<p>".__('Informer not selected!','wp-universal-exchange-informer')."</p>";
	}
}

/* Add shortcode */
function uci_informer_shortcode($atts) {
	global $wpdb;
	extract(shortcode_atts(array("informer"=>''),$atts));
	return uci_generate_informer($informer);
}
add_shortcode("excange-informer","uci_informer_shortcode");
/* Shortcodes in text widgets */
add_filter('widget_text','do_shortcode');

?>