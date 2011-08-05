<?php
/*
Plugin Name: Update Message
Description: <p>Add an update box in posts. </p><p>This box can contain a message, for instance in order to point out that the post have been modified of to stress that the post in no longer up to date</p><p>The message can be configured direcly when editing a post. There is a box 'Update message' added on the left.</p><p>Plugin developped from the orginal plugin <a href="http://wordpress.org/extend/plugins/wp-update-message/">WP Update Message</a>. </p><p>This plugin is under GPL licence. </p>
Version: 1.0.2
Author: SedLex
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
		// Configuration
		$this->pluginName = 'Update Message' ; 
		$this->tableSQL = "" ; 
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		//Init et des-init
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'uninstall'));
		
		//Paramètres supplementaires
		add_action('save_post', array($this,'update_message_save'));
		add_filter('the_content', array($this,'update_message_content'));
		add_action('admin_menu', array($this, 'meta_box'));

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
		<p>If you have been updated this post with new information, use this box to inform about it.</p>
		<p>Please not that the different update messages have to be separed with a <code>---</code></p>
		<p>If you want to force the date displayed, the first line must be <code>*dd/mm/yy*</code></p>
		<p>Example:</p>
		<p><code>*dd/mm/yy*<br/>
		The first message<br/>
		---<br/>
		*dd/mm/yy*<br/>
		The second message</code></p>
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
			case 'position' 	: return array("*top", "bottom", "both", "none") 	; break ; 
		}
		return null ;
	}


	/** ====================================================================================================================================================
	* The configuration page
	* 
	* @return void
	*/
	function configuration_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . $this->pluginID;
	
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
			<?php echo $this->signature ; ?>
			<p>This plugin creates information box in posts/page to contain "update information"</p>
			<!--debut de personnalisation-->
		<?php
			
			//==========================================================================================
			//
			// Mise en place du systeme d'onglet
			//		(bien mettre a jour les liens contenu dans les <li> qui suivent)
			//
			//==========================================================================================
	?>		
			<script>jQuery(function($){ $('#tabs').tabs(); }) ; </script>		
			<div id="tabs">
				<ul class="hide-if-no-js">
					<li><a href="#tab-parameters"><? echo __('Parameters',$this->pluginName) ?></a></li>					
				</ul>
				<?php
				//==========================================================================================
				//
				// Premier Onglet 
				//		(bien verifier que id du 1er div correspond a celui indique dans la mise en 
				//			place des onglets)
				//
				//==========================================================================================
				?>
				<div id="tab-parameters" class="blc-section">
				
					<h3 class="hide-if-js"><? echo __('Parameters',$this->pluginName) ?></h3>
					<p><?php echo __('Here is the parameters of the plugin. Please modify them at your convenience.',$this->pluginName) ; ?> </p>
				
					<?php					
					$params = new parametersSedLex($this, 'tab-parameters') ; 
					$params->add_title(__('Where do you want to place the update message?',$this->pluginName)) ; 
					$params->add_param('position', __('Placement:',$this->pluginName)) ; 
					$params->add_title(__('How do you want to render the message?',$this->pluginName)) ; 
					$params->add_param('html', __('HTML:',$this->pluginName)) ; 
					$comment = __('The standard html is:',$this->pluginName); 
					$comment .= "<br/><span style='margin-left: 30px;'><code>&lt;div class=\"update_message\"&gt;</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 60px;'><code>&lt;small&gt;Updated: %ud%&lt;/small&gt</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 60px;'><code>&lt;p&gt;%ut%&lt;/p&gt</code></span><br/>" ; 
					$comment .= "<span style='margin-left: 30px;'><code>&lt;/div&gt</code></span><br/>";
					$comment .= "<code>%pd%</code> = Published date</span><br/>" ; 
					$comment .= "<code>%ud%</code> = Updated date</span><br/>" ; 
					$comment .= "<code>%ut%</code> = Updated text</span>" ; 
					$params->add_comment($comment) ; 	
					$params->flush() ; 
					
					?>
				</div>
			</div>
			<!--fin de personnalisation-->
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
}

$updatemessage = updatemessage::getInstance();

?>