<?php
namespace ByteBunch\BBWPDBBackup;
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBWPDBBackupCron{

	/******************************************/
	/***** class constructor **********/
	/******************************************/
  public function __construct(){

  }// construct function end here


	


	/******************************************/
	/***** DoThisWeekly **********/
	/******************************************/
  	public function DoThisWeekly( $fileName ) {
		
		//$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'_'.date("m-d-Y_h-i-A",time()).'.sql.gz';
		//$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'-'.generateRandomInt(4).'-backup-'.date("m-d-Y_h-i-A",time()).'.sql';
		//$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'-backup-'.time().'.sql.gz';
		//update_option('dummy', get_option('dummy')+1);
		$isgzip = shell_exec("gzip");
		$ismysqldump = shell_exec("mysqldump");
		if($isgzip && $ismysqldump){
			$fileName .= ".gz";		
			exec('mysqldump --user='.DB_USER.' --password='.DB_PASSWORD.' --host='.DB_HOST.' '.DB_NAME.' | gzip > '.$fileName);
		}else if($ismysqldump){
			exec('mysqldump --user='.DB_USER.' --password='.DB_PASSWORD.' --host='.DB_HOST.' '.DB_NAME.' > '.$fileName);
		}else{
			
		}

		return $fileName;

	}



}