<?php
/*
Plugin Name: BBWP DB Backup
Plugin URI: https://bytebunch.com/
Description: Wordpress Database Backup plugin with Mysql dump support.
Author: ByteBunch
Version: 0.1
Stable tag:        0.1
Requires at least: 5.1
Tested up to: 5.2.4
Author URI: https://bytebunch.com
Text Domain:       bbwp-db-backup
Domain Path:       /languages
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.txt

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version
2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
with this program. If not, visit: https://www.gnu.org/licenses/

*/

use ByteBunch\BBWPDBBackup\BBWP_DB_Backup as BBWP_DB_Backup;

// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



// constant for plugin directory path
define('BBWP_DB_BACKUP_URL', plugin_dir_url(__FILE__));
define('BBWP_DB_BACKUP_ABS', plugin_dir_path( __FILE__ ));
define('BBWP_DB_BACKUP_PLUGIN_FILE', plugin_basename(__FILE__));

// include the generic functions file.
include_once BBWP_DB_BACKUP_ABS.'inc/functions.php';
include_once BBWP_DB_BACKUP_ABS.'inc/autoload.php';

$BBWP_DB_Backup = new BBWP_DB_Backup();

if(is_admin()){

	// add the data sanitization and validation class
	if(!class_exists('BBWPSanitization'))
		include_once BBWP_DB_BACKUP_ABS.'inc/classes/BBWPSanitization.php';

	// Setting page for Meta Boxes, Field  and custom admin pages.
	if(!class_exists('BBWPDBBackupPageSettings')){
		include_once BBWP_DB_BACKUP_ABS.'inc/classes/BBWPDBBackupPageSettings.php';
		$BBWPDBBackupPageSettings = new BBWPDBBackupPageSettings();
	}

}// if is_admin_panel
