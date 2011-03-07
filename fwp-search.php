<?php
/**
 * Plugin Name: Fast WordPress Search
 * Short Name: fwp_search
 * Description: Faster and Relevance WordPress Search result with low resource consuming
 * Author: Ivan Kristianto
 * Version: 0.6
 * Requires at least: 2.7
 * Tested up to: 3.1
 * Tags: search, better search, fast search, relevance search
 * Contributors: Ivan Kristianto
 * WordPress URI: http://wordpress.org/extend/plugins/Fast-WP-Search/
 * Author URI: http://www.ivankristianto.com/
 * Donate URI: http://www.ivankristianto.com/freebies/
 * Plugin URI: http://www.ivankristianto.com/internet/blogging/fast-and-relevant-wordpress-search/1750/
 *
 *
 * FWP-Search - Better WordPress Search
 * Copyright (C) 2010	IvanKristianto.com
 *
 * This program is free software - you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.	If not, see <http://www.gnu.org/licenses/>.
 */
 
 // exit if add_action or plugins_url functions do not exist
if (!function_exists('add_action') || !function_exists('plugins_url')) exit;

// function to replace wp_die if it doesn't exist
if (!function_exists('wp_die')) : function wp_die ($message = 'wp_die') { die($message); } endif;

// define some definitions if they already are not
!defined('WP_CONTENT_DIR') && define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
!defined('WP_PLUGIN_DIR') && define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
!defined('WP_CONTENT_URL') && define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
!defined('WP_PLUGIN_URL') && define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');


// don't load directly
!defined('ABSPATH') && exit;


/**
 * fwp_search
 * 
 * @package   
 * @author Ivan Kristianto
 * @version 2011
 * @access public
 */
class fwp_search{
	var $options = array();	// an array of options and values
	var $plugin = array();	// array to hold plugin information
	var $benchmark = 0;
	
	/**
	 * Defined blank for loading optimization
	 */
	function fwp_search() {}
	
	/**
	 * Loads options named by opts array into correspondingly named class vars
	 */
	function LoadOptions($opts=array('options', 'plugin')){
		foreach ($opts as $pn) $this->{$pn} = get_option("fwp_search_{$pn}");
	}
	
	/**
	 * Saves options from class vars passed in by opts array and the adsense key and api key
	 */
	function SaveOptions($opts=array('options','code','plugin'))	{
		foreach ($opts as $pn) update_option("fwp_search_{$pn}", $this->{$pn});
	}
	
	/**
	 * Gets and sets the default values for the plugin options, then saves them
	 */
	function default_options()	{
		
		// get all the plugin array data
		$this->plugin = $this->get_plugin_data();	
		
		// default options
		$this->options = array(
			'enabled' 			=> '1',	// WP Search is on by default
			'benchmark_enabled' => '1',	// Show benchmark at the end of search result
			'search_num' 		=> 10,	// number of search result posts to show
		);
		
		// Save all these variables to database
		$this->SaveOptions();
	}
	
	/**
	 */
	function options_page()	{
		if(!current_user_can('administrator')) wp_die('<strong>ERROR</strong>: Not an Admin!');
echo <<<JS
<script type="text/javascript">
jQuery(document).ready(function($) {
	$(".fade").fadeIn(1000).fadeTo(1000, 1).fadeOut(1000);
});
</script>

JS;
	?>
		<div class="wrap">
		<a href="http://www.ivankristianto.com/">
			<div id="wp-search-icon" style="background: url(<?php echo plugin_dir_url(__FILE__) ?>static/wpsearch-icon.png) no-repeat;" class="icon32"><br /></div>
		</a>
		<h2><?php echo $this->plugin['plugin-name']; ?></h2>
		<?php if(isset($_POST['_wpnonce'])) {
			echo '<div class="updated fade" id="message"><p>'.__('Configuration', 'fwp-search').' <strong>'.__('SAVED', 'fwp-search').'</strong></p></div>';
		} ?>
		<div class="postbox-container" style="width:70%;">
			<div class="metabox-holder">	
				<div class="meta-box-sortables">
					<form action="<?php echo admin_url($this->plugin['action']); ?>" method="post" id="form">
						<?php
							wp_nonce_field($this->plugin['nonce']);
							$rows = array();
							$pre_content = '<p>Fast WordPress Search is an extended feature from WordPress core Search feature. Fast WordPress search provide faster and more relevance search results. It will automatically replace WordPress core search result in search page.</p>';
							$content = '';
							
							$rows[] = array(
								'id' => 'enabled',
								'label' => 'Enable/Disable plugin',
								'desc' => 'Enable or disable this plugin',
								'content' =>  $this->checkbox('enabled'),
							);
							
							$rows[] = array(
								'id' => 'benchmark_enabled',
								'label' => 'Enable Benchmarking',
								'desc' => 'This option will enable benchmarking result at the end of search result',
								'content' =>  $this->checkbox('benchmark_enabled'),
							);
							
							$rows[] = array(
								'id' => 'search_num',
								'label' => 'Max Search Result',
								'desc' => 'Maximum Search result to be displayed',
								'content' => $this->textinput('search_num'),
							);
							
							$this->postbox('generalsettings','General Settings',$pre_content.$this->form_table($rows).$this->save_button());
						?>
					</form>
		
					<form action="<?php echo admin_url($this->plugin['action']); ?>" method="post" onsubmit="javascript:return(confirm('Do you really want to reset all settings?'))">
					<?php wp_nonce_field('reset_nonce'); ?>
						<input type="hidden" name="reset" value="true"/>
						<div class="submit"><input type="submit" value="Reset All Settings &raquo;" /></div>
					</form>
				</div>
			</div>
		</div>
		
		<div class="postbox-container side" style="width:25%;">
			<div class="metabox-holder">	
				<div class="meta-box-sortables">
					<?php
						$this->plugin_like();
						$this->postbox('donate','<strong class="red">Donate $10, $20 or $50!</strong>','<p>This plugin has cost me countless hours of work, if you use it, please donate a token of your appreciation!</p><br/><form style="margin-left:50px;" action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="G463UW5KA8EZ6">
						<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
						</form>');
						$this->plugin_support();
						$this->news(); 
					?>
				</div>
				<br/><br/><br/>
			</div>
		</div>
		</div>
		
		
<?php
	}
	
	/**
	 * Intercept the WordPress core search query .
	 * Use the custom query instead.
	 * Run in wp_head hook
	 */
	function wp_intercept_search(){
		global $wp_query,$wpdb;
		if(is_search() && ($this->options['enabled'] == 1)){
			if(1 == $this->options['benchmark_enabled']){
				$this->benchmark = microtime(true);
			}
		
			$paged = $wp_query->query_vars['paged'];
			$paged = $wp_query->query_vars['posts_per_page'] * $paged;

			//var_dump($wp_query->posts);
			//wp_die('Stop');
			
			$wp_query->posts = $wpdb->get_results($wpdb->prepare($this->searched_query($this->options['search_num'], $paged)));
			$wp_query->request = null;
			$wp_query->post_count = count($wp_query->posts);
			
			//var_dump($wp_query->posts);
			//wp_die('Stop');
			
			if(1 == $this->options['benchmark_enabled']){
				$end = microtime(true);
				$this->benchmark = round($end - $this->benchmark,4);
			}
		}
		
		
	}
	
	function wps_loop_end(){
		if($this->options['benchmark_enabled'] == 1){
			echo '<div class="benchmark-info">Time executed '.$this->benchmark.' seconds</div>';
		}
	}
	
	/**
	 * Loads the options into the class vars.  
	 * Adds this plugins 'load' function to the 'load-plugin' hook.
	 * Adds this plugins 'admin_print_styles' function to the 'admin_print_styles-plugin' hook. 
	 */
	function init()
	{
		$this->LoadOptions();
		
		add_action('wp_head', array(&$this, 'wp_intercept_search'));
		add_action('loop_end', array(&$this, 'wps_loop_end'));
		add_action("load-{$this->plugin['hook']}", array(&$this, 'load'));
		add_action('admin_print_scripts', array(&$this,'config_page_scripts'));
		add_action('admin_print_styles', array(&$this,'config_page_styles'));	
		add_action("admin_footer-{$this->plugin['hook']}", create_function('', 'echo "<script src=\"'.plugins_url('/static/admin.js',__FILE__).'\" type=\"text/javascript\"></script>";'));
	}
	
	/**
	 * Enqueue javascript in FWP-Search Admin page.
	 * Enqueue required javascript.
	 * Run in admin_print_scripts hook
	 */
	function config_page_scripts() {
		if (isset($_GET['page']) && $_GET['page'] == $this->plugin['page']) {
			wp_enqueue_script('postbox');
			wp_enqueue_script('dashboard');
			wp_enqueue_script('thickbox');
			wp_enqueue_script('media-upload');
		}
	}
	
	/**
	 * Enqueue css styles in FWP-Search Admin page.
	 * Enqueue required css styles.
	 * Run in admin_print_styles hook
	 */
	function config_page_styles() {
		if (isset($_GET['page']) && $_GET['page'] == $this->plugin['page']) {
			
			wp_enqueue_style('dashboard');
			wp_enqueue_style('thickbox');
			wp_enqueue_style('global');
			wp_enqueue_style('wp-admin');
			wp_enqueue_style($this->plugin['pagenice'], plugins_url('/static/fwp-search.css',__FILE__));
		}
	}
	
	/**
	 * Run in every FWP-Search admin page load.
	 * Handle Post Request and update the wp_option.
	 * Run in load_ hook
	 */
	function load()
	{
		// parse and handle post requests to plugin
		if('POST' == $_SERVER['REQUEST_METHOD']) $this->handle_post();
  	}
	
	/**
	 * this plugin has to protect the code as it is displayed live on error pages, a prime target for malicious crackers and spammers
	 * and update the wp_options value
	 * @return
	 */
	function handle_post()
	{
		// if current user does not have administrator rights, then DIE
		if(!current_user_can('administrator')) wp_die('<strong>ERROR</strong>: Not an Admin!');
		
		// verify nonce, if not verified, then DIE
		if(isset($_POST["_{$this->plugin['nonce']}"])) wp_verify_nonce($_POST["_{$this->plugin['nonce']}"], $this->plugin['nonce']) || wp_die('<strong>ERROR</strong>: Incorrect Form Submission, please try again.');
		elseif(isset($_POST["reset"])) wp_verify_nonce($_POST["reset"], 'reset_nonce') || wp_die('<strong>ERROR</strong>: Incorrect Form Submission, please try again.');
		
		// resets options to default values
		if(isset($_POST["reset"])) return $this->default_options();
		
		// load up the current options from the database
		$this->LoadOptions();
		// process absolute integer options
		foreach (array('search_num', 'search_length') as $k) 
			$this->options[$k] = ((isset($_POST["{$k}"])) ? absint($_POST["{$k}"]) : absint($this->options[$k]));
		
		
		//Process Checkbox
		foreach (array('benchmark_enabled', 'thumbnail_enabled', 'enabled', 'auto_enabled') as $k)$this->options[$k] = ((!isset($_POST["{$k}"])) ? '0' : '1');

		// Save code and options arrays to database
		$this->SaveOptions();
	}
	
	/**
	 * Build custom search query.
	 * Search query using FULL-TEXT function in MYSQL which is only available for MyISAM engine.
	 */
	function searched_query($limit = 15, $start=0){
		global $wpdb;
		$terms = $rr = $out = '';
		$terms = $this->get_keywords(' ');
		if (strlen($terms) < 3) return;
		$query_ext = '';
		
        if(substr_count($terms, ' ') < 1) $query_ext = 'WITH QUERY EXPANSION';
		$sql = "SELECT ID, post_author, post_date_gmt, post_title, post_content, post_excerpt, post_status, comment_status, ping_status, post_password, to_ping, pinged, post_modified, post_modified_gmt, post_content_filtered, post_parent, guid, menu_order, post_type, post_mime_type, comment_count, post_name, MATCH (post_title, post_content) AGAINST ('{$terms}' {$query_ext}) AS `score` FROM {$wpdb->posts} WHERE MATCH (post_title, post_content) AGAINST ('{$terms}' {$query_ext}) " . "AND post_type = 'post' AND post_status = 'publish' AND post_password = '' AND post_date < '" . current_time('mysql') . "' ORDER BY score DESC LIMIT {$start},{$limit}";
		
		return $sql;
	}
	
	/**
	 * Get the search keyword.
	 * Parse s variable, if it is not available, parse the HTTP URI
	 */
	function get_keywords($sep, $num = 6){
		$s=get_query_var('s');
		if($s!=""){
			return $s;
		}
		
		if(!isset($found_words)){
			$comp_words = $found_words = array();

			$n = preg_match_all("/[\w]{3,15}/", strtolower(html_entity_decode(strip_tags($_SERVER['REQUEST_URI'], ' ' . $_SERVER['QUERY_STRING']))), $found_words);
			if ($n < 1) return $_SERVER['HTTP_HOST'];
		}

		foreach (array_unique((array )$found_words[0]) as $key => $aa_word) $comp_words[] = $aa_word;
			if (sizeof((array )$comp_words) > 0) {
				if (sizeof($comp_words) > $num) array_splice($comp_words, $num + 1);
				return ((sizeof($comp_words) > 0) ? trim(implode($sep, $comp_words)) : $_SERVER['HTTP_HOST']);
		}
	}
	
	/**
	 * Create a Checkbox input field
	 */
	function checkbox($id) {
		$options = $this->options[$id];
		return '<input type="checkbox" id="'.$id.'" name="'.$id.'"'. checked($options,true,false).'/>';
	}
	
	/**
	 * Create a Text input field
	 */
	function textinput($id) {
		$options = $this->options[$id];
		return '<input class="text" type="text" id="'.$id.'" name="'.$id.'" size="30" value="'.$options.'"/>';
	}
	
	/**
	 * Create a dropdown field
	 */
	function select($id, $options, $multiple = false) {
		$opt = get_option($this->optionname);
		$output = '<select class="select" name="'.$id.'" id="'.$id.'">';
		foreach ($options as $val => $name) {
			$sel = '';
			if ($opt[$id] == $val)
				$sel = ' selected="selected"';
			if ($name == '')
				$name = $val;
			$output .= '<option value="'.$val.'"'.$sel.'>'.$name.'</option>';
		}
		$output .= '</select>';
		return $output;
	}
	
	/**
	 * Create a save button
	 */
	function save_button() {
		return '<div class="alignright"><input type="submit" class="button-primary" name="submit" value="Update WP Search Setting &raquo;" /></div><br class="clear"/>';
	}
	
	/**
	 * A souped-up function that reads the plugin file __FILE__ and based on the plugin data (commented at very top of file) creates an array of vars
	 *
	 * @return array
	 */
	function get_plugin_data()
	{
		$data = $this->_readfile(__FILE__, 1500);
		$mtx = $plugin = array();
		preg_match_all('/[^a-z0-9]+((?:[a-z0-9]{2,25})(?:\ ?[a-z0-9]{2,25})?(?:\ ?[a-z0-9]{2,25})?)\:[\s\t]*(.+)/i', $data, $mtx, PREG_SET_ORDER);
		foreach ($mtx as $m) $plugin[trim(str_replace(' ', '-', strtolower($m[1])))] = str_replace(array("\r", "\n", "\t"), '', trim($m[2]));

		$plugin['title'] = '<a href="' . $plugin['plugin-uri'] . '" title="' . __('Visit plugin homepage') . '">' . $plugin['plugin-name'] . '</a>';
		$plugin['author'] = '<a href="' . $plugin['author-uri'] . '" title="' . __('Visit author homepage') . '">' . $plugin['author'] . '</a>';
		$plugin['pb'] = preg_replace('|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__);
		$plugin['page'] = basename(__FILE__);
		$plugin['pagenice'] = str_replace('.php', '', $plugin['page']);
		$plugin['nonce'] = 'form_' . $plugin['pagenice'];
		$plugin['hook'] = 'settings_page_' . $plugin['pagenice'];
		$plugin['action'] = 'options-general.php?page=' . $plugin['page'];

		if (preg_match_all('#(?:([^\W_]{1})(?:[^\W_]*?\W+)?)?#i', $plugin['pagenice'] . '.' . $plugin['version'], $m, PREG_SET_ORDER))$plugin['op'] = '';
		foreach($m as $k) sizeof($k == 2) && $plugin['op'] .= $k[1];
		$plugin['op'] = substr($plugin['op'], 0, 3) . '_';

		return $plugin;
	}
	
	/**
	 * Reads a file with fopen and fread for a binary-safe read.  $f is the file and $b is how many bytes to return, useful when you dont want to read the whole file (saving mem)
	 *
	 * @return string - the content of the file or fread return
	 */
	function _readfile($f, $b = false)
	{
		$fp = NULL;
		$d = '';
		!$b && $b = @filesize($f);
		if (!($b > 0) || !file_exists($f) || !false === ($fp = @fopen($f, 'r')) || !is_resource($fp)) return false;
		if ($b > 4096) while (!feof($fp) && strlen($d) < $b)$d .= @fread($fp, 4096);
		else $d = @fread($fp, $b);
		@fclose($fp);
		return $d;
	}
	
	/**
	 * Create a potbox widget
	 */
	function postbox($id, $title, $content) {
		echo <<<end
		<div id="{$id}" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div>
			<h3 class="hndle"><span>{$title}</span></h3>
			<div class="inside">
				{$content}
			</div>
		</div>
end;
	}	
	
	/**
	 * Create a form table from an array of rows
	 */
	function form_table($rows) {
		$content = '<table class="form-table">';
		$i = 1;
		foreach ($rows as $row) {
			$class = '';
			if ($i > 1) {
				$class .= 'bws_row';
			}
			if ($i % 2 == 0) {
				$class .= ' even';
			}
			$content .= '<tr id="'.$row['id'].'_row" class="'.$class.'"><th valign="top" scrope="row">';
			if (isset($row['id']) && $row['id'] != '')
				$content .= '<label for="'.$row['id'].'">'.$row['label'].':</label>';
			else
				$content .= $row['label'];
			$content .= '</th><td valign="top">';
			$content .= $row['content'];
			$content .= '</td></tr>'; 
			if ( isset($row['desc']) && !empty($row['desc']) ) {
				$content .= '<tr class="'.$class.'"><td colspan="2" class="bws_desc"><small>'.$row['desc'].'</small></td></tr>';
			}
				
			$i++;
		}
		$content .= '</table>';
		return $content;
	}
	
	/**
	 * Create a "plugin like" box.
	 */
	function plugin_like() {
		$content = '<p>'.__('Why not do any or all of the following:','ivanplugin').'</p>';
		$content .= '<ul>';
		$content .= '<li><a href="'.$this->plugin['plugin-uri'].'">'.__('Link to it so other folks can find out about it.','ivanplugin').'</a></li>';
		$content .= '<li><a href="http://wordpress.org/extend/plugins/fast-wordpress-search/">'.__('Let other people know that it works with your WordPress setup.','ivanplugin').'</a></li>';
		$content .= '<li><a href="http://www.ivankristianto.com/internet/blogging/guide-to-improve-your-wordpress-blog-performance-for-free/1471/">'.__('Guide To Improve Your WordPress Blog Performance For Free.','ivanplugin').'</a></li>';
		$content .= '</ul>';
		$this->postbox($hook.'like', 'Like this plugin?', $content);
	}

	/**
	 * Info box with link to the bug tracker.
	 */
	function plugin_support() {
		$content = '<p>If you\'ve found a bug in this plugin, please submit it in the <a href="http://www.ivankristianto.com/about/">IvanKristianto.com Contact Form</a> with a clear description.</p>';
		$this->postbox($this->plugin['pagenice'].'support', __('Found a bug?','ystplugin'), $content);
	}

	/**
	 * Box with latest news from IvanKristianto.com
	 */
	function news() {
		include_once(ABSPATH . WPINC . '/feed.php');
		$rss = fetch_feed('http://feeds2.feedburner.com/ivankristianto');
		$rss_items = $rss->get_items( 0, $rss->get_item_quantity(5) );
		$content = '<ul>';
		if ( !$rss_items ) {
			$content .= '<li class="ivankristianto">no news items, feed might be broken...</li>';
		} else {
			foreach ( $rss_items as $item ) {
				$content .= '<li class="ivankristianto">';
				$content .= '<a class="rsswidget" href="'.esc_url( $item->get_permalink(), $protocolls=null, 'display' ).'">'. htmlentities($item->get_title()) .'</a> ';
				$content .= '</li>';
			}
		}						
		$content .= '<li class="rss"><a href="http://feeds2.feedburner.com/ivankristianto">Subscribe with RSS</a></li>';
		//$content .= '<li class="email"><a href="http://ivankristianto.com/email-blog-updates/">Subscribe by email</a></li>';
		$content .= '</ul>';
		$this->postbox('ivankristiantolatest', 'Latest from IvanKristianto.com', $content);
	}

	function text_limit( $text, $limit, $finish = ' [&hellip;]') {
		if( strlen( $text ) > $limit ) {
			$text = substr( $text, 0, $limit );
			$text = substr( $text, 0, - ( strlen( strrchr( $text,' ') ) ) );
			$text .= $finish;
		}
		return $text;
	}	
}

if (!function_exists('fwp_search_function')) 
{
	/**
	 */
	function fwp_search_function()
	{
		global $fwp_search;
		if (!is_object($fwp_search_))$fwp_search_ = new fwp_search();
		$fwp_search->output();
	}
}

$fwp_search = new fwp_search();
add_action('init', array(&$fwp_search, 'init'));

/**
 * 
 *
 * @return
 */
function fwp_search_activation_hook(){
	global $wpdb, $fwp_search;
	$wpdb->hide_errors();
	
	$wpdb->query('ALTER TABLE '.$wpdb->posts.' ENGINE = MYISAM;');
	$wpdb->query('ALTER TABLE '.$wpdb->posts.' DROP INDEX post_related');
	$wpdb->query('ALTER TABLE '.$wpdb->posts.' ADD FULLTEXT post_related ( post_title , post_content )');
	$wpdb->show_errors();

	if(!is_object($fwp_search))$fwp_search=new fwp_search();
	$fwp_search->default_options();
}

if (is_admin()) :
	register_activation_hook(__FILE__, 'fwp_search_activation_hook');

	add_action('admin_menu',
		create_function('', 'global $fwp_search; if(!is_object($fwp_search))$fwp_search=new fwp_search(); add_options_page( "Fast WP Search", "Fast  WP Search", "administrator", "fwp-search.php", array(&$fwp_search,"options_page"));'));

	add_filter('plugin_links_fwp-search/fwp-search.php',
		create_function('$l', 'return array_merge(array("<a href=\"options-general.php?page=fwp-search.php\">Settings</a>"), $l);'));

	add_action('deactivate_fwp-search/fwp-search.php',
		create_function('', 'foreach ( array("options", "plugin", "code") as $pn ) delete_option("fwp_search_{$pn}" );'));

	add_action('admin_footer-settings_page_fwp-search',
		create_function('','$g="";$g.="\n<script type=\"text/javascript\">\nvar codepress_path=\"'.includes_url("js/codepress/").'\";jQuery(\"#form\").submit(function(){\n";foreach(array("html","css","javascript") as $k)$g.="if (jQuery(\"#{$k}_cp\").length)jQuery(\"#{$k}_cp\").val({$k}.getCode()).removeAttr(\"disabled\");";$g.="});\n</script>\n";echo $g;'));
endif;
?>