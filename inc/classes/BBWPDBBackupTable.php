<?php
namespace ByteBunch\BBWPDBBackup;

class BBWPDBBackupTable{

    public function __construct(){

        db($this->process_table());

    }// construct end here
    
    /**
	 * Returns an array of table names with associated size in kilobytes.
	 *
	 * @return mixed
	 *
	 * NOTE: Returned array may have been altered by wpmdb_table_sizes filter.
	 */
	function get_table_sizes() {
		global $wpdb;

		static $return;

		if ( ! empty( $return ) ) {
			return $return;
		}

		$return = array();

		$sql = $wpdb->prepare(
			"SELECT TABLE_NAME AS 'table',
			ROUND( ( data_length + index_length ) / 1024, 0 ) AS 'size'
			FROM INFORMATION_SCHEMA.TABLES
			WHERE table_schema = %s
			AND table_type = %s
			ORDER BY TABLE_NAME",
			DB_NAME,
			'BASE TABLE'
		);

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$return[ $result['table'] ] = $result['size'];
			}
		}
		return $return;
	}
	

		/**
	 * Returns an array of table names with their associated row counts.
	 *
	 * @return array
	 */
	function get_table_row_count() {
		global $wpdb;

		$sql     = $wpdb->prepare( 'SELECT table_name, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s ORDER BY table_name', DB_NAME );
		$results = $wpdb->get_results( $sql, ARRAY_A );

		$return = array();

		foreach ( $results as $result ) {
			$return[ $result['table_name'] ] = ( $result['TABLE_ROWS'] == 0 ? 1 : $result['TABLE_ROWS'] );
		}

		return $return;
	}


	/**
	 * Loops over data in the provided table to perform a migration.
	 *
	 * @param string $table
	 *
	 * @return mixed
	 */
	function process_table( $table, $fp = null ) {
		global $wpdb;

		$state_data = $state_data = $this->migration_state_manager->set_post_data();

		// Setup form data
		$this->form_data->setup_form_data();
		$form_data = $this->form_data->getFormData();

		$temp_prefix       = ( isset( $state_data['temp_prefix'] ) ? $state_data['temp_prefix'] : $this->props->temp_prefix );
		$site_details      = empty( $state_data['site_details'] ) ? array() : $state_data['site_details'];
		$target_table_name = apply_filters( 'wpmdb_target_table_name', $table, $form_data['action'], $state_data['stage'], $site_details );
		$temp_table_name   = $temp_prefix . $target_table_name;
		$structure_info    = $this->get_structure_info( $table );
		$row_start         = $this->get_current_row();
		$this->row_tracker = $row_start;

		if ( ! is_array( $structure_info ) ) {
			return $structure_info;
		}

		$this->pre_process_data( $table, $target_table_name, $temp_table_name, $fp );

		do {
			// Build and run the query
			$select_sql = $this->build_select_query( $table, $row_start, $structure_info );
			$table_data = $wpdb->get_results( $select_sql );

			if ( ! is_array( $table_data ) ) {
				continue;
			}

			$to_search  = isset( $this->dynamic_props->find_replace_pairs['replace_old'] ) ? $this->dynamic_props->find_replace_pairs['replace_old'] : '';
			$to_replace = isset( $this->dynamic_props->find_replace_pairs['replace_new'] ) ? $this->dynamic_props->find_replace_pairs['replace_new'] : '';
			$replacer   = $this->replace->register( array(
				'table'        => ( 'find_replace' === $state_data['stage'] ) ? $temp_table_name : $table,
				'search'       => $to_search,
				'replace'      => $to_replace,
				'intent'       => $state_data['intent'],
				'base_domain'  => $this->multisite->get_domain_replace(),
				'site_domain'  => $this->multisite->get_domain_current_site(),
				'wpmdb'        => $this,
				'site_details' => $site_details,
			) );

			$this->start_query_buffer( $target_table_name, $temp_table_name, $structure_info );

			// Loop over the results
			foreach ( $table_data as $row ) {
				$result = $this->process_row( $table, $replacer, $row, $structure_info, $fp );
				if ( ! is_bool( $result ) ) {
					return $result;
				}
			}

			$this->stow_query_buffer( $fp );
			$row_start += $this->rows_per_segment;

		} while ( count( $table_data ) > 0 );

		// Finalize and return.
		$this->post_process_data( $table, $target_table_name, $fp );

		return $this->transfer_chunk( $fp );
	}
	
}

//$BBWPDBBackupTable = new BBWPDBBackupTable();