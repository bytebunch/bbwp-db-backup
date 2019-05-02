<?php
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBWPDBBackupFileSystem extends BBWP_DB_Backup{

    /******************************************/
    /***** class constructor **********/
    /******************************************/
    public function __construct(){

    }// construct end here


    /******************************************/
    /***** get_backups **********/
    /******************************************/
    public function get_backups() {
        $backup_dir = $this->get_option("upload_dir")."/";
        $files      = $this->scandir( $backup_dir );
        $output     = [];
        
        foreach ( $files as $file ) {
          if ( ! preg_match( '/(.*)-backup-(.*)$/', $file['name'] ) ) {
          //if ( ! preg_match( '/(.*)-backup-\d{14}-\w{5}.sql$/', $file['name'] ) ) {
            continue;
          }
          $file_name_formatted = $this->format_backup_name( $file['name'] );
    
          // Respects WordPress core options 'timezone_string' or 'gmt_offset'
          $modified = get_date_from_gmt( date( 'Y-m-d H:i:s', $file['filemtime'] ), 'M d, Y g:i a' );
    
          $backup_info = [
            'path'         => $file['absolute_path'],
            'modified'     => $modified,
            'download_url' => WP_CONTENT_URL . DIRECTORY_SEPARATOR . $file['wp_content_path'],
            'name'         => $file_name_formatted,
            'raw_name'     => $file['name'],
            'size'         => $file['size'],
            'filemtime'    => $file['filemtime'],
          ];
    
          $output[] = $backup_info;
        }
    
        if ( empty( $output ) ) {
          return false;
        }
    
        return $output;
      }

        /******************************************/
        /***** scandir **********/
        /******************************************/
      public function scandir( $abs_path ) {

        if ( is_link( $abs_path ) ) {
          return false;
        }
    
        $dirlist = @scandir( $abs_path );
        
        
    
        $return = array();
    
        // normalize return to look somewhat like the return value for WP_Filesystem::dirlist
        foreach ( $dirlist as $entry ) {
          if ( '.' === $entry || '..' === $entry || is_link( $abs_path . $entry ) ) {
            continue;
          }
    
          $return[ $entry ] = $this->get_file_info( $entry, $abs_path );
        }
    
        return $return;
      }

        /******************************************/
        /***** get_file_info **********/
        /******************************************/
      function get_file_info( $entry, $abs_path ) {
        $abs_path  = $this->slash_one_direction( $abs_path );
        $full_path = realpath( trailingslashit( $abs_path ) . $entry );
      
        $return                    = array();
        $return['name']            = $entry;
        $return['relative_path']   = str_replace( $abs_path, '', $full_path );
        $return['wp_content_path'] = str_replace( $this->slash_one_direction( WP_CONTENT_DIR ) . DIRECTORY_SEPARATOR, '', $full_path );
        $return['subpath']         = preg_replace( '#^(themes|plugins)#', '', $return['wp_content_path'] );
        $return['absolute_path']   = $full_path;
        $return['type']            = $this->is_dir( $abs_path . DIRECTORY_SEPARATOR . $entry ) ? 'd' : 'f';
        $return['size']            = $this->filesize( $abs_path . DIRECTORY_SEPARATOR . $entry );
        $return['filemtime']       = filemtime( $abs_path . DIRECTORY_SEPARATOR . $entry );
      
        $exploded              = explode( DIRECTORY_SEPARATOR, $return['subpath'] );
        $return['folder_name'] = isset( $exploded[1] ) ? $exploded[1] : $return['relative_path'];
      
        return $return;
      }


      /**
	 * Converts file paths that include mixed slashes to use the correct type of slash for the current operating system.
	 *
	 * @param $path string
	 *
	 * @return string
	 */
	public function slash_one_direction( $path ) {
		return str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path );
    }
    
    	/**
	 * Is the specified path a directory?
	 *
	 * @param string $abs_path
	 *
	 * @return bool
	 */
	public function is_dir( $abs_path ) {
		$return = is_dir( $abs_path );
		return $return;
    }
    

    	/**
	 * Get a file's size
	 *
	 * @param string $abs_path
	 *
	 * @return int
	 */
	public function filesize( $abs_path ) {
		$return = filesize( $abs_path );
		return $return;
    }
    

    public function format_backup_name( $file_name ) {
		$new_name = preg_replace( '/-\w{5}.sql/', '.sql${1}', $file_name );

		return $new_name;
  }
  

  public function download_backup( $backup ) {
		$backup_dir = $this->get_option('upload_dir') . DIRECTORY_SEPARATOR;
		$diskfile   = $backup_dir . $backup;

		if ( ! file_exists( $diskfile ) ) {
			wp_die( __( 'Could not find backup file to download:', 'wp-migrate-db' ) . '<br>' . esc_html( $diskfile ) );
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: ' . $this->filesize( $diskfile ) );
		header( 'Content-Disposition: attachment; filename=' . $backup );
		readfile( $diskfile );
		exit;
  }
  
  public function delete_backup($path) {

		$backup_dir = $this->get_option('upload_dir') . DIRECTORY_SEPARATOR;
		$file_path  = $backup_dir . $path;

		if (file_exists( $file_path ) ) {
			$deleted = $this->unlink( $file_path );
		}

  }
  

  	/**
	 * Delete a file
	 *
	 * @param string $abs_path
	 *
	 * @return bool
	 */
	public function unlink( $abs_path ) {
		return @unlink( $abs_path );
	}


}// class end