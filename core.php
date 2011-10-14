<?php
/**
* Core SedLex Plugin Bootstrap
* VersionInclude : 3.0
*/ 



if (!class_exists('pluginSedLex')) {
	$folders = scandir(WP_PLUGIN_DIR) ; 
	$url = "" ; 
	$date = 0 ; 
	foreach ($folders as $f) {
		if ($f != "." && $f != "..") {
			if (is_dir(WP_PLUGIN_DIR."/".$f)) {
				if (is_file(WP_PLUGIN_DIR."/".$f."/core.nfo")) {
					if (is_file(WP_PLUGIN_DIR."/".$f."/core.class.php")) {
						$info = explode("#",file_get_contents(WP_PLUGIN_DIR."/".$f."/core.nfo")) ; 
						// We get the max (< 0 if str1 is less than str2)
						if (strcmp($date, $info[1])<0) {
							$date = $info[1] ; 
							$url = WP_PLUGIN_DIR."/".$f."/" ; 
						}
					}
				}
			}
		}
	}

	require_once($url.'core.class.php') ; 
	require_once($url.'core/admin_table.class.php') ; 
	require_once($url.'core/tabs.class.php') ; 
	require_once($url.'core/box.class.php') ; 
	require_once($url.'core/feedback.class.php') ; 
	require_once($url.'core/otherplugins.class.php') ; 
	require_once($url.'core/parameters.class.php') ; 
	require_once($url.'core/phpdoc.class.php') ; 
	require_once($url.'core/translation.class.php') ; 
	require_once($url.'core/utils.class.php') ; 
	require_once($url.'core/zip.class.php') ; 

}

?>