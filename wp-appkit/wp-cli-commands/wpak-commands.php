<?php
/**
 * WP-AppKit plugin commands
 */
class Wpak_Commands extends WP_CLI_Command {

    /**
     * Export app sources for PhoneGap CLI compilation
     * 
     * ## OPTIONS
     * 
     * <app_id_or_slug> : Application ID or Application slug
	 * 
	 * <target_directory> : Target directory where the export files will be copied
	 * 
	 * <export_type> : (Optionnal) Export type can be "phonegap-cli" (default if not provided), "phonegap-build" or "webapp"
     * 
     * ## EXAMPLES
     * 
     *     PhoneGap CLI export : wp wpak export 123 /target/directory/
	 *     PhoneGap Build export : wp wpak export 123 /target/directory/ "phonegap-build"
	 *     Webapp export : wp wpak export 123 /target/directory/ "webapp"
     *
     * @synopsis <app_id> <target_directory> [<export_type>]
	 * 
	 * @subcommand export
     */
    public function export( $args, $assoc_args ) {
		list( $app_id_or_slug, $target_directory, $export_type ) = $args;
		
		if ( empty( $export_type ) ) {
			$export_type = "phonegap-cli";
		}
		
		if ( !WpakBuild::is_allowed_export_type( $export_type ) ) {
			WP_CLI::error( 'Unknown export type "'. $export_type .'"' );
		}

		//Check that the given app exists :
		if ( WpakApps::app_exists( $app_id_or_slug ) ) {

			if ( is_dir( $target_directory ) ) {

				WP_CLI::line( 'Export app "' . $app_id_or_slug . '" to ' . $target_directory );

				$app_id = WpakApps::get_app_id( $app_id_or_slug );

				$answer = WpakBuild::build_app_sources( $app_id, $export_type );
				if ( $answer['ok'] === 1 ) {
					
					$zip_file = $answer['export_full_name'];
					
					WP_CLI::line( "App sources zipped to " . $zip_file);

					WP_CLI::line( "Extract zip to destination");
					
					//Extract to target directory :
					WP_Filesystem();
					$result = unzip_file( $zip_file, $target_directory );
					if ( !is_wp_error( $result ) ) {
						WP_CLI::success( "App sources extracted successfully to $target_directory" );
					} else {
						WP_CLI::line( 'Could not extract ZIP export to : '. $target_directory );
						WP_CLI::error( $result->get_error_message() );
					}
					
				} else {
					WP_CLI::error( 'Export error : ' . $answer['msg'] );
				}
			} else {
				WP_CLI::error( 'Destination directory not found : ' . $target_directory );
			}
		} else {
			WP_CLI::error( 'Application "' . $app_id_or_slug . '" not found' );
		}
	}

}

WP_CLI::add_command( 'wpak', 'Wpak_Commands' );