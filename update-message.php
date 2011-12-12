<?php
/**
Plugin Name: Update Message
Description: <p>Add an update box in posts. </p><p>This box can contain a message, for instance in order to point out that the post have been modified of to stress that the post in no longer up to date</p><p>The message can be configured direcly when editing a post. There is a box 'Update message' added on the left.</p><p>Plugin developped from the orginal plugin <a href="http://wordpress.org/extend/plugins/wp-update-message/">WP Update Message</a>. </p><p>This plugin is under GPL licence. </p>
Version: 1.1.6
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/extend/plugins/update-message/
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
	static $path = false;

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
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
		
		//Paramètres supplementaires
		add_action('save_post', array($this,'update_message_save'));
		add_filter('the_content', array($this,'update_message_content'));
		add_action('admin_menu', array($this, 'meta_box'));
		add_shortcode( 'maj', array( $this, 'maj_shortcode' ) );
		add_action('wp_print_styles', array( $this, 'ajoute_inline_css'));

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
		$all_msg = split("---",$_POST['update_message_text']) ; 
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
	* Printing the message of update in the post
	* 
	* @return variant of the option
	*/

	function update_message_content($content) {
		global $post ;

		if(is_single() || is_page()) {
			$update_message_text = trim(get_post_meta($post->ID, 'update_message_text', true));
			$html = "" ; 
			// On cree le conteneur HTML
			if ($update_message_text != '') {
			
			
				$all_msg = split("---",$update_message_text) ; 
				$resultat = "" ; 
				$html = stripslashes($this->get_param('html')) ; 
				foreach ($all_msg as $a) {
		
					preg_match('|\*([0-3][0-9]\/[0-1][0-9]\/[0-9]{2})\*|',$a, $date) ; 
					$a = trim(str_replace("*".$date[1]."*", "", $a)) ; 
					
					$b = str_replace('%ud%', $date[1], $html);
					$b = str_replace('%pd%', get_the_time(), $b);
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
		}
		
		return $content;
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
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* Add CSS
	* 
	* @return void
	*/
	
	function ajoute_inline_css() {
		$this->add_inline_css($this->get_param('css')) ; 
	}
	
	/** ====================================================================================================================================================
	* The configuration page
	* 
	* @return void
	*/
	function configuration_page() {
	
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">
			<?php echo $this->signature ; ?>
			<p><?php echo __('This plugin creates information box in posts/page to contain update information', $this->pluginID) ; ?></p>
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
			$tabs = new adminTabs() ; 
			
			ob_start() ; 
					?>
					<p><?php echo __('Here is the parameters of the plugin. Please modify them at your convenience.',$this->pluginID) ; ?> </p>
					<?php					
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('Where do you want to place the update message?',$this->pluginID)) ; 
					$params->add_param('position', __('Placement:',$this->pluginID)) ; 
					$params->add_comment(sprintf(__('You can also add a shorcode %s to add an updated box wherever you want in your posts', $this->pluginID), '<code>[maj update="jj/mm/yy"]your updated text[/maj]</code>')) ; 
					$params->add_title(__('How do you want to render the message?',$this->pluginID)) ; 
					$params->add_param('html', __('HTML:',$this->pluginID)) ; 
					$comment = __('The standard html is:',$this->pluginID); 
					$comment .= "<br/><span style='margin-left: 30px;'><code>&lt;div class=\"update_message\"&gt;</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 60px;'><code>&lt;small&gt;Updated: %ud%&lt;/small&gt</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 60px;'><code>&lt;p&gt;%ut%&lt;/p&gt</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 30px;'><code>&lt;/div&gt</code></span><br/>";
					$comment .= "<code>%pd%</code> = ".__('Published date',$this->pluginID)."</span><br/>" ; 
					$comment .= "<code>%ud%</code> = ".__('Updated date',$this->pluginID)."</span><br/>" ; 
					$comment .= "<code>%ut%</code> = ".__('Updated text',$this->pluginID)."</span>" ; 
					$params->add_comment($comment) ; 	
					$params->add_param('css', __('CSS:',$this->pluginID)) ; 
					$comment = __('The standard CSS is:',$this->pluginID); 
					$comment .= "<br/><code>.update_message {<br/>
&nbsp; &nbsp; &nbsp; background: #fff url(../../update-message/img/bkg-yellow.gif) repeat-x top;<br/>
&nbsp; &nbsp; &nbsp; border: 1px solid #e6db55;<br/>
&nbsp; &nbsp; &nbsp; padding: 15px 15px 5px 15px;<br/>
&nbsp; &nbsp; &nbsp; margin: 10px 0 10px 0;<br/>
&nbsp; &nbsp; &nbsp; -moz-border-radius: 5px;<br/>
&nbsp; &nbsp; &nbsp; -khtml-border-radius: 5px;<br/>
&nbsp; &nbsp; &nbsp; -webkit-border-radius: 5px;<br/>
&nbsp; &nbsp; &nbsp; border-radius: 5px;<br/>
}<br/>
<br/>
.update_message p {<br/>
&nbsp; &nbsp; &nbsp; font: normal 11px/14px Arial;<br/>
&nbsp; &nbsp; &nbsp; margin: 0;<br/>
&nbsp; &nbsp; &nbsp; padding: 0 0 10px 0;<br/>
&nbsp; &nbsp; &nbsp; color: #555;<br/>
}<br/>
<br/>
.update_message small {<br/>
&nbsp; &nbsp; &nbsp; font: bold 11px/14px Arial;<br/>
&nbsp; &nbsp; &nbsp; color: #555;<br/>
&nbsp; &nbsp; &nbsp; text-transform: uppercase;<br/>
}</code><br/>";

					$params->add_comment($comment) ; 						
					$params->flush() ; 
					
			$tabs->add_tab(__('Parameters',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new translationSL($this->pluginID, $plugin) ; 
				$trans->enable_translation() ; 
			$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean(), WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png" ) ; 	

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				$trans = new otherPlugins("sedLex", array('wp-pirates-search')) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			echo $this->signature ; ?>
		</div>
		<?php
	}
	
	//[maj update="jj/mm/yy"]text[/maj]
	
	function maj_shortcode( $_atts, $text ) {
		
		$atts = shortcode_atts( array(
			'update' => ""
		), $_atts );
		
		$html = stripslashes($this->get_param('html')) ; 
		
					
		$b = str_replace('%ud%', $atts['update'], $html);
		$b = str_replace('%pd%', get_the_time(), $b);
		$b = str_replace('%ut%', $text, $b);
					
		return $b ; 					
		
		
	}

}

$updatemessage = updatemessage::getInstance();

?>