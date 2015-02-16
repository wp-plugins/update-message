<?php
/**
Plugin Name: Update Message
Plugin Tag: posts, post, update, message
Description: <p>Add an update box in posts. </p><p>This box can contain a message, for instance in order to point out that the post have been modified of to stress that the post in no longer up to date</p><p>The message can be configured direcly when editing a post. There is a box 'Update message' added on the left.</p><p>In addition, you may use a shortcode [maj update='dd/mm/yy' expire='dd/mm/yy']xxx[/maj]</p><p>Plugin developped from the orginal plugin <a href="http://wordpress.org/plugins/wp-update-message/">WP Update Message</a>. </p><p>This plugin is under GPL licence. </p>
Version: 1.3.6
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/plugins/update-message/
License: GPL3
*/

require_once('core.php') ; 

class updatemessage extends pluginSedLex {
	/** ====================================================================================================================================================
	* Initialisation du plugin
	* 
	* @return void
	*/
	static $instance = false;
	var $path = false;

	protected function _init() {
		global $wpdb ; 
		// Configuration
		$this->pluginName = 'Update Message' ; 
		$this->tableSQL = "" ; 
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('updatemessage','uninstall_removedata'));
		
		//Paramètres supplementaires
		add_action('save_post', array($this,'update_message_save'));
		add_action('admin_menu', array($this, 'meta_box'));
		add_shortcode('maj', array( $this, 'maj_shortcode' ) );
	}
	
	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('updatemessage'.'_options') ;
		if (is_multisite()) {
			delete_site_option('updatemessage'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'updatemessage')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'updatemessage' ) ; 
		}
		
		// DELETE FILES if needed
		//SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/my_plugin/"); 
		$plugins_all = 	get_plugins() ; 
		$nb_SL = 0 ; 	
		foreach($plugins_all as $url => $pa) {
			$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url);
			if ($info['Framework_Email']=="sedlex@sedlex.fr"){
				$nb_SL++ ; 
			}
		}
		if ($nb_SL==1) {
			SLFramework_Utils::rm_rec(WP_CONTENT_DIR."/sedlex/"); 
		}
	}
	
	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		$buttons[] = array(__('Add Update tags', $this->pluginID), '[maj update="'.date_i18n("d/m/y").'"]', '[/maj]', plugin_dir_url("/").'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/maj_button.png') ; 
		return $buttons ; 
	}

	/** ====================================================================================================================================================
	* Create the meta box for storing the message
	* 
	* 
	*/

	function meta_box() {
		add_meta_box('post_info', 'Update message', array($this,'custom_meta_box'), 'post', 'side', 'high');
	}
	
	function custom_meta_box() {
		global $post;
		echo '<input type="hidden" name="myplugin_noncename" id="myplugin_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		?>
		<textarea style="width: 98%;" cols="40" rows="6" name="update_message_text" id="update_message_text"><?php echo get_post_meta($post->ID, 'update_message_text', true); ?></textarea>
		<p><?php echo __('If you have been updated this post with new information, use this box to inform about it.', $this->pluginID) ; ?></p>
		<p><?php echo __('Please note that the different update messages have to be separed with 3 dashes:', $this->pluginID) ; ?> <code>---</code></p>
		<p><?php echo sprintf(__('If you want to force the date displayed, the first line must be %s', $this->pluginID), "<code>*dd/mm/yy*</code>") ; ?> </p>
		<p><?php echo __('Example:', $this->pluginID) ; ?></p>
		<p><code>*dd/mm/yy*<br/>
		<?php echo __('The first message', $this->pluginID) ; ?><br/>
		---<br/>
		*dd/mm/yy*<br/>
		<?php echo __('The second message', $this->pluginID) ; ?></code></p>
		<?php
	}
	
	/** ====================================================================================================================================================
	* Saving the message
	* 
	* @return 
	*/
	
	function update_message_save($post_id) {
		global $_POST ; 
		
		if (!isset($_POST['myplugin_noncename'])) {
			return $post_id; 
		}
		
		if ( !wp_verify_nonce( $_POST['myplugin_noncename'], plugin_basename(__FILE__) )) {
			return $post_id;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
				return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ))
				return $post_id;
		}
		$all_msg = explode("---",$_POST['update_message_text']) ; 
		$resultat = "" ; 
		foreach ($all_msg as $a) {
			if ($resultat!="")
				$resultat .= "---" ; 
			if ((!preg_match('|\*[0-3][0-9]\/[0-1][0-9]\/[0-9]{2}\*|',$a))&&(trim($a)!="")) {
				$a = "*".date("d")."/".date("m")."/".date("y")."*\n".$a ; 
			}
			$resultat .= $a ; 
		}
		
		update_post_meta($post_id, 'update_message_text', trim($resultat) );
	}
	
	/** ====================================================================================================================================================
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {	
		global $post ;
		
		if ($excerpt) {
			if ($this->get_param('show_home')) {
				$update_message_text = trim(get_post_meta($post->ID, 'update_message_text', true));
				$html = "" ; 
				// On cree le conteneur HTML
				if ($update_message_text != '') {
				
					$all_msg = explode("---",$update_message_text) ; 
					$resultat = "" ; 
					$html = stripslashes($this->get_param('html')) ; 
					foreach ($all_msg as $a) {
			
						preg_match('|\*([0-3][0-9])\/([0-1][0-9])\/([0-9]{2})\*|',$a, $date) ; 
						$a = trim(str_replace("*".$date[1]."/".$date[2]."/".$date[3]."*", "", $a)) ; 
						
						$b = str_replace('%ud%', date_i18n(get_option('date_format'), strtotime($date[2]."/".$date[1]."/".$date[3])), $html);
						$b = str_replace('%pd%', get_the_date(get_option('date_format')), $b);
						$b = str_replace('%ut%', $a, $b);
						
						$resultat .= $b ; 
						
					}
					
					$array = $this->get_param('position_home') ; 
					$pos = "top" ; 
					foreach ($array as $a) {
						if ($a != str_replace("*", "", $a)) {
							$pos = str_replace("*", "", $a) ;
						}
					}
					
					switch ($pos) {
						case "top":
						case "":
							$content = $resultat . $content;
							break;
						case "bottom":
							$content = $content . $resultat;
							break;
						case "both":
							$content = $resultat . $content . $resultat;
							break;
					}
				}
			}
			return $content;		
		} else {
			$update_message_text = trim(get_post_meta($post->ID, 'update_message_text', true));
			$html = "" ; 
			// On cree le conteneur HTML
			if ($update_message_text != '') {
			
			
				$all_msg = explode("---",$update_message_text) ; 
				$resultat = "" ; 
				$html = stripslashes($this->get_param('html')) ; 
				foreach ($all_msg as $a) {
		
					preg_match('|\*([0-3][0-9])\/([0-1][0-9])\/([0-9]{2})\*|',$a, $date) ; 
					$a = trim(str_replace("*".$date[1]."/".$date[2]."/".$date[3]."*", "", $a)) ; 
					
					$b = str_replace('%ud%', date_i18n(get_option('date_format'), strtotime($date[2]."/".$date[1]."/".$date[3])), $html);
					$b = str_replace('%pd%', get_the_date(get_option('date_format')), $b);
					$b = str_replace('%ut%', $a, $b);
					
					$resultat .= $b ; 
					
				}
				
				$array = $this->get_param('position') ; 
				$pos = "none" ; 
				foreach ($array as $a) {
					if ($a != str_replace("*", "", $a)) {
						$pos = str_replace("*", "", $a) ;
					}
				}
				switch ($pos) {
					case "top":
					case "":
						$content = $resultat . $content;
						break;
					case "bottom":
						$content = $content . $resultat;
						break;
					case "both":
						$content = $resultat . $content . $resultat;
						break;
				}
			}		
			return $content;
		}
	}
	
	/** ====================================================================================================================================================
	* Define the default option value of the plugin
	* 
	* @return variant of the option
	*/
	function get_default_option($option) {
		switch ($option) {
			case 'html'		 	: return '*<div class="update_message">
	<small>Updated: %ud%</small>
	<p>%ut%</p>
</div>' 	; break ; 
			case 'css'		 	: return '*.update_message {
	background: #fff url(../../update-message/img/bkg-yellow.gif) repeat-x top;
	border: 1px solid #e6db55;
	padding: 15px 15px 5px 15px;
	margin: 10px 0 10px 0;
	-moz-border-radius: 5px;
	-khtml-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

.update_message p {
	font: normal 11px/14px Arial;
	margin: 0;
	padding: 0 0 10px 0;
	color: #555;
}

.update_message small {
	font: bold 11px/14px Arial;
	color: #555;
	text-transform: uppercase;
}' ; break ; 

			case 'position' 	: return array("*top", "bottom", "both", "none") 	; break ; 
			case 'position_home' 	: return array("*top", "bottom", "both") 	; break ; 
			case 'show_home' 	: return false	; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		$content = str_replace("../..", plugins_url(), $this->get_param('css')) ; 
		$this->add_inline_css($content) ; 
	}
	
	/** ====================================================================================================================================================
	* The configuration page
	* 
	* @return void
	*/
	function configuration_page() {
	
		?>
		<div class="plugin-titleSL">
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		
		<div class="plugin-contentSL">		
			<?php echo $this->signature ; ?>

			<!--debut de personnalisation-->
		<?php
			
			// On verifie que les droits sont corrects
			$this->check_folder_rights( array() ) ; 
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
			$tabs = new SLFramework_Tabs() ; 
			
			ob_start() ; 
					$params = new SLFramework_Parameters($this, 'tab-parameters') ; 
					$params->add_title(__('Where do you want to place the update message?',$this->pluginID)) ; 
					$params->add_param('position', __('Placement:',$this->pluginID)) ; 
					$params->add_comment(sprintf(__('You can also add a shorcode %s to add an updated box wherever you want in your posts', $this->pluginID), '<code>[maj update="dd/mm/yy" expire="dd/mm/yy"]your updated text[/maj]</code>')) ; 
					$params->add_param('show_home', __('Show the update message on home page:',$this->pluginID), '', '', array('position_home')) ; 
					$params->add_comment(__('Indicate if you want the update message to be shown in the summary of the posts in your home page.',$this->pluginID)); 
					$params->add_param('position_home', __('Placement for the excerpt:',$this->pluginID)) ; 

					$params->add_title(__('How do you want to render the message?',$this->pluginID)) ; 
					$params->add_param('html', __('HTML:',$this->pluginID)) ; 
					$params->add_comment(__('The default HTML is:',$this->pluginID)); 
					$params->add_comment_default_value('html') ; 
					$params->add_comment(__('The following expressions will be replaced:',$this->pluginID)); 
					$comment = "<code>%pd%</code> = ".__('Published date',$this->pluginID)."</span><br/>" ; 
					$comment .= "<code>%ud%</code> = ".__('Updated date',$this->pluginID)."</span><br/>" ; 
					$comment .= "<code>%ut%</code> = ".__('Updated text',$this->pluginID)."</span>" ; 
					$params->add_comment($comment) ; 	
					$params->add_param('css', __('CSS:',$this->pluginID)) ; 
					$params->add_comment(__('The default CSS is:',$this->pluginID)) ; 
					$params->add_comment_default_value('css') ; 
					$params->flush() ; 
					
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			

			// HOW To
			ob_start() ;
				echo "<p>".__('This plugin may display update/warning boxes in your posts/pages (for instance, to indicate that the content is not up-to-date, that the content has been changed, etc.).', $this->pluginID)."</p>" ;
			$howto1 = new SLFramework_Box (__("Purpose of that plugin", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				echo "<p>".sprintf(__('To display the update box, you may add an update box by adding to your post %s', $this->pluginID), '<code>[maj update="dd/mm/yy" expire="dd/mm/yy"]Your text[/maj]</code>')."</p>" ;
				echo "<p>".sprintf(__(' - use %s to indicate the date of the update', $this->pluginID), '<code>update="dd/mm/yy"</code>')."</p>" ;
				echo "<p>".sprintf(__(' - use %s to indicate the date when the box is to be removed as the update message had expired', $this->pluginID), '<code>expire="dd/mm/yy"</code>')."</p>" ;
				echo "<p>".__('A button is available in the post/page editor.', $this->pluginID)."</p>" ;
				echo "<p>".__('In the post/page editor, there is also a update widget on the left.', $this->pluginID)."</p>" ;
			$howto2 = new SLFramework_Box (__("How to display the update box?", $this->pluginID), ob_get_clean()) ; 
			ob_start() ;
				 echo $howto1->flush() ; 
				 echo $howto2->flush() ; 
			$tabs->add_tab(__('How To',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_how.png") ; 				

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Translation($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean(), plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png" ) ; 	

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new SLFramework_Feedback($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				$trans = new SLFramework_OtherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			echo $this->signature ; ?>
		</div>
		<?php
	}
	
	//[maj update="dd/mm/yy" expire="dd/mm/yy"]text[/maj]
	
	function maj_shortcode( $_atts, $text ) {
		
		$atts = shortcode_atts( array(
			'update' => "", 
			'expire' => ""
		), $_atts );
		
		$html = stripslashes($this->get_param('html')) ; 
		
		// expire
		if (preg_match('|([0-3][0-9])\/([0-1][0-9])\/([0-9]{2})|',$atts['expire'], $exp_date)) {
			$exp_time = strtotime($exp_date[2]."/".$exp_date[1]."/".$exp_date[3]) ; 
			if ($exp_time<time()) {
				return "" ; // On retourne rien car on ne veut plus l'afficher
			}
		}
		
		if (preg_match('|([0-3][0-9])\/([0-1][0-9])\/([0-9]{2})|',$atts['update'], $date)) { 
			$b = str_replace('%ud%', date_i18n(get_option('date_format'), strtotime($date[2]."/".$date[1]."/".$date[3])), $html);
		} else {
			$b = str_replace('%ud%', $atts['update'], $html);
		}
		$b = str_replace('%pd%', get_the_date(get_option('date_format')), $b);
		$b = str_replace('%ut%', $text, $b);
					
		return $b ;
	}
}
$updatemessage = updatemessage::getInstance();
?>