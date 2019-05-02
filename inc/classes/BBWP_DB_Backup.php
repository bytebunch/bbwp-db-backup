<?php
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
