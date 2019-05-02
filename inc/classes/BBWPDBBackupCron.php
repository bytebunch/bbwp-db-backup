<?php
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBWPDBBackupCron extends BBWP_DB_Backup{

	/******************************************/
	/***** class constructor **********/
	/******************************************/
  public function __construct(){

		// Plugin activation hook
		//register_activation_hook(BBWP_DB_BACKUP_PLUGIN_FILE, array($this, 'PluginActivation'));
		
		// add weekly schedule to wp cron
		add_filter( 'cron_schedules', array($this , 'AddWeeklyCron' ));

		//Weekly schedule hook
		add_action($this->prefix.'_weekly_event', array($this , 'DoThisWeekly'));

		//$this->DoThisWeekly();
    

  }// construct function end here


	/******************************************/
	/***** AddWeeklyCron **********/
	/******************************************/
  public function AddWeeklyCron( $schedules ) {

		// add a 'weekly' schedule to the existing set
		$schedules['weekly'] = array(
			'interval' => 60 * 60 * 24 * 7, # 604,800, seconds in a week
			//'interval' => 30,
			'display' => __('Once Weekly')
		);

		// add a 'monthly' schedule to the existing set
		$schedules['monthly'] = array(
			'interval' => 60 * 60 * 24 * 30, # 604,800, seconds in a week
			'display' => __('Once Monthly')
		);

		return $schedules;
	}


	/******************************************/
	/***** DoThisWeekly **********/
	/******************************************/
  	public function DoThisWeekly( ) {
		
		//$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'_'.date("m-d-Y_h-i-A",time()).'.sql.gz';
		$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'-backup-'.generateRandomInt(5).'-'.date("m-d-Y",time()).'.sql';
		//$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'-backup-'.time().'.sql.gz';
		
		$isgzip = shell_exec("gzip");
		$ismysqldump = shell_exec("mysqldump");
		if($isgzip && $ismysqldump){
			$fileName .= ".gz";		
			exec('mysqldump --user='.DB_USER.' --password='.DB_PASSWORD.' --host='.DB_HOST.' '.DB_NAME.' | gzip > '.$fileName);
		}else if($ismysqldump){
			exec('mysqldump --user='.DB_USER.' --password='.DB_PASSWORD.' --host='.DB_HOST.' '.DB_NAME.' > '.$fileName);
		}else{
			
		}
	}



} // BBWP_CustomFields class
