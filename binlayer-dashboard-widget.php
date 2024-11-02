<?php
/*
Plugin Name: Binlayer Dashboard Widget
Plugin URI: http://www.michaelplas.de/wordpress/binlayer-de-wordpress-dashboard-plugin/
Description: Dieses Widget fügt dem Admin-Dashboard  Einnahmen- und Nachrichtenstatus von binlayer.de hinzu
Version: 1.0
Author: Michael Plas ( Teilcodes von Ulrich Wolf http://wolf-u.li )
Author URI: http://www.michaelplas.de
*/

/*
 * Wenn "binlayer-de-einnahmen" aktiviert ist erscheint ein Link im Dashboard 
 * 
 * Das Plugin gibt es auf demnächst
 * 		http://www.michaelplas.de
 */
add_action('wp_dashboard_setup', 'binlayerde_register_dashboard_widget');
function binlayerde_register_dashboard_widget ()
{
	add_option('binlayerde_widget_url', '');
	add_option('binlayerde_widget_twi', 'Ja');
	add_option('binlayerde_widget_userlevel', '10');
	if (function_exists('plugBinLayerStat_showStat')) {
		$allLink = 'index.php?page=binlayer-de-einnahmen.php';
	} else {
		$allLink = 'http://binlayer.de';
	}
	wp_register_sidebar_widget('dashboard_binlayerde', __('Binlayer.de', 'binlayerde'), 'dashboard_binlayerde', array('all_link' => $allLink , 'width' => 'half' , 'height' => 'single'));
	wp_register_widget_control('dashboard_binlayerde', 'binlayerde Config', 'dashboard_binlayerde_control');
}

/*
 * Fügt das Widget dem WP-Core hinzu
 */
add_filter('wp_dashboard_widgets', 'binlayerde_add_dashboard_widget');
function binlayerde_add_dashboard_widget ($widgets)
{
	global $wp_registered_widgets;
	if (! isset($wp_registered_widgets['dashboard_binlayerde'])) {
		return $widgets;
	}
	array_splice($widgets, sizeof($widgets) - 1, 0, 'dashboard_binlayerde');
	return $widgets;
}

/*
 * Ausgabe des Widgets
 */
function dashboard_binlayerde ($sidebar_args)
{
	global $wpdb;
	//extract($sidebar_args, EXTR_SKIP);
	echo $before_widget;
	echo $before_title;
	echo $widget_name;
	echo $after_title;
	global $user_ID;
	if ($user_ID) {
		if (current_user_can('level_' . get_option('binlayerde_widget_userlevel'))) {
			if ($xmlData = binlayerde_getdatafromxml()) {
				echo '<style>';
				echo '.binlayerde_table td {border-bottom: 1px solid black;padding: 5px;}';
				echo '.binlayerde_table th {background: #EEEEEE;padding: 10px;}';
				echo '</style>';
				echo '<table class="binlayerde_table">';
				echo '<tr><th>Guthaben:</th><td>' . $xmlData->stats[0]->total[0] . ' Euro</td>';
				$twion = get_option('binlayerde_widget_twi');
				if ($twion == "Ja" ) {
				echo '<td rowspan="2"><a href="http://twitter.com/MichaelP08"><img src="'.  WP_PLUGIN_URL .'/binlayer-dashboard-widget/Twitter-5A.png" alt="follow me on twitter" style="border: 0;" /></a></td>';
				}
	
				echo '</tr><tr><th>Heute:</th><td>' . $xmlData->stats[0]->today[0] . ' Euro </td></tr>';
				echo '<tr><th>Gestern:</th><td>' . $xmlData->stats[0]->yesterday[0] . ' Euro </td></tr>';
				
				echo '<tr><th>Unge. Nachrichten:</th><td>' . $xmlData->news[0]->unread[0] . '</td></tr>';
				echo '</table>';
			} else {
				echo 'Error while loading XML - API-Key eingegeben?';
			}
		} else {
			echo "You must be admin to see the Stats";
		}
	}
	echo $after_widget;
}

/*
 * Gets the Data from a given URL
 * 
 * @return The Data of the Result
 */

function binlayerde_getdatafromxml ()
{
	if (ini_get('allow_url_fopen') == 0) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, get_option('binlayerde_widget_url'));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		$str = curl_exec($curl);
		curl_close($curl);
		$returnData = @simplexml_load_string($str);
	} else {
		$returnData = @simplexml_load_file(get_option('binlayerde_widget_url'));
	}
	return $returnData;
}

/*
 * Dashboard Widget Edit-Modus
 */
function dashboard_binlayerde_control ()
{
	if (current_user_can('level_' . get_option('binlayerde_widget_userlevel'))) {
		if ('POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['binlayerde-widget-url'])) {
			echo stripslashes_deep($_POST['binlayerde-widget-url']);
			update_option('binlayerde_widget_url', "http://binlayer.de/api-" . stripslashes_deep($_POST['binlayerde-widget-url']));
			update_option('binlayerde_widget_twi', $_POST['binlayerde_widget_twi']);
			
		} else {
			echo '<p><label for="binlayerde-widget-url"><strong>Bitte fuege hier einen API-Key ein</strong><br />Dazu einfach den Key hinter "http://binlayerde/api-" rauskopieren "<br /><small>Beispiel: abcdefghijklmnopqrstuvwx12345678.xml</small><br />';
			echo '<input class="widefat" id="binlayerde" name="binlayerde-widget-url" type="text" value="' . substr(get_option('binlayerde_widget_url'), 23) . '"/>';
			echo '</label></p>';
			echo '<p><label for="binlayerde-widget-userlevel"><strong>Welche User sollen Zugriff auf das Widget haben?</strong><br /><small>Beispiel: Lvl 10 entspricht Administratoren</small><br />';
			$userLevel = get_option('binlayerde_widget_userlevel');
			echo '<select id="binlayerde" name="binlayerde_widget_userlevel">';
			for ($i = 0; $i <= 10; ++ $i)
				echo "<option value='$i' " . ($userLevel == $i ? "selected='selected'" : '') . ">$i</option>";
			echo '</select></label></p>';
			echo '<p><label for="binlayerde-widget-twi"><strong><a href="http://twitter.com/MichaelP08">Twitter</a> Symbol anzeigen?</strong><br />';
			echo '<select id="binlayerde" name="binlayerde_widget_twi">';
		 echo'  <option>Ja</option>';
      echo ' <option>Nein</option>';

			echo '</select></label></p>';
		}
	}
}
?>