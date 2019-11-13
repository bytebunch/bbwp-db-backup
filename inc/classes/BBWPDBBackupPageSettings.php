<?php
use ByteBunch\BBWPDBBackup\BBWP_DB_Backup as BBWP_DB_Backup;
use ByteBunch\BBWPDBBackup\BBWPDBBackupFileSystem as BBWPDBBackupFileSystem;
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBWPDBBackupPageSettings extends BBWP_DB_Backup{

  private $edit_taxonomy_values = array();

  public function __construct(){

    add_action('init', array($this, 'input_handle'));
    add_action( 'admin_menu', array($this,'admin_menu'));
  }// construct function end here

  /******************************************/
  /***** page_bboptions_admin_menu function start from here *********/
  /******************************************/
  public function admin_menu(){

    /* add sub menu in our wordpress dashboard main menu */
    add_management_page('BBWP DB Backup', 'BBWP DB Backup', 'manage_options', $this->prefix, array($this,'add_submenu_page') );
  }

  /******************************************/
  /***** add_submenu_page_bboptions function start from here *********/
  /******************************************/
  public function add_submenu_page(){
    $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'settings';
   ?>
    <div class="wrap bytebunch_admin_page_container">
      <div id="icon-tools" class="icon32"></div>
      <h2> BBWP Database Backup </h2>
      <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo $this->prefix; ?>&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
        <a href="?page=<?php echo $this->prefix; ?>&tab=backups" class="nav-tab <?php echo $active_tab == 'backups' ? 'nav-tab-active' : ''; ?>">Backups</a>
      </h2>

      <div id="poststuff">
        <div id="postbox-container" class="postbox-container">
        <?php BBWPUpdateErrorMessage(); ?>
        
        
        
        <?php if($active_tab == 'settings'){ ?>        
        <form method="post" action="">
          <div class="meta-box-sortables ui-sortable">
            <div class="postbox ">
            <h3 class="hndle"><span>General Settings</span></h3>
            <div class="inside">
            <table class="form-table">
              <tr valign="top">
                <th scope="row"><label for="backup_schedule">Backup Schedule (Recurrence)</label></th>
                  <td>
                  <?php 
                    $schedulesCrons = array('oneoff' => 'Non-repeating', 'hourly' => 'Once Hourly (1 Hour)', 'twicedaily' => 'Twice Daily (12 Hours)', 'daily' => 'Once Daily (24 Hours)', 'weekly' => 'Once Weekly', 'monthly' => 'Once Monthly');
                    /*$schedules = wp_get_schedules();
                    foreach($schedules as $key=>$schedule){
                      $schedulesCrons[$key] = $key;
                    }*/
                    $selected_value = $this->get_option('recurrence');
                    echo '<select name="'.$this->prefix.'[recurrence]">' . ArraytoSelectList($schedulesCrons, $selected_value) .'</select'; ?>
                  </td>
							</tr>
							<tr valign="top">
								<?php $selected_value = $this->get_option('dropbox_token'); ?>
                <th scope="row"><label for="dropbox_token">Dropbox Authorization Token</label></th>
								<td><input type="text" name="<?php echo $this->prefix.'[dropbox_token]'; ?>" id="dropbox_token" value="<?php echo $selected_value; ?>"></td>
							</tr>
            </table>
            </div><!-- inside-->
            </div><!-- postbox-->
          </div><!-- meta-box-sortables-->
          <?php submit_button('Save Changes'); ?>
        </form>
        
        <?php }elseif($active_tab == 'backups'){ ?>
          <form method="post" action="">
            <?php submit_button('Backup Now', 'primary', $this->prefix("run_cron"), false); ?>
          </form>
          <p>Backups are currently saved to: <br> <?php echo $this->get_option('upload_dir'); ?></p>
          <?php 
            
            $BBWPDBBackupFileSystem = new BBWPDBBackupFileSystem();
            $backups = $BBWPDBBackupFileSystem->get_backups();
            if($backups && is_array($backups) && count($backups) >= 1){

              usort($backups, function($a, $b) {
                return $b['filemtime'] <=> $a['filemtime'];
              });

              //db($backups);
              echo '<ul class="bbwp_db_backup_list">';
                foreach($backups as $backup){
                  echo '<li>
                  <span>'.$backup['modified'].'</span>
                  <span>'.$backup['raw_name'].'</span> 
                  <span><a href="'.  esc_url(add_query_arg(array('action' => 'download', 'name' => $backup['raw_name']), get_admin_url(null, 'tools.php?page='.$this->prefix))) .'" target="_blank">Download</a> (Size: '.formatBytes($backup["size"]).' )</span> 
                  <a href="'. esc_url(add_query_arg(array('action' => 'delete', 'name' => $backup['raw_name']), get_admin_url(null, 'tools.php?page='.$this->prefix))) .'"><span class="dashicons dashicons-dismiss"></span></a>
                  </li>';
                }
              echo '<ul>';
            }
          ?>
        <?php } ?>
        
        

          
        </div><!-- postbox-container-->
      </div><!-- poststuff-->

    </div><!-- main wrap div end here -->
    <?php
  }


  /******************************************/
  /***** input_handle function start from here *********/
  /******************************************/
  public function input_handle(){
    if(isset($_GET['page']) && $_GET['page'] === $this->prefix){

      if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['name']) && $_GET['name']){
        $BBWPDBBackupFileSystem = new BBWPDBBackupFileSystem();
        $backup = BBWPSanitization::Textfield($_GET['name']);
        $BBWPDBBackupFileSystem->delete_backup($backup);
        $update_message = '<p>Backup ('.$backup.') have been deleted.</p>';
        update_option("bbwp_update_message", $update_message);
      }

      if(isset($_GET['action']) && $_GET['action'] == 'download' && isset($_GET['name']) && $_GET['name']){
        $BBWPDBBackupFileSystem = new BBWPDBBackupFileSystem();
        $backup = BBWPSanitization::Textfield($_GET['name']);
        $BBWPDBBackupFileSystem->download_backup($backup);
      }
      
      if(isset($_POST[$this->prefix]) && is_array($_POST[$this->prefix]) && count($_POST[$this->prefix]) >= 1){
        
        if(isset($_POST[$this->prefix]['recurrence'])){
          $recurrence = BBWPSanitization::Textfield($_POST[$this->prefix]['recurrence']);
          $recurrence_db = $this->get_option('recurrence');
          if($recurrence && $recurrence != $recurrence_db){ 
            $this->set_option('recurrence', $recurrence);
            $this->StartCronEvent();
            $update_message = '<p>Your setting have been updated.</p>';
            update_option("bbwp_update_message", $update_message);
          }
				}
				if(isset($_POST[$this->prefix]['dropbox_token']) && $_POST[$this->prefix]['dropbox_token']){
					$value = BBWPSanitization::Textfield($_POST[$this->prefix]['dropbox_token']);
					$this->set_option('dropbox_token', $value);
					$update_message = '<p>Your setting have been updated.</p>';
					update_option("bbwp_update_message", $update_message);
				}

      }

       
      if(isset($_POST[$this->prefix("run_cron")]) && $_POST[$this->prefix("run_cron")] === 'Backup Now'){
        wp_schedule_single_event( time() - 1, $this->prefix.'_weekly_event');
        spawn_cron();
      }

    } // if isset page end here
  } // input handle function end here

}// class end here
