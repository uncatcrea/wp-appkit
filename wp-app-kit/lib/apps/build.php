<?php

class WpakBuild{
	
	const export_file_memory = 10;
	
	public static function hooks(){
		if( is_admin() ){
			add_action('wp_ajax_wpak_build_app_sources', array(__CLASS__,'build_app_sources') );
			add_action('admin_action_wpak_download_app_sources', array(__CLASS__,'download_app_sources') );
			add_action('add_meta_boxes', array(__CLASS__,'add_meta_boxes'),20);
			add_action('save_post', array(__CLASS__,'save_post'));
		}
	}
	
	public static function add_meta_boxes(){
		add_meta_box(
			'wpak_simulation_box',
			__('App Simulation',WpAppKit::i18n_domain),
			array(__CLASS__,'inner_simulation_box'),
			'wpak_apps',
			'side',
			'default'
		);
		
		add_meta_box(
			'wpak_export_box',
			__('Phonegap ready App export',WpAppKit::i18n_domain),
			array(__CLASS__,'inner_export_box'),
			'wpak_apps',
			'side',
			'default'
		);
	}
	
	public static function inner_simulation_box($post,$current_box){
		$debug_mode = self::get_app_debug_mode_raw($post->ID);
		$wp_ws_url = WpakWebServices::get_app_web_service_url($post->ID,'synchronization');
		$appli_url = self::get_appli_index_url($post->ID);
		?>
		<label><?php _e('Debug Mode',WpAppKit::i18n_domain) ?> : </label>
		<select name="wpak_app_debug_mode">
			<option value="on" <?php echo $debug_mode == 'on' ? 'selected="selected"' : '' ?>><?php _e('On',WpAppKit::i18n_domain) ?></option>
			<option value="off" <?php echo $debug_mode == 'off' ? 'selected="selected"' : '' ?>><?php _e('Off',WpAppKit::i18n_domain) ?></option>
			<option value="wp" <?php echo $debug_mode == 'wp' ? 'selected="selected"' : '' ?>><?php _e('Same as Wordpress WP_DEBUG',WpAppKit::i18n_domain) ?></option>
		</select>
		<br/><span class="description"><?php _e('If activated, echoes debug infos in the browser javascript console while simulating the app.',WpAppKit::i18n_domain) ?></span>
		<br/>
		<br/>
		<a href="<?php echo WpakSimulator::get_simulator_url($post->ID) ?>" class="button button-large"><?php _e('View application in simulator',WpAppKit::i18n_domain) ?></a>
		<br/>
		<br/>
		<a href="<?php echo $appli_url ?>" class="button button-large"><?php _e('View application in browser',WpAppKit::i18n_domain) ?></a>
		<br/>
		<br/>
		<a href="<?php echo self::get_appli_dir_url() .'/config.js?wpak_app_id='. WpakApps::get_app_slug($post->ID) ?>"><?php _e('View config.js',WpAppKit::i18n_domain) ?></a>
		<br/>
		<br/>
		<div style="word-wrap: break-word;">
			<label><?php _e('Web services',WpAppKit::i18n_domain) ?> :</label><br/>
			<?php _e('Synchronization',WpAppKit::i18n_domain) ?> : <a href="<?php echo $wp_ws_url ?>"><?php echo $wp_ws_url ?></a>
		</div>
		<?php wp_nonce_field('wpak-simulation-data-'. $post->ID,'wpak-nonce-simulation-data') ?>
		<?php 
	}
	
	public static function inner_export_box($post,$current_box){
		$app_id = $post->ID;
		$available_themes = WpakThemes::get_available_themes();
		$current_theme = WpakThemesStorage::get_current_theme($app_id);
		?>
		<span class="description wpak_export_infos"><?php _e('Phonegap exports are Zip files created in the WordPress uploads directory',WpAppKit::i18n_domain) ?> : <br/><strong><?php echo str_replace(ABSPATH,'',self::get_export_files_path()) ?></strong></span>
		<br/><span class="description"><?php echo sprintf(__("The %s last App exports are memorized in this directory.",WpAppKit::i18n_domain),self::export_file_memory) ?></span>
		<br/><br/>
		<label><?php _e('Themes to include in app export',WpAppKit::i18n_domain)?> : </label><br/>
		<select id="wpak_export_theme" multiple>
			<?php foreach($available_themes as $theme): ?>
				<?php $selected = $theme == $current_theme ? 'selected="selected"' : '' ?>
				<option value="<?php echo $theme ?>" <?php echo $selected ?>><?php echo ucfirst($theme)?> </option>
			<?php endforeach ?>
		</select>
		<label for="wpak_download_after_build"><?php _e('Download after export',WpAppKit::i18n_domain) ?></label> <input type="checkbox" id="wpak_download_after_build" checked="checked" />
		<a id="wpak_export_link" href="#" class="button button-primary button-large"><?php _e('Export as PhoneGap App sources',WpAppKit::i18n_domain) ?>!</a>
		<div id="wpak_export_feedback"></div>
		
		<?php $previous_exports = self::get_available_app_exports($app_id) ?>
		<?php if( !empty($previous_exports) ): ?>
			<label><?php _e('Download a previous export',WpAppKit::i18n_domain) ?> : </label>
			<select id="wpak_available_exports">
				<?php foreach( $previous_exports as $timestamp => $entry): ?>
					<option value="<?php echo str_replace('.zip','',$entry) ?>"><?php echo get_date_from_gmt(date( 'Y-m-d H:i:s', $timestamp ),'F j, Y H:i:s' ) ?></option>
				<?php endforeach ?>
			</select>
			<a id="wpak_download_existing_link" href="#" class="button button-large"><?php _e('Download',WpAppKit::i18n_domain) ?>!</a>
		<?php endif ?>
		
		<?php wp_nonce_field('wpak-export-data-'. $post->ID,'wpak-nonce-export-data') ?>
		
		<script>
			jQuery("#wpak_export_link").click(function(e) {
				e.preventDefault();
				var themes = jQuery('#wpak_export_theme').val();
				if( themes == null ){
					jQuery('#wpak_export_feedback').addClass('error').html('<?php echo addslashes(__('Please select at least one theme',WpAppKit::i18n_domain)) ?>');
				}else{
				    var data = {
						action: 'wpak_build_app_sources',
				    	app_id: '<?php echo $app_id ?>',
						nonce: '<?php echo wp_create_nonce('wpak_build_app_sources_'. $app_id) ?>',
						themes: themes
				    }
					jQuery.post(ajaxurl, data, function(response) {
						if( response.ok == 1 || response.ok == 2 ){
							var $feedback = jQuery('#wpak_export_feedback');
							var message = '<?php echo addslashes(__("Zip export created successfully.",WpAppKit::i18n_domain)) ?>';
							var download = jQuery('#wpak_download_after_build')[0].checked;
							if( download ){
								message += '<br/>' + '<?php echo addslashes(__("Download should start automatically.",WpAppKit::i18n_domain)) ?>';
							}
							$feedback.addClass('updated').html(message);
							if( response.ok == 2 ){
								$feedback.append('<br/><br/><strong><?php _e("Warning!",WpAppKit::i18n_domain) ?></strong> : '+ response.msg);
							}
							if( download ){
								window.location.href = '<?php echo add_query_arg(array('action'=>'wpak_download_app_sources'),wp_nonce_url(admin_url(),'wpak_download_app_sources')) ?>&export='+ response['export'];
							}
						}else{
							jQuery('#wpak_export_feedback').addClass('error').html(response.msg);
						}
					});
				}
			});
			jQuery("#wpak_download_existing_link").click(function(e) {
				e.preventDefault();
				var existing_export = jQuery('#wpak_available_exports').val();
				window.location.href = '<?php echo add_query_arg(array('action'=>'wpak_download_app_sources'),wp_nonce_url(admin_url(),'wpak_download_app_sources')) ?>&export='+ existing_export;
			});
		</script>
		<style>
			a#wpak_export_link,#wpak_download_existing_link{
				margin:10px 0;
			}
			div#wpak_export_feedback{
				padding:5px;
			}
			select#wpak_export_theme,#wpak_available_exports{
				width:100%;
			}
			select#wpak_export_theme{
				margin:10px;
			}
		</style>
		<?php
	}
	
	public static function save_post($post_id){
	
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return;
		}
	
		if( empty($_POST['post_type']) || $_POST['post_type'] != 'wpak_apps' ){
			return;
		}
	
		if( !current_user_can('edit_post', $post_id) ){
			return;
		}
		
		if( !check_admin_referer('wpak-simulation-data-'. $post_id, 'wpak-nonce-simulation-data')
			|| !check_admin_referer('wpak-export-data-'. $post_id, 'wpak-nonce-export-data')
		){
			return;
		}
	
		if ( isset( $_POST['wpak_app_debug_mode'] ) ) {
			update_post_meta( $post_id, '_wpak_app_debug_mode', $_POST['wpak_app_debug_mode']);
		}

	}
	
	private static function get_app_debug_mode_raw($app_id){
		$debug_mode = get_post_meta($app_id,'_wpak_app_debug_mode',true);
		return empty($debug_mode) ? 'off' : $debug_mode;
	}
	
	public static function get_app_debug_mode($app_id){
		$debug_mode = self::get_app_debug_mode_raw($app_id);
		return $debug_mode == 'wp' ? (WP_DEBUG ? 'on' : 'off') : $debug_mode;
	}
	
	public static function get_appli_dir_url(){
		return plugins_url('appli' , dirname(dirname(__FILE__)) );
	}
	
	public static function get_appli_index_url($app_id){
		return self::get_appli_dir_url() .'/index.html?wpak_app_id='. WpakApps::get_app_slug($app_id);
	}
	
	public static function download_app_sources(){
		
		if( !check_admin_referer('wpak_download_app_sources') ){
			return;
		}
		
		$export = addslashes($_GET['export']);
		$filename = $export .'.zip';
		$filename_full = self::get_export_files_path() ."/". $filename;
		
		if( file_exists($filename_full) ){
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-type: application/octet-stream");
			header("Content-Disposition: attachment; filename=\"".$filename."\"");
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: ".filesize($filename_full));
			ob_end_flush();
			@readfile($filename_full);
			exit();
		}else{
			echo sprintf(__('Error: Could not find zip export file [%s]',WpAppKit::i18n_domain),$filename_full);
			echo ' <a href="'. $_SERVER['HTTP_REFERER'] .'">'. __('Back to app edition',WpAppKit::i18n_domain) .'</a>';
			exit();
		}
	}
	
	public static function build_app_sources(){
		$answer = array('ok'=>1, 'msg'=>'');
		
		if( empty($_POST) || empty($_POST['app_id']) || !is_numeric($_POST['app_id']) ){
			$answer['ok'] = 0;
			$answer['msg'] = __('Wrong application ID',WpAppKit::i18n_domain);
			self::exit_sending_json($answer);
		}
		
		$app_id = addslashes($_POST['app_id']);
		
		if( !check_admin_referer('wpak_build_app_sources_'. $app_id,'nonce') ){
			return;
		}
		
		if( !extension_loaded('zip') ){
			$answer['ok'] = 0;
			$answer['msg'] = __('Zip PHP extension is required to run file export. See http://www.php.net/manual/fr/book.zip.php.',WpAppKit::i18n_domain);
			self::exit_sending_json($answer);
		}

		if( !self::create_export_directory_if_doesnt_exist() ){
			$export_directory = self::get_export_files_path();
			$answer['ok'] = 0;
			$answer['msg'] = sprintf(__('The export directory [%s] could not be created. Please check that you have the right permissions to create this directory.',WpAppKit::i18n_domain),$export_directory);
			self::exit_sending_json($answer);
		}
		
		$themes = !empty($_POST['themes']) && is_array($_POST['themes']) ? $_POST['themes'] : null;
		if( $themes == null ){
			$answer['ok'] = 0;
			$answer['msg'] = __('Please choose at least one theme for the export',WpAppKit::i18n_domain);
			self::exit_sending_json($answer);
		}
		
		$plugin_dir = plugin_dir_path( dirname(dirname(__FILE__)) );
		$appli_dir = $plugin_dir .'appli';
		 
		$export_filename_base = self::get_export_file_base_name($app_id);
		$export_filename = $export_filename_base .'-'. date('YmdHis');
		$export_filename_full = self::get_export_files_path() ."/". $export_filename .'.zip';
		
		$answer = self::build_zip($app_id,$appli_dir,$export_filename_full,$themes);
		
		$maintenance_answer = self::export_files_maintenance($app_id);
		if( $maintenance_answer['ok'] == 0 ){
			$answer['ok'] = $answer['ok'] == 1 ? 2 : $answer['ok'];
			$answer['msg'] .= "<br/>". $maintenance_answer['msg'];
		}
		
		$answer['export'] = $export_filename;
		
		self::exit_sending_json($answer);
	}
	
	private static function exit_sending_json($answer){
		//If something was displayed before, clean it so that our answer can
		//be valid json (and store it in an "echoed_before_json" answer key
		//so that we can warn the user about it) :
		$content_already_echoed = ob_get_contents();
		if( !empty($content_already_echoed) ){
			$answer['echoed_before_json'] = $content_already_echoed;
			ob_end_clean();
		}
		
		header('Content-type: application/json');
		echo json_encode($answer);
		exit();
	}
	
	private static function get_export_files_path(){
		return WP_CONTENT_DIR .'/uploads/wpak-export';
	}
	
	private static function get_export_file_base_name($app_id){
		return 'phonegap-export-'.  WpakApps::get_app_slug($app_id);
	}
	
	private static function create_export_directory_if_doesnt_exist(){
		$export_directory = self::get_export_files_path();
		$ok = true;
		if( !file_exists($export_directory) ){
			$ok = mkdir($export_directory,0777,true);
		}
		return $ok;
	}
	
	private static function build_zip($app_id,$source, $destination,$themes){

		$answer = array('ok'=>1, 'msg'=>'');		
		
	    if (!extension_loaded('zip') || !file_exists($source)) {
	        $answer['msg'] = sprintf(__('The Zip archive file [%s] could not be created. Please check that you have the permissions to write to this directory.',WpAppKit::i18n_domain),$destination);
	        $answer['ok'] = 0;
			return $answer;
	    }
	
	    $zip = new ZipArchive();
	    if( !$zip->open($destination, ZIPARCHIVE::CREATE) ){
			$answer['msg'] = sprintf(__('The Zip archive file [%s] could not be opened. Please check that you have the permissions to write to this directory.',WpAppKit::i18n_domain),$destination);
	        $answer['ok'] = 0;
			return $answer;
	    }
	   	
	    if( is_dir($source) === true ){

	        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
	
	        foreach($files as $file){
				$filename = str_replace($source, '', $file);
				$filename = ltrim($filename,'/\\');
				
				//Filter themes :
				if( preg_match('|themes[/\\\].+|',$filename) ){
					$theme = preg_replace('|themes[/\\\]([^/\\\]*).*|','$1',$filename);
					if( !in_array($theme, $themes) ){
						continue;
					}
				}
				
				//Filter php directory
				if( preg_match('|themes[/\\\].+?[/\\\]php|',$filename) ){
					continue;
				}
				
	            if( is_dir($file) === true ){
	                if( !$zip->addEmptyDir($filename) ){
						$answer['msg'] = sprintf(__('Could not add directory [%s] to zip archive',WpAppKit::i18n_domain),filename);
						$answer['ok'] = 0;
						return $answer;
					}
	            }elseif( is_file($file) === true ){

					if( $filename == 'index.html' ){
						
						$index_content = self::filter_index(file_get_contents($file));
						
						if( !$zip->addFromString($filename,$index_content) ){
							$answer['msg'] = sprintf(__('Could not add file [%s] to zip archive',WpAppKit::i18n_domain),filename);
							$answer['ok'] = 0;
							return $answer;
						}
						
					}else{

		                if( !$zip->addFile($file,$filename) ){
							$answer['msg'] = sprintf(__('Could not add file [%s] to zip archive',WpAppKit::i18n_domain),filename);
							$answer['ok'] = 0;
							return $answer;
						}
					}
	            }
	        }
	        
	        //Create config.js and config.xml files
	        $zip->addFromString('config.js', WpakConfigFile::get_config_js($app_id));
	        $zip->addFromString('config.xml', WpakConfigFile::get_config_xml($app_id));
	        
	    }else{
	        $answer['msg'] = sprintf(__('Zip archive source directory [%s] could not be found.',WpAppKit::i18n_domain),$source);
	        $answer['ok'] = 0;
	        return $answer;
	    }
	
	    if( !$zip->close() ){
			$answer['msg'] = __('Error during archive creation',WpAppKit::i18n_domain);
			$answer['ok'] = 0;
			return $answer;
		}
		
	    return $answer;
	}
	
	private static function export_files_maintenance($app_id){
		$answer = array('ok'=>1, 'msg'=>'');
		
		$export_directory = self::get_export_files_path();
		
		$entries = self::get_available_app_exports($app_id);
		
		if( !empty($entries) ){
			$i = 1;
			foreach($entries as $entry){
				if( $i > self::export_file_memory ){
					if( !unlink($export_directory .'/'. $entry) ){
						$answer['msg'] .= sprintf(__("Couldn't delete old export [%s]",WpAppKit::i18n_domain), $entry) ."<br/>\n";
						$answer['ok'] = 0;
					}
				}
				$i++;
			}
		}
		
		return $answer;
	}
	
	/**
	 * Retrieves app export zip files ordered by date desc. 
	 */
	private static function get_available_app_exports($app_id){
		$available_exports = array();
		
		$export_filename_base = self::get_export_file_base_name($app_id);
		$export_directory = self::get_export_files_path();
		
		if( self::create_export_directory_if_doesnt_exist() ){
			if( $handle = opendir($export_directory) ){
				while( false !== ($entry = readdir($handle)) ){
					if( strpos($entry,$export_filename_base) !== false ){
						$entry_date = preg_replace('/'.$export_filename_base.'-(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})\.zip/','$1-$2-$3 $4:$5:$6',$entry);
						$available_exports[strtotime($entry_date)] = $entry;
					}
				}
				closedir($handle);
				if( !empty($available_exports) ){
					krsort($available_exports);
				}
			}
		}

		return $available_exports;
	}
	
	private static function filter_index($index_content){
		
		//Add phonegap.js script :
		$index_content = str_replace('<head>', "<head>\r\n\t\t<script src=\"phonegap.js\"></script>\r\n\t\t", $index_content);
		
		//Remove script used only for app simulation in web browser :
		$index_content = preg_replace('/<script[^>]*>[^<]*var query[^<]*<\/script>\s*<script/is','<script',$index_content);
		
		return $index_content;
	}
}

WpakBuild::hooks();