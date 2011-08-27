<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class enables the creation of tabulation in the admin backend
*/
if (!class_exists("adminTabs")) {
	class adminTabs  {
		var $title ; 
		var $content ; 
		var $activated ; 
		
		/** ====================================================================================================================================================
		* Constructor of the class
		* 
		* @return adminTabs the tabs
		*/
		function adminTabs() {	
			$this->title = array() ; 
			$this->content = array() ; 
			$this->activated = 0 ; 
		}
		
		/** ====================================================================================================================================================
		* Add a tabulation
		* For instance, 
		* <code>$tabs = new adminTabs() ; <br/> ob_start() ;  <br/> echo "Content 1" ;  <br/> $tabs->add_tab("Tab1", ob_get_clean() ) ; 	 <br/> ob_start() ;  <br/> echo "Content 2" ;  <br/> $tabs->add_tab("Tab2", ob_get_clean() ) ;  <br/> echo $tabs->flush() ; </code>
		* will create to basic tabulation.
		* @param string $title the title of the tabulation
		* @param string $content the HTML content of the tab
		* @return void
		*/
		function add_tab($title, $content) {
			$this->title[] = $title ; 
			$this->content[] = $content ; 
		}
		
		/** ====================================================================================================================================================
		* Change the tabs activated by default (normally it is the first tab i.e. 1)
		* 1 is the first, 2 is the second, etc.
		* 
		* @param integer $nb the tabultaion index to activate
		* @return void
		*/
		function activate($nb) {
			$this->activated = $nb-1 ; 
		}
		
		/** ====================================================================================================================================================
		* Print the tabulation HTML code. 
		* 
		* @return void
		*/
		function flush() {
			ob_start() ; 
			$rnd = rand(1, 100000) ; 
?>
			<script>
				jQuery(function($){ 
					$tabs<?php echo $rnd ; ?> = $('#tabs<?php echo $rnd ; ?>').tabs();  
					$tabs<?php echo $rnd ; ?>.tabs('select', <?php echo ($this->activated) ?>) ; 
				}) ; 
			</script>		
			
			<div id="tabs<?php echo $rnd ; ?>">
				<ul class="hide-if-no-js">
<?php
			for ($i=0 ; $i<count($this->title) ; $i++) {
?>					<li><a href="#tab-<? echo md5($this->title[$i]) ?>"><? echo $this->title[$i] ?></a></li>		
<?php
			}
?>				</ul>
<?php
			for ($i=0 ; $i<count($this->title) ; $i++) {
?>				<div id="tab-<? echo md5($this->title[$i]) ?>" class="blc-section">
					<?php echo $this->content[$i] ; ?>
				</div>
<?php
				
			}
?>
			</div>
<?php		return ob_get_clean() ; 
		}
	}
}
?>