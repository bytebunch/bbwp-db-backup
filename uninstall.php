<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if(!function_exists("bbwp_delete_directory")){
	function bbwp_delete_directory($dirname) {
		if (is_dir($dirname))
		  $dir_handle = opendir($dirname);
		if (!$dir_handle)
			return false;
		while($file = readdir($dir_handle)) {
			if ($file != "." && $file != "..") {
				if (!is_dir($dirname."/".$file))
						unlink($dirname."/".$file);
				else
				bbwp_delete_directory($dirname.'/'.$file);
			}
		}
		closedir($dir_handle);
		rmdir($dirname);
		return true;
	}
}



$prefix = 'bbwp_db_backup';


$upload = wp_upload_dir();
$upload_dir = $upload['basedir'];
$upload_dir = $upload_dir . '/' .$prefix;
bbwp_delete_directory($upload_dir);

delete_option($prefix.'_options');