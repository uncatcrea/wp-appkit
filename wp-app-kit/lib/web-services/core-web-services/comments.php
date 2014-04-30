<?php

class WpakWebServiceComments{

	public static function hooks(){
		add_filter('wpak_read_one_comments-post',array(__CLASS__,'read_one'),10,3);
	}
	
	public function read_one($service_answer,$id,$app_id){
		$service_answer = array();
		
		$post_id = $id;
		
		$max = 50;
		
		$query_args = array(
			'post_id' => $post_id,
			'status' => 'approve',
			'number' => $max,
		);
		
		$comments = get_comments($query_args);
		$comment_tree = self::get_comments_tree($comments);
		
		foreach($comment_tree as $comment_node){
		
			$data = self::get_comment_web_service_data($comment_node);

			if( !empty($data) ){
				$service_answer[] = $data;
			}
		}
		
		return $service_answer;
	}
	
	private static function get_comment_web_service_data($comment_node){
		$comment_data = array();
	
		$id = $comment_node['id'];
		$depth = $comment_node['depth'];
		$comment = $comment_node['comment'];
	
		$comment_data_raw = get_comment($id,ARRAY_A);
		foreach($comment_data_raw as $k=>$v){
			$k = strtolower(str_replace('comment_','',$k));
			$comment_data[$k] = $v;
		}
	
		$comment_data['depth'] = $depth;
		
		$comment_data['date'] = strtotime($comment_data['date']);
	
		$post_id = $comment_data['post_id'];
		
		$to_remove = array('karma','approved','agent','type','date_gmt','author_ip',
				 		   'author_email','author_url','parent','user_id','post_id');
		foreach($to_remove as $field){
			unset($comment_data[$field]);
		}
		
		$comment_data = apply_filters('wpak_comments_data',$comment_data,$comment_data_raw,$post_id);
		
		return $comment_data;
	}
	
	private static function get_comments_tree($comments){
	
		$tree = array();
	
		if( !empty($comments) ){
			
			$comments_by_id = array();
			foreach($comments as $comment){
				$comments_by_id[$comment->comment_ID] = $comment;
			}
				
			ob_start();
			$wp_list_comments_args = array();
			wp_list_comments(apply_filters('wpak_comments_list_args',$wp_list_comments_args),$comments);
			$comments_list = ob_get_contents();
			ob_end_clean();
			
			//TODO : find another way to retrieve depths and ids than parsing the html (which can change!!!)
			
			$depths_found = preg_match_all('/depth-(\d+)/',$comments_list,$matches_depths);
			$ids_found = preg_match_all('/id="comment-(\d+)"/',$comments_list,$matches_ids);
			
			if( !empty($depths_found) && !empty($ids_found) ){
				foreach($matches_depths[1] as $k=>$depth){
					$comment_id = $matches_ids[1][$k];
					$tree[$comment_id] = array(
							'id'=>$comment_id,
							'depth'=>(int)$depth,
							'comment'=>$comments_by_id[$comment_id]
					);
				}
			}
		}
		
		return $tree;
	}
}

WpakWebServiceComments::hooks();