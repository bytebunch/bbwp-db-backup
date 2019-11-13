<?php
namespace ByteBunch\BBWPDBBackup;

use DateTime;

// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBWP_DB_Backup{

  public $prefix = 'bbwp_db_backup';
  static $options = array();


	/******************************************/
	/***** class constructor **********/
	/******************************************/
  public function __construct(){

		// get the plugin options/settings.
		self::$options = SerializeStringToArray(get_option($this->prefix.'_options'));
		
		
    
    if(is_admin()){

      // add javascript and css to wp-admin dashboard.
      add_action( 'admin_enqueue_scripts', array($this, 'wp_admin_style_scripts') );

      //localization hook
      add_action( 'plugins_loaded', array($this, 'plugins_loaded') );

      //add settings page link to plugin activation page.
      add_filter( 'plugin_action_links_'.BBWP_DB_BACKUP_PLUGIN_FILE, array($this, 'plugin_action_links') );

      // Plugin activation hook
      register_activation_hook(BBWP_DB_BACKUP_PLUGIN_FILE, array($this, 'PluginActivation'));

      // plugin deactivation hook
      register_deactivation_hook(BBWP_DB_BACKUP_PLUGIN_FILE, array($this, 'PluginDeactivation'));


      //create new directory in uploads folder for new backups.
      $upload = wp_upload_dir();
      $upload_dir = $upload['basedir'];
      $upload_dir = $upload_dir . '/' .$this->prefix;
      if (! is_dir($upload_dir)) {
          mkdir( $upload_dir);
      }
      $this->set_option('upload_dir', $upload_dir);
      
		}
		
		// add weekly schedule to wp cron
		add_filter( 'cron_schedules', array($this , 'AddWeeklyCron' ));

		//Weekly schedule hook
		add_action($this->prefix.'_weekly_event', array($this , 'DoThisWeekly'));

		//Dropbox upload event
		add_action($this->prefix.'_dropbox_upload', array($this , 'dropboxUpload'));

  }// construct function end here


	/******************************************/
	/***** get plugin prefix with custom string **********/
	/******************************************/
  public function prefix($string = '', $underscore = "_"){

    return $this->prefix.$underscore.$string;

  }// prefix function end here.


	/******************************************/
	/***** localization function **********/
	/******************************************/
	public function plugins_loaded(){

		load_plugin_textdomain( 'bbwp-db-backup', false, BBWP_DB_BACKUP_ABS . 'languages/' );

	}// plugin_loaded


	/******************************************/
	/***** add settings page link in plugin activation screen.**********/
	/******************************************/
  public function plugin_action_links( $links ) {

     $links[] = '<a href="'. esc_url(get_admin_url(null, 'tools.php?page='.$this->prefix)) .'">Settings</a>';
     return $links;

  }// localization function


	/******************************************/
  /***** Plugin activation function **********/
  /******************************************/
  public function PluginActivation() {

    $ver = "0.1";
    if(!(isset(self::$options['ver']) && self::$options['ver'] == $ver))
      $this->set_option('ver', $ver);

    //create new directory in uploads folder for new backups.
    $upload = wp_upload_dir();
    $upload_dir = $upload['basedir'];
    $upload_dir = $upload_dir . '/' .$this->prefix;
    if (! is_dir($upload_dir)) {
        mkdir( $upload_dir);
    }
    $this->set_option('upload_dir', $upload_dir);

    $this->set_option('recurrence', 'weekly');

    //create index.php and .htaccess files
    $this->CreateFile("index.php", $upload_dir, "<?php \r\n// Silence is golden\r\n?>");
    $this->CreateFile(".htaccess", $upload_dir, "Options -Indexes\r\nDeny from all");



    $this->StartCronEvent();
    

  }// plugin activation


	/******************************************/
  /***** plugin deactivation function **********/
  /******************************************/
  public function PluginDeactivation(){

    //delete_option($this->prefix.'_options');
    wp_clear_scheduled_hook($this->prefix.'_weekly_event');

  }// plugin deactivation
  
  /******************************************/
  /***** start cron event **********/
  /******************************************/
  public function StartCronEvent(){

    $recurrence = $this->get_option('recurrence');
    if($recurrence == "oneoff" && wp_next_scheduled ( $this->prefix.'_weekly_event' )){
      wp_clear_scheduled_hook($this->prefix.'_weekly_event');
    }elseif (! wp_next_scheduled ( $this->prefix.'_weekly_event' )) {
      wp_schedule_event(time()+1, $recurrence, $this->prefix.'_weekly_event');
    }elseif (wp_next_scheduled ( $this->prefix.'_weekly_event' )) {
      wp_clear_scheduled_hook($this->prefix.'_weekly_event');
      wp_schedule_event(time()+1, $recurrence, $this->prefix.'_weekly_event');
    }
    

	}// plugin deactivation


	/******************************************/
  /***** get option function**********/
  /******************************************/
  public function get_option($key){

    if(isset(self::$options[$key]))
      return self::$options[$key];
    else
      return NULL;

  }// get_option


	/******************************************/
  /***** get option function **********/
  /******************************************/
  public function set_option($key, $value){

      self::$options[$key] = $value;
      update_option($this->prefix.'_options', ArrayToSerializeString(self::$options));

	}// set_option
	

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
	/***** AddWeeklyCron **********/
	/******************************************/
  public function DoThisWeekly() {

		$fileName = $this->get_option('upload_dir').'/'.DB_NAME.'-'.generateRandomInt(4).'-backup-'.date("m-d-Y_h-i-A",time()).'.sql';
		
		$BBWPDBBackupCron = new BBWPDBBackupCron();
		$BBWPDBBackupCron->DoThisWeekly($fileName);
		
		// time() + 3600 = one hour from now.
		// time() + 300 = 5 min from now.
		wp_schedule_single_event( time() + 60, $this->prefix.'_dropbox_upload');

	}

	/******************************************/
	/***** AddWeeklyCron **********/
	/******************************************/
  public function dropboxUpload() {
		$BBWPDBBackupFileSystem = new BBWPDBBackupFileSystem();
		$backups = $BBWPDBBackupFileSystem->get_backups();
		if($backups && is_array($backups) && count($backups) >= 1){
			$authorizationToken = $this->get_option('dropbox_token');
			if($authorizationToken){
				$DropBoxClient = new DropBoxClient($authorizationToken);
				foreach($backups as $backup){
					$date = new DateTime($backup['modified']);
					$path = '/'.$date->format('Y').'/'.$date->format('m');
					$file_path = $path.'/'.$backup['raw_name'];
					$existing_files = $DropBoxClient->listFolder($path);
					$uploaded = false;
					
					if(isset($existing_files['entries']) && is_array($existing_files['entries'])){						
						foreach($existing_files['entries'] as $dfile){
							if(isset($dfile['name']) && $dfile['name'] == $backup['raw_name']){
								$uploaded = true;
								break; 
							}
						}
					}
					
					if($uploaded == true)
						wp_delete_file($backup['path']);
					else
						$DropBoxClient->upload($file_path, $backup['path']);
				}
			}
		}
	}
	
	

  /******************************************/
  /***** CreateFile function **********/
  /******************************************/
  public function CreateFile($name, $path, $data){
    $handle = fopen($path.'/'.$name, 'w');
    fwrite($handle, $data);
    fclose($handle);

  }// set_option


	/******************************************/
  /***** add javascript and css to wp-admin dashboard. **********/
  /******************************************/
  public function wp_admin_style_scripts() {

    if(isset($_GET['page']) && $_GET['page'] === $this->prefix){
     
      

      wp_register_style( $this->prefix.'_wp_admin_css', BBWP_DB_BACKUP_URL . '/css/style.css', array(), '1.0.0' );
      wp_enqueue_style($this->prefix.'_wp_admin_css');

      wp_enqueue_script( 'postbox' );
      
      wp_register_script( $this->prefix.'_wp_admin_script', BBWP_DB_BACKUP_URL . '/js/script.js', array('jquery'), '1.0.0' );
      wp_enqueue_script( $this->prefix.'_wp_admin_script' );


      //$js_variables = array('prefix' => $this->prefix."_");
      //wp_localize_script( $this->prefix.'_wp_admin_script', $this->prefix, $js_variables );

		}

  }// wp_admin_style_scripts

} // BBWP_CustomFields class
