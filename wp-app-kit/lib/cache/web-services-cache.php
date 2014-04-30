<?php
/**
 * This class may be used before the Wordpress API is loaded (for WP cache applications : see advanced-cache.php),
 * so this must be pure PHP -> no use of Wordpress API functions inhere. No hook either!   
 */

class WpakCache{
	
	public static function cache_web_service_result($web_service_cache_id,$web_service_result,$timestamp){
		if( self::create_cache_directory_if_doesnt_exist() ){
			$filename = self::get_cache_file_full_path($web_service_cache_id,$timestamp);
			if( $f = fopen($filename,'w') ){
				fwrite($f,$web_service_result);
				fclose($f);
			}
		}
	}
	
	public static function get_cached_web_service($web_service_cache_id,$force_reload=false,$last_update=0,$not_changed_answer_callback=null){
		
		$cached_web_service_content = '';
		
		if( !$force_reload ){
			$cached_web_service_infos = self::get_cached_web_service_infos($web_service_cache_id);
			if( !empty($cached_web_service_infos) ){
				if( !empty($last_update) ){
					$asked_last_update = (int)$last_update;
					$cached_last_update = $cached_web_service_infos['timestamp'];
					if( $asked_last_update == $cached_last_update ){
						$cached_web_service_content = $not_changed_answer_callback === null ? self::get_not_changed_answer($cached_last_update) : call_user_func($not_changed_answer_callback,$cached_last_update);
					}else{
						$cached_web_service_content = $cached_web_service_infos['content'];
					}
				}else{
					$cached_web_service_content = $cached_web_service_infos['content'];
				}
			}
		}
		
		return $cached_web_service_content;
	}
	
	/**
	 * Builds a webservice cache id from given identifiers that allow to make this cache unique.
	 * @param array $cache_identifiers Cache identifiers : ews_data, ews_id, ews_action, ews_subaction, ews_subaction_data, ews_mapping
	 * @param array $get Params added in url that can be added to the hash
	 * @param array $reserved_keys Keys in $get that must not be added to the hash
	 * @return string Cache id
	 */
	public static function build_web_service_cache_id($ws_slug,$cache_identifiers,$get=array(),$reserved_keys=array()){
		$cache_id = '';
		
		$to_hash = array();
		
		foreach($cache_identifiers as $k=>$value){
			if( !empty($value) ){
				$to_hash[$k] = $value;
			}
		}
		
		if( !empty($get) ){
			$reserved_get_keys = array_merge(array('last_update','force_reload','callback'),$reserved_keys);
			foreach($get as $k => $v){
				if( !in_array($k,$reserved_get_keys) && !empty($v) ){
					$to_hash[$k] = $v;
				}
			}
		}
		
		ksort($to_hash);
		
		$cache_id = $ws_slug .'__'. md5(implode('',$to_hash));
		
		return $cache_id;
	}
	
	public static function build_web_service_cache_id_by_slug($web_service_slug,$action,$id='',$subaction='',$subaction_data='',$get=array(),$reserved_keys=array()){
		
		$identifiers = array(
				'ews_data' => $web_service_slug,
				'ews_id' => $id,
				'ews_action' => $action,
				'ews_subaction' => $subaction,
				'ews_subaction_data' => $subaction_data
		);
		
		return self::build_web_service_cache_id($web_service_slug,$identifiers,$get,$reserved_keys);
	}
	
	/**
	 * Retrieves info from the last cached file for the given web service cache id.
	 * @param string $web_service_cache_id
	 * @return array
	 */
	public static function get_cached_web_service_infos($web_service_cache_id){
		$cached_web_service = array();
		
		$path = self::get_cache_files_path();
		if( file_exists($path) ){
			if( $handle = opendir($path) ){
				
				$prefix = $web_service_cache_id .'__';
				$found_files = array();
				
				while( false !== ($entry = readdir($handle)) ){
					if ($entry != "." && $entry != "..") {
						if( strpos($entry,$prefix) !== false ){
							$timestamp = (int)str_replace($prefix,'',$entry);
							$found_files[$timestamp] = $entry;
						}
					}
				}
				
				closedir($handle);
				
				if( !empty($found_files) ){
					krsort($found_files);
					$last_file = reset($found_files);
					$timestamp = key($found_files);
					$full_path = $path .'/'. $last_file;
					$cached_web_service = array(
							'full_path' => $full_path,
							'filename' => $last_file,
							'content' => file_get_contents($full_path),
							'timestamp' => $timestamp
					);
				}
				
			}
		}
		
		return $cached_web_service;
	}
	
	/**
	 * Deletes the cache for the given web service
	 * @param string $web_service_slug To delete all caches, set $web_service_slug = 'wpak-delete-all-caches'
	 */
	public static function delete_web_service_cache($web_service_slug_or_prefix){
		$result = array('ok'=>true,'deletes_nok'=>array(),'deletes_ok'=>array());
		
		$path = self::get_cache_files_path();
		if( !empty($web_service_slug_or_prefix) && file_exists($path) ){
			if( $handle = opendir($path) ){
				$prefix = strpos($web_service_slug_or_prefix,'__') !== false ? $web_service_slug_or_prefix : $web_service_slug_or_prefix .'__';
				$delete_all = $web_service_slug_or_prefix == 'wpak-delete-all-caches';
				while( false !== ($entry = readdir($handle)) ){
					if ($entry != "." && $entry != "..") {
						if( $delete_all ){
							if( !unlink($path .'/'. $entry) ){
								$result['deletes_nok'][] = $path .'/'. $entry;
							}else{
								$result['deletes_ok'][] = $path .'/'. $entry;
							}
						}else{
							if( strpos($entry,$prefix) !== false ){
								if( !unlink($path .'/'. $entry) ){
									$result['deletes_nok'][] = $path .'/'. $entry;
								}else{
									$result['deletes_ok'][] = $path .'/'. $entry;
								}
							}
						}
					}
				}
		
				closedir($handle);
			}
		}
		
		$result['ok'] = empty($result['deletes_nok']);
		
		return $result;
	}
	
	public static function delete_web_service_cache_before_timestamp($timestamp){
		$result = array('ok'=>true,'deletes_nok'=>array(),'deletes_ok'=>array());
		
		$path = self::get_cache_files_path();
		if( !empty($timestamp) && is_numeric($timestamp) && file_exists($path) ){
			if( $handle = opendir($path) ){
				while( false !== ($entry = readdir($handle)) ){
					if ($entry != "." && $entry != "..") {
						if( preg_match('/.*__(\d+)\.cache$/',$entry,$matches) ){
							$entry_timestamp = intval($matches[1]);
							if( $entry_timestamp < intval($timestamp) ){
								if( !unlink($path .'/'. $entry) ){
									$result['deletes_nok'][] = $path .'/'. $entry;
								}else{
									$result['deletes_ok'][] = $path .'/'. $entry;
								}
							}
						}
					}
				}
				closedir($handle);
			}
		}
		
		$result['ok'] = empty($result['deletes_nok']);
		
		return $result;
	}
	
	private static function get_cache_file_full_path($web_service_cache_id,$timestamp){
		$path = self::get_cache_files_path();
		return $path .'/'. $web_service_cache_id .'__'. $timestamp .'.cache';
	}
	
	private static function get_cache_files_path(){
		return WP_CONTENT_DIR .'/uploads/web-services-cache'; //WP_CONTENT_DIR is defined even when WP API is not loaded yet.
	}
	
	private static function create_cache_directory_if_doesnt_exist(){
		$cache_directory = self::get_cache_files_path();
		$ok = true;
		if( !file_exists($cache_directory) ){
			$ok = mkdir($cache_directory,0777,true);
		}
		return $ok;
	}
	
	private static function get_not_changed_answer($cached_last_update){
		$not_changed_answer = array('result' => (object)array('status'=>2,'message'=>''), 'last-update' => $cached_last_update );
		if( function_exists('apply_filters') ){ //We can pass here when Wordpress API is not loaded yet... 
			$not_changed_answer = apply_filters('wpak_not_changed_answer',$not_changed_answer,$cached_last_update);
		}
		return json_encode((object)$not_changed_answer);
	}
}