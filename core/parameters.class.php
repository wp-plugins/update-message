<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 
/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enable the creation of form to manage the parameter of your plugin 
*/
if (!class_exists("parametersSedLex")) {
	class parametersSedLex {
		
		var $output ; 
		var $maj ; 
		var $modified ; 
		var $warning ; 
		var $error ; 
		var $hastobeclosed ;
		var $obj ; 
		
		/** ====================================================================================================================================================
		* Constructor of the object
		* 
		* @see  pluginSedLex::get_param
		* @see  pluginSedLex::set_param
		* @param class $obj a reference to the object containing the parameter (usually, you need to provide "$this"). If it is "new rootSLframework()", it means that it is the framework parameters.
		* @param string $tab if you want to activate a tabulation after the submission of the form
		* @return parametersSedLex the form class to manage parameter/options of your plugin
		*/
		
		function parametersSedLex($obj, $tab="") {
			$this->buffer = array() ; 
			$this->obj = $obj ; 
		}
		
		/** ====================================================================================================================================================
		* Add title in the form
		* 
		* @param string $title the title to add
		* @return void
		*/
		function add_title($title)  {
			$this->buffer[] = array('title', $title) ; 
		}

		/** ====================================================================================================================================================
		* Add a comment in the form
		* 
		* @param string $comment the comment to add 
		* @return void
		*/
		function add_comment($comment)  {
			$this->buffer[] = array('comment', $comment) ; 
		}

		/** ====================================================================================================================================================
		* Add a textarea, input, checkbox, etc. in the form to enable the modification of parameter of the plugin
		* 	
		* Please note that the default value of the parameter (defined in the  <code>get_default_option</code> function) will define the type of input form. If the default  value is a: <br/>&nbsp; &nbsp; &nbsp; - string, the input form will be an input text <br/>&nbsp; &nbsp; &nbsp; - integer, the input form will be an input text accepting only integer <br/>&nbsp; &nbsp; &nbsp; - string beggining with a '*', the input form will be a textarea <br/>&nbsp; &nbsp; &nbsp; - string equals to '[file]$path', the input form will be a file input and the file will be stored at $path (relative to the upload folder)<br/>&nbsp; &nbsp; &nbsp; - string equals to '[password]$password', the input form will be a password input ; <br/>&nbsp; &nbsp; &nbsp; - array of string, the input form will be a dropdown list<br/>&nbsp; &nbsp; &nbsp; - boolean, the input form will be a checkbox 
		*
		* @param string $param the name of the parameter/option as defined in your plugin and especially in the <code>get_default_option</code> of your plugin
		* @param string $name the displayed name of the parameter in the form
		* @param string $forbid regexp which will delete some characters in the submitted string (only a warning is raised) : For instance <code>$forbid = "/[^a-zA-Z0-9]/"</code> will remove all the non alphanumeric value
		* @param string $allow regexp which will verify that the submitted string will respect this rexexp, if not, the submitted value is not saved  and an erreor is raised : For instance, <code>$allow = "/^[a-zA-Z]/"</code> require that the submitted string begin with a nalpha character
		* @param array $related a list of the other params that will be actived/deactivated when this parameter is set to true/false (thus, this param should be a boolean)
		* @return void
		*/

		function add_param($param, $name, $forbid="", $allow="", $related=array())  {
			$this->buffer[] = array('param', $param, $name, $forbid, $allow, $related) ; 
		}
		
		/** ====================================================================================================================================================
		* Print the form with parameters
		* 	
		* @return void
		*/
		function flush()  {
			global $_POST ; 
			global $_FILES ; 
			
			// We create the beginning of the form
				
			$this->output =  "<h3>".__("Parameters",'SL_framework')."</h3>" ; 
				
			if ($this->obj->getPluginID()!="") {
				$this->output .= "<p>".__("Here are the parameters of the plugin. Modify them at will to fit your needs.","SL_framework")."</p>" ; 
			} else {
				$this->output .= "<p>".__("Here are the parameters of the framework. Modify them at will to fit your needs.","SL_framework")."</p>" ; 			
			}
			$this->output .= "<div class='wrap parameters'><form enctype='multipart/form-data' method='post' action='".$_SERVER["REQUEST_URI"]."'>\n" ; 
			
			// We compute the parameter output
			$hastobeclosed = false ; 
			$maj = false ; 
			$modified = false ; 
			$error = false ; 
			$warning = false ; 
			$toExecuteWhenLoaded = "" ; 
				
			for($iii=0; $iii<count($this->buffer); $iii++) {
				$ligne = $this->buffer[$iii] ; 
				
				// Is it a title
				if ($ligne[0]=="title") {	
					if ($hastobeclosed) {
						$this->output .= $currentTable->flush()."<br/>" ; 
					} 
					// We create a new table 
					$currentTable = new adminTable() ; 
					$currentTable->removeFooter() ; 
					$currentTable->title(array($ligne[1], "") ) ; 
					$hastobeclosed = true ;
				}
				
				// Is it a comment
				if ($ligne[0]=="comment") {	
					if (!$hastobeclosed) {
						// We create a default table as no title has been provided
						$currentTable = new adminTable() ; 
						$currentTable->removeFooter() ; 
						$currentTable->title(array(__("Parameters","SL_framework"), __("Values","SL_framework")) ) ; 
						$hastobeclosed = true ; 
					}
					$cl = "<p class='paramComment' style='color: #a4a4a4;'>".$ligne[1]."</p>" ; 
					// We check if there is a comment just after it
					while (isset($this->buffer[$iii+1])) {
						if ($this->buffer[$iii+1][0]!="comment") break ; 
						$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
						$iii++ ; 
					}
					$cel_label = new adminCell($cl) ; 
					$cel_value = new adminCell("") ; 
					$currentTable->add_line(array($cel_label, $cel_value), '1') ; 
				}
				
				
				// Is it a param
				if ($ligne[0]=="param") {	
					$param = $ligne[1] ; 
					$name = $ligne[2] ; 
					$forbid = $ligne[3] ; 
					$allow = $ligne[4] ; 
					$related = $ligne[5] ; 
					if (!$hastobeclosed) {
						// We create a default table as no title has been provided
						$currentTable = new adminTable() ; 
						$currentTable->removeFooter() ; 
						$currentTable->title(array(__("Parameters","SL_framework"), __("Values","SL_framework")) ) ; 
						$hastobeclosed = true ; 
					}
					
					// What is the type of the parameter ?
					//---------------------------------------
					$type = "string" ; 
					if (is_bool($this->obj->get_default_option($param))) $type = "boolean" ; 
					if (is_int($this->obj->get_default_option($param))) $type = "int" ; 
					if (is_array($this->obj->get_default_option($param))) $type = "list" ; 
					// C'est un text si dans le texte par defaut, il y a une etoile
					if (is_string($this->obj->get_default_option($param))) {
						if (str_replace("*","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "text" ; 
					}
					// C'est un file si dans le texte par defaut est egal a [file]
					if (is_string($this->obj->get_default_option($param))) {
						if (str_replace("[file]","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "file" ; 
					}
					// C'est un password si dans le texte par defaut est egal a [password]
					if (is_string($this->obj->get_default_option($param))) {
						if (str_replace("[password]","",$this->obj->get_default_option($param)) != $this->obj->get_default_option($param)) $type = "password" ; 
					}
					
					// We update the param
					//---------------------------------------
					$problem_e = "" ; 
					$problem_w = "" ; 
					if (isset($_POST['submitOptions'])) {
						$maj = true ; 
						// Is it a boolean ?
						if ($type=="boolean") {
							if ($_POST[$param]) {
								$this->obj->set_param($param, true) ; 
								$modified = true ; 
							} else {
								$this->obj->set_param($param, false) ; 
								$modified = true ; 
							}
						} 
						
						// Is it an integer ?
						if ($type=="int") {
							if (Utils::is_really_int($_POST[$param])) {
								$this->obj->set_param($param, (int)$_POST[$param]) ; 
								$modified = true ; 
							} else {
								$problem_e .= "<p>".__('Error: the submitted value is not an integer and thus, the parameter has not been updated!', 'SL_framework')."</p>\n" ; 
								$error = true ; 
							}
						} 
						
						// Est ce que c'est bien un string
						if (($type=="string")||($type=="text")||($type=="password")) {
							$tmp = $_POST[$param] ; 
							if ($forbid!="") {
								$tmp = preg_replace($forbid, '', $_POST[$param]) ; 
							} 
		
							if ($tmp!=$_POST[$param]) {
								$problem_w .= "<p>".__('Warning: some characters have been removed because they are not allowed here', 'SL_framework')." (".$forbid.")!</p>\n" ; 
								$warning = true ; 
							}
							
							if (($allow!="")&&(!preg_match($allow, $_POST[$param]))) {
								$problem_e .= "<p>".__('Error: the submitted string does not match the constrains', 'SL_framework')." (".$allow.")!</p>\n" ; 
								$error = true ; 
							} else {
								$this->obj->set_param($param, stripslashes($tmp)) ; 
								$modified = true ; 
							}
							$_POST[$param] = $tmp  ; 
						} 
						
						// Is it a list ?
						if ($type=="list") {
							$selected = $_POST[$param] ; 
							$array = $this->obj->get_param($param) ; 
							$mod = false ; 
							for ($i=0 ; $i<count($array) ; $i++) {
								// if the array is a simple array of string
								if (!is_array($array[$i])) {
									$tmpa = $array[$i] ; 
									$array[$i] = str_replace("*","",$array[$i]) ;
									// On met une etoile si c'est celui qui est selectionne par defaut
									if ($selected == Utils::create_identifier($array[$i])) {
										$array[$i] = '*'.$array[$i] ; 
									}
									if ($tmpa != $array[$i]) {
										$mod = true ; 
									}
								} else {
									$tmpa = $array[$i][0] ; // The first is the title
									$array[$i][0] = str_replace("*","",$array[$i][0]) ;
									// On met une etoile si c'est celui qui est selectionne par defaut
									if ($selected == $array[$i][1]) { // The second is the identifier
										$array[$i][0] = '*'.$array[$i][0] ; 
									}
									if ($tmpa != $array[$i][0]) {
										$mod = true ; 
									}
								}
							}
							if ($mod) {
								$this->obj->set_param($param, $array) ; 
								$modified = true ; 
							}
						} 
						
						// Is it a file ?
						if ($type=="file") {
							// deleted ?
							$upload_dir = wp_upload_dir();
							$deleted = $_POST["delete_".$param] ; 
							if ($deleted=="1") {
								if (file_exists($upload_dir["basedir"].$this->obj->get_param($param))){
									@unlink($upload_dir["basedir"].$this->obj->get_param($param)) ; 
								}
								$this->obj->set_param($param, $this->obj->get_default_option($param)) ; 
								$modified = true ; 
							}
							
							$tmp = $_FILES[$param]['tmp_name'] ; 
							if ($tmp != "") {
								if ($_FILES[$param]["error"] > 0) {
									$problem_e .= "<p>".__('Error: the submitted file can not be uploaded!', 'SL_framework')."</p>\n" ; 
									$error = true ; 
								} else {
									if (is_uploaded_file($_FILES[$param]['tmp_name'])) {
										$upload_dir = wp_upload_dir();
										$path = $upload_dir["basedir"].str_replace("[file]","", $this->obj->get_default_option($param)) ; 
										@mkdir($path, 0777, true) ; 
										if (file_exists($path . $_FILES[$param]["name"])){
											$problem_e .= "<p>".sprintf(__('Error: %s file already exists', 'SL_framework'), "<em>".$_FILES[$param]["name"]."</em>")."</p>\n" ; 
											$error = true ; 
										} else {
											move_uploaded_file($_FILES[$param]["tmp_name"], $path . $_FILES[$param]["name"]);
											$this->obj->set_param($param, str_replace("[file]","", $this->obj->get_default_option($param).  $_FILES[$param]["name"])) ; 
											$modified = true ; 
										}
									}
								}
							}
						} 
					}
					
					// We built a new line for the table
					//---------------------------------------
					if ($type=="boolean") {
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>" ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$checked = "" ; 
						if ($this->obj->get_param($param)) { 
							$checked = "checked" ;  
						}
						if (count($related)>0) { 
							$onClick = "onClick='activateDeactivate_Params(\"".$param."\",new Array(\"".implode("\",\"", $related)."\"))'" ;  
							$toExecuteWhenLoaded .= "activateDeactivate_Params(\"".$param."\",new Array(\"".implode("\",\"", $related)."\"));" ; 
						} else {
							$onClick = "" ; 
						}
						$cel_value = new adminCell("<p class='paramLine'><input ".$onClick." name='".$param."' id='".$param."' type='checkbox' ".$checked." ></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 
					}
					
					if ($type=="int") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><input name='".$param."' id='".$param."' type='text' value='".$this->obj->get_param($param)."' size='".min(30,max(6,(strlen($this->obj->get_param($param).'')+1)))."'> ".__('(integer)', 'SL_framework')."</p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 
					}
					
					if ($type=="string") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><input name='".$param."' id='".$param."' type='text' value='".htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8")."' size='".min(30,max(6,(strlen($this->obj->get_param($param).'')+1)))."'></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
					
					if ($type=="password") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><input name='".$param."' id='".$param."' type='password' value='".htmlentities($this->obj->get_param($param), ENT_QUOTES, "UTF-8")."' size='".min(30,max(6,(strlen($this->obj->get_param($param).'')+1)))."'></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}					
					if ($type=="text") {
						$num = count(explode("\n", $this->obj->get_param($param))) + 1 ; 
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cel_value = new adminCell("<p class='paramLine'><textarea name='".$param."' id='".$param."' rows='".$num."' cols='70'>".htmlentities(str_replace("*","",$this->obj->get_param($param)), ENT_QUOTES, "UTF-8")."</textarea></p>") ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
					
					if ($type=="list") {
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>" ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cc = "" ; 
						ob_start() ; 
						?>
							<p class='paramLine'>
							<select name='<?php echo $param ; ?>' id='<?php echo $param ; ?>'>
							<?php 
							$array = $this->obj->get_param($param);
							foreach ($array as $a) {
								if (!is_array($a)) {
									$selected = "" ; 
									if (str_replace("*", "", $a) != $a) {
										$selected = "selected" ; 
									}
									?>
										<option value="<?php echo Utils::create_identifier($a) ; ?>" <?php echo $selected ; ?>><?php echo str_replace("*", "", $a) ; ?></option>
									<?php
								} else {
									$selected = "" ; 
									if (str_replace("*", "", $a[0]) != $a[0]) {
										$selected = "selected" ; 
									}
									?>
										<option value="<?php echo $a[1] ; ?>" <?php echo $selected ; ?>><?php echo str_replace("*", "", $a[0]) ; ?></option>
									<?php
								}
							}
							?>
							</select>
							</p>
						<?php
						$cc = ob_get_clean() ; 
						$cel_value = new adminCell($cc) ; 
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
					
					if ($type=="file") {
						$ew = "" ; 
						if ($problem_e!="") {	
							$ew .= "<div class='errorSedLex'>".$problem_e."</div>" ; 
						}
						if ($problem_w!="") {	
							$ew .= "<div class='warningSedLex'>".$problem_w."</div>" ; 
						}
						$cl = "<p class='paramLine'><label for='".$param."'>".$name."</label></p>".$ew ; 
						// We check if there is a comment just after it
						while (isset($this->buffer[$iii+1])) {
							if ($this->buffer[$iii+1][0]!="comment") break ; 
							$cl .= "<p class='paramComment' style='color: #a4a4a4;'>".$this->buffer[$iii+1][1]."</p>" ; 
							$iii++ ; 
						}
						$cel_label = new adminCell($cl) ; 
						$cc = "" ; 
						ob_start() ; 
							$upload_dir = wp_upload_dir();
							if (!file_exists($upload_dir["basedir"].$this->obj->get_param($param))) {
								$this->obj->set_param($param,$this->obj->get_default_option($param)) ; 
							}
							if ($this->obj->get_default_option($param)==$this->obj->get_param($param)) {
								?>
								<p class='paramLine'><input type='file' name='<?php echo $param ; ?>' id='<?php echo $param ; ?>'/></p>
								<?php 
							} else {
								$path = $upload_dir["baseurl"].$this->obj->get_param($param) ; 
								$pathdir = $upload_dir["basedir"].$this->obj->get_param($param) ; 
								$info = pathinfo($pathdir) ; 
								if ((strtolower($info['extension'])=="png") || (strtolower($info['extension'])=="gif") || (strtolower($info['extension'])=="jpg") ||(strtolower($info['extension'])=="bmp")) {
									list($width, $height) =  getimagesize($pathdir) ; 
									$max_width = 100;
									$max_height = 100; 
									$ratioh = $max_height/$height;
									$ratiow = $max_width/$width;
									$ratio = min($ratioh, $ratiow);
									// New dimensions
									$width = min(intval($ratio*$width), $width);
									$height = min(intval($ratio*$height), $height);  
									?>
									<p class='paramLine'><img src='<?php echo $path; ?>' width="<?php echo $width?>px" height="<?php echo $height?>px" style="vertical-align:middle;"/> <a href="<?php echo $path ; ?>"><?php echo $this->obj->get_param($param) ; ?></a></p>
									<?php 								
								} else {
									?>
									<p class='paramLine'><img src='<?php echo WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__))."img/file.png" ; ?>' width="75px" style="vertical-align:middle;"/> <a href="<?php echo $path ; ?>"><?php echo $this->obj->get_param($param) ; ?></a></p>
									<?php 
								}
								?>
								<p class='paramLine'><?php echo sprintf(__("(If you want to delete this file, please check this box %s)", "SL_framework"), "<input type='checkbox'  name='delete_".$param."' value='1' id='delete_".$param."'>") ; ?></p>
								<?php 
							}
						$cc = ob_get_clean() ; 
						$cel_value = new adminCell($cc) ; 						
						$currentTable->add_line(array($cel_label, $cel_value), '1') ; 			
					}
				}
				// End is it a param?
			}
			
			// We finish the form output
			ob_start();
			if ($hastobeclosed) {
				// We close the table
				echo $currentTable->flush() ; 
			}
			
			?>
					<div class="submit">
						<input type="submit" name="submitOptions" class='button-primary validButton' value="<?php echo __('Update', 'SL_framework') ?>" />
					</div>
				</form>
			</div>
			<script><?php echo $toExecuteWhenLoaded ;  ?></script>
			<?php

			// If the parameter have been modified, we say it !
			
			if (($error) && ($maj)) {
				?>
				<div class="error fade">
					<p><?php echo __('Some parameters have not been updated due to errors (see below)!', 'SL_framework') ?></p>
				</div>
				<?php
			} else if (($warning) && ($maj)) {
				?>
				<div class="updated  fade">
					<p><?php echo __('Parameters have been updated (but with some warnings)!', 'SL_framework') ?></p>
				</div>
				<?php
			} else if (($modified) && ($maj)) {
				?>
				<div class="updated  fade">
					<p><?php echo __('Parameters have been updated successfully!', 'SL_framework') ?></p>
				</div>
				<?php
			} 
			
			$this->output .= ob_get_contents();
			ob_end_clean();
			echo $this->output ; 
		}
	}
}

?>