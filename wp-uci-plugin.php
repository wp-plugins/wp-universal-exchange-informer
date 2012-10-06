<?php

/*
Plugin Name: WP Universal Exchange Informer
Plugin URI: http://cyber-notes.net
Description: Exchange rate informer for Wordpress
Version: 0.1
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

/* Admin Interface*/

if(is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {
	add_action('admin_menu','wp_uci_options');

	function wp_uci_options() {
		/* Add new submenu */
		add_options_page('WP Universal Exchange Informer','WP Universal Exchange Informer','manage_options','wp_uci','uci_options');
		/* Add options */
		add_option('wp_uci_bank','');
		add_option('wp_uci_widget_title','Exchange Informer');
		add_option('wp_uci_currencies_upd_nbm','');
		add_option('wp_uci_currencies_upd_nbu','');
		add_option('wp_uci_currencies_upd_cbr','');
		add_option('wp_uci_currencies_max','5');
		add_option('wp_uci_plurl',plugins_url().'/'.basename(dirname(__FILE__)));
	}

	function uci_options() {
		$banks=array(
			"cbr"=>"The Central Bank of the Russian Federation",
			"nbu"=>"National bank of Ukraine",
			"nbm"=>"National Bank of Moldova",
		);

		$currencies=array(
			"EUR"=>"978","USD"=>"840","RUB"=>"643","RON"=>"946","UAH"=>"980","MDL"=>"498","AED"=>"784","ALL"=>"008","AMD"=>"051","AUD"=>"036",
			"AZN"=>"944","BGN"=>"975","BYR"=>"974","CAD"=>"124","CHF"=>"756","CNY"=>"156","CZK"=>"203","DKK"=>"208","GBP"=>"826",
			"GEL"=>"981","HKD"=>"344","HRK"=>"191","HUF"=>"348","ILS"=>"376","INR"=>"356","ISK"=>"352","JPY"=>"392","KGS"=>"417",
			"KRW"=>"410","KWD"=>"414","KZT"=>"398","LTL"=>"440","LVL"=>"428","MKD"=>"807","MYR"=>"458","NOK"=>"578","NZD"=>"554",
			"PLN"=>"985","RSD"=>"941","SEK"=>"752","TJS"=>"972","TMT"=>"934","TRY"=>"949","UZS"=>"860","XDR"=>"960",
		);

		/* Start Options Form */
		echo "
			<h3>WP Universal Exchange Informer Settings:</h3>\n
			<form method=\"post\" action=\"options.php\" id=\"options\">\n
			";
		wp_nonce_field('update-options');
		
		/* Select Bank */
		echo "
			<h4>Select bank to get exchange rates:</h4>\n
			<select name=\"wp_uci_bank\">\n
			<option>Bank not selected...</option>\n
			";
			foreach($banks as $k=>$v) {
				$selected=($k==get_option('wp_uci_bank'))?"selected=\"selected\" ":"";
				echo "<option ".$selected."value=\"".$k."\">".$v."</option>\n";
			}
		echo "
			</select>\n
			<br>\n
			";
		
		/* Set Widget Title */
		echo "<br><label>Widget title:</label><input type=\"text\" size=\"30\" name=\"wp_uci_widget_title\" value=\"".get_option('wp_uci_widget_title')."\" /><br>";
		
		/* Set max currencies */
		echo "<br><label>Number of currencies in widget:</label><input type=\"text\" size=\"3\" name=\"wp_uci_currencies_max\" value=\"".get_option('wp_uci_currencies_max')."\" /><br>";
		
		echo "<h4>Select currencies:</h4>\n";
		
		/* Select Currency */
		for($i=1;$i<=get_option('wp_uci_currencies_max');$i++) {
			echo "
				<label>Currency ".$i."</label>\n
				<select name=\"wp_uci_currency".$i."\">\n
				<option value=\"\">Not active</option>\n
				";
			foreach($currencies as $k=>$v) {
				$selected=($v==get_option('wp_uci_currency'.$i))?"selected=\"selected\" ":"";
				echo "<option ".$selected."value=\"".$v."\">".$k."</option>\n";
			}
			echo "</select><br>\n";
		}
		
		/* End Options Form */
		echo "
			<br>\n
			<input type=\"hidden\" name=\"wp_uci_currencies_upd_nbm\" value=\"\" />\n
			<input type=\"hidden\" name=\"wp_uci_currencies_upd_nbu\" value=\"\" />\n
			<input type=\"hidden\" name=\"wp_uci_currencies_upd_cbr\" value=\"\" />\n
			<input type=\"hidden\" name=\"action\" value=\"update\" />\n
			<input type=\"hidden\" name=\"page_options\" value=\"wp_uci_bank,wp_uci_currencies_max,wp_uci_widget_title,";
		for($i=1;$i<=get_option('wp_uci_currencies_max');$i++) echo "wp_uci_currency".$i.",";
		foreach($banks as $k=>$v) echo "wp_uci_currencies_upd_".$k.",";
		echo "\" />\n
			<input type=\"submit\" class=\"button-primary\" name=\"submit\" value=\"Save Changes\">\n
			</form>\n
			<br>\n
			";
	}
}

/* Get Currencies */
function wp_uci_get_currencies() {
	if(get_option('wp_uci_bank')!="") {
		if(get_option('wp_uci_bank')=="nbm") {
			$today=date('d.m.Y',current_time('timestamp'));
			$yesterday=date("d.m.Y",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
			if(strtotime($today)!=strtotime(get_option('wp_uci_currencies_upd_nbm')) || get_option('wp_uci_currencies_upd_nbm')=="") {
				$get_xml_today=file_get_contents("http://www.bnm.md/ru/official_exchange_rates?get_xml=1&date=".$today,0);
				$get_xml_yesterday=file_get_contents("http://www.bnm.md/ru/official_exchange_rates?get_xml=1&date=".$yesterday,0);
				$xml_today=new SimplexmlElement($get_xml_today);
				$xml_yesterday=new SimplexmlElement($get_xml_yesterday);
				foreach($xml_today->Valute as $item) $currencies_array_today[(string)$item->NumCode]=(string)$item->Value/(string)$item->Nominal;
				foreach($xml_yesterday->Valute as $item) $currencies_array_yesterday[(string)$item->NumCode]=(string)$item->Value/(string)$item->Nominal;
				for($i=1;$i<=get_option('wp_uci_currencies_max');$i++) {
					if(get_option('wp_uci_currency'.$i)!="") {
						update_option('wp_uci_currency_today'.$i,$currencies_array_today[get_option('wp_uci_currency'.$i)]);
						update_option('wp_uci_currency_yesterday'.$i,$currencies_array_yesterday[get_option('wp_uci_currency'.$i)]);
					}
				}
				update_option('wp_uci_currencies_upd_nbm',$today);
			}
		}
		if(get_option('wp_uci_bank')=="nbu") {
			$today=date('d.m.Y',current_time('timestamp'));
			$yesterday=date("d.m.Y",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
			$today1=date('dmY',current_time('timestamp'));
			$yesterday1=date("dmY",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
			if(strtotime($today)!=strtotime(get_option('wp_uci_currencies_upd_nbu')) || get_option('wp_uci_currencies_upd_nbu')=="") {
				$get_xml_today=file_get_contents("http://pfsoft.com.ua/service/currency/?date=".$today1,0);
				$get_xml_yesterday=file_get_contents("http://pfsoft.com.ua/service/currency/?date=".$yesterday1,0);
				$xml_today=new SimplexmlElement($get_xml_today);
				$xml_yesterday=new SimplexmlElement($get_xml_yesterday);
				foreach($xml_today->Valute as $item) $currencies_array_today[(string)$item->NumCode]=(string)$item->Value/(string)$item->Nominal;
				foreach($xml_yesterday->Valute as $item) $currencies_array_yesterday[(string)$item->NumCode]=(string)$item->Value/(string)$item->Nominal;
				for($i=1;$i<=get_option('wp_uci_currencies_max');$i++) {
					if(get_option('wp_uci_currency'.$i)!="") {
						update_option('wp_uci_currency_today'.$i,$currencies_array_today[get_option('wp_uci_currency'.$i)]);
						update_option('wp_uci_currency_yesterday'.$i,$currencies_array_yesterday[get_option('wp_uci_currency'.$i)]);
					}
				}
				update_option('wp_uci_currencies_upd_nbu',$today);
			}
		}
		if(get_option('wp_uci_bank')=="cbr") {
			$today=date('d.m.Y',current_time('timestamp'));
			$yesterday=date("d.m.Y",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
			$today1=date('d/m/Y',current_time('timestamp'));
			$yesterday1=date("d/m/Y",strtotime(date("d.m.Y",strtotime($today))."-1 day"));
			if(strtotime($today)!=strtotime(get_option('wp_uci_currencies_upd_cbr')) || get_option('wp_uci_currencies_upd_cbr')=="") {
				$get_xml_today=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$today1,0);
				$get_xml_yesterday=file_get_contents("http://www.cbr.ru/scripts/XML_daily.asp?date_req=".$yesterday1,0);
				$xml_today=new SimplexmlElement($get_xml_today);
				$xml_yesterday=new SimplexmlElement($get_xml_yesterday);
				foreach($xml_today->Valute as $item) $currencies_array_today[(string)$item->NumCode]=str_replace(",",".",$item->Value)/str_replace(",",".",$item->Nominal);
				foreach($xml_yesterday->Valute as $item) $currencies_array_yesterday[(string)$item->NumCode]=str_replace(",",".",$item->Value)/str_replace(",",".",$item->Nominal);
				for($i=1;$i<=get_option('wp_uci_currencies_max');$i++) {
					if(get_option('wp_uci_currency'.$i)!="") {
						update_option('wp_uci_currency_today'.$i,$currencies_array_today[get_option('wp_uci_currency'.$i)]);
						update_option('wp_uci_currency_yesterday'.$i,$currencies_array_yesterday[get_option('wp_uci_currency'.$i)]);
					}
				}
				update_option('wp_uci_currencies_upd_cbr',$today);
			}
		}
	}
}
wp_uci_get_currencies();


/* Widget */

function wp_uci_widget_display($args,$instance) {
	extract($args);
	
	$currencies=array(
			"978"=>"EUR","840"=>"USD","643"=>"RUB","946"=>"RON","980"=>"UAH","498"=>"MDL","784"=>"AED","008"=>"ALL","051"=>"AMD","036"=>"AUD","944"=>"AZN","975"=>"BGN",
			"974"=>"BYR","124"=>"CAD","756"=>"CHF","156"=>"CNY","203"=>"CZK","208"=>"DKK","826"=>"GBP","981"=>"GEL","344"=>"HKD","191"=>"HRK","348"=>"HUF",
			"376"=>"ILS","356"=>"INR","352"=>"ISK","392"=>"JPY","417"=>"KGS","410"=>"KRW","414"=>"KWD","398"=>"KZT","440"=>"LTL","428"=>"LVL","807"=>"MKD",
			"458"=>"MYR","578"=>"NOK","554"=>"NZD","985"=>"PLN","941"=>"RSD","752"=>"SEK","972"=>"TJS","934"=>"TMT","949"=>"TRY","860"=>"UZS","960"=>"XDR",
	);
	
	$nominals=array(
		"978"=>"1","840"=>"1","643"=>"1","946"=>"1","980"=>"1","784"=>"1","008"=>"10","051"=>"10","036"=>"1","944"=>"1","975"=>"1","974"=>"100","124"=>"1","756"=>"1","156"=>"1",
		"203"=>"1","208"=>"1","826"=>"1","981"=>"1","344"=>"1","191"=>"1","348"=>"100","376"=>"1","356"=>"10","352"=>"10","392"=>"100","417"=>"10","410"=>"100","414"=>"1","398"=>"10",
		"440"=>"1","428"=>"1","807"=>"10","458"=>"1","578"=>"1","554"=>"1","985"=>"1","941"=>"100","752"=>"1","972"=>"1","934"=>"1","949"=>"1","860"=>"100","960"=>"1","498"=>"10",
	);
	
	$bank=get_option('wp_uci_bank');
	$curr_name="";
	if($bank=="nbm") { $curr_name="Lei"; $steps=4; }
	elseif($bank=="nbu") { $curr_name="Grn"; $steps=4; }
	elseif($bank=="cbr") { $curr_name="Rub"; $steps=4; }
	
	echo $before_widget;
	echo $before_title.get_option('wp_uci_widget_title').$after_title."
		<table width=\"210px\" id=\"uci_table\">\n
		<tr><td id=\"uci_curr_title\" colspan=\"7\">".get_option('wp_uci_currencies_upd_'.$bank)."</td></tr>\n
		";
	for($i=1;$i<=get_option('wp_uci_currencies_max');$i++) {
		echo "<tr>\n
			<td id=\"uci_curr_text2\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/".$currencies[get_option('wp_uci_currency'.$i)].".gif\" /></td>\n
			<td id=\"uci_curr_text2\">".$nominals[get_option('wp_uci_currency'.$i)]."</td>\n
			<td id=\"uci_curr_text2\">".$currencies[get_option('wp_uci_currency'.$i)]."</td>\n
			<td id=\"uci_curr_text2\">".number_format(get_option('wp_uci_currency_today'.$i)*$nominals[get_option('wp_uci_currency'.$i)],$steps,'.',' ')."</td>\n
			<td id=\"uci_curr_text\">".$curr_name."</td>\n";
		$curr_diff=(get_option('wp_uci_currency_today'.$i)*$nominals[get_option('wp_uci_currency'.$i)])-(get_option('wp_uci_currency_yesterday'.$i)*$nominals[get_option('wp_uci_currency'.$i)]);
		if($curr_diff>0) echo "<td id=\"uci_curr_text2\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/arrow_up.gif\" /></td>\n";
		elseif($curr_diff<0) echo "<td id=\"uci_curr_text2\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/arrow_down.gif\" /></td>\n";
		else echo "<td id=\"uci_curr_text2\"><img src=\"".plugins_url()."/".basename(dirname(__FILE__))."/img/point.gif\" /></td>\n";
		if($curr_diff>0) echo "<td id=\"uci_curr_text_green\">+".round($curr_diff,4)."</td>\n";
		elseif($curr_diff<0) echo "<td id=\"uci_curr_text_red\">".round($curr_diff,4)."</td>\n";
		else echo "<td id=\"uci_curr_text2\">0.0000</td>\n";
		echo "</tr>\n";
	}
	echo "</table>".$after_widget;
}

wp_register_sidebar_widget('wp_uci_widget','WP Universal Exchange Informer','wp_uci_widget_display',array('description' => 'WP Universal Exchange Informer'));


/* CSS */
function uci_add_stylesheets() {
	if(!wp_style_is('uci_css','registered')) {
		wp_register_style("uci_css",plugins_url()."/".basename(dirname(__FILE__))."/css/uci.css");
	}
	if(did_action('wp_print_styles')) wp_print_styles('uci_css');
	else wp_enqueue_style("uci_css");
}
add_action('wp_head','uci_add_stylesheets');


?>