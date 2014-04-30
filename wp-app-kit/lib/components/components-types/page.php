<?php
class WpakComponentTypePage extends WpakComponentType{
	
	protected function compute_data($component,$options,$args=array()){
		global $wpdb;
		
		if( !empty($options['page']) ){
			$page = get_post($options['page']);
			if( !empty($page) ){
				$pages = array($page->ID => self::get_page_data($page));
				$this->set_specific('id',$page->ID);
				$this->set_globals('pages',$pages);
			}
		}
	} 
	
	protected function get_page_data($page){
		global $post;
		$post = $page;
		setup_postdata($post);
	
		$post_data = array(
				'id' => $post->ID,
				'date' => strtotime($post->post_date),
				'title' => $post->post_title,
				'content' => '',
				'excerpt' => '',
				'featured-img' => '',
				'author' => '',
				'url' => get_permalink(),
				'nb_comments' => (int)get_comments_number()
		);
	
		//Use the "wpak_posts_list_post_content" filter to format app pages content your own way :
		$content = apply_filters('wpak_page_content','',$post);
		if( empty($content) ){
			$content = WpakComponentsUtils::get_formated_content();
		}
		$post_data['content'] = $content;
	
		$post_data['excerpt'] = WpakComponentsUtils::get_post_excerpt($post);
	
		$post_featured_img_id = get_post_thumbnail_id($post->ID);
		if( !empty($post_featured_img_id) ){
			$featured_img_src = wp_get_attachment_image_src($post_featured_img_id, 'mobile-featured-thumb');
			$post_data['featured_img']['src'] = $featured_img_src[0];
		}
	
		$post_data = apply_filters('wpak_page_data',$post_data,$post);
	
		return (object)$post_data;
	}
	
	protected function get_post_data($_post){
		global $post;
		$post = $_post;
		setup_postdata($post);
		
		$post_data = array(
			'id' => $post->ID,
			'date' => strtotime($post->post_date),
			'title' => $post->post_title,
			'content' => '',
			'excerpt' => '',
			'featured_img' => '',
			'author' => '',
			'url' => get_permalink(),
			'nb_comments' => (int)get_comments_number()
		);
		
		$post_data = apply_filters('wpak_page_post_data',$post_data,$post);
		
		return (object)$post_data;
	}
	
	public function get_options_to_display($component){
		$page = get_post($component->options['page']);
		$page_title = !empty($page) ? $page->post_title .' (ID='. $page->ID .')' : __('Page not found');
		$options = array(
				'page' => array('label'=>__('Page'),'value'=>$page_title),
		);
		return $options;
	}
	
	public function echo_form_fields($component){
		$pages = get_posts(array('post_type'=>'page','posts_per_page'=>-1));
		$pages = apply_filters('wpak_component_type_pages_form_pages',$pages);
		
		$current_page = '';
		if( !empty($component) ){
			$options = $component->options;
			$current_page = $options['page'];
		}
		?>
		<div>
			<label><?php _e('Page') ?> : </label>
			<select name="page_id" class="page-pages">
				<?php foreach($pages as $page): ?>
					<?php $selected = $page->ID == $current_page ? 'selected="selected"' : '' ?>
					<option value="<?php echo $page->ID ?>" <?php echo $selected ?>><?php echo $page->post_title ?></option>
				<?php endforeach ?>
			</select>
		</div>
		<?php
	}
	
	public function echo_form_javascript(){
	}
	
	public function get_ajax_action_html_answer($action,$params){
	}
	
	public function get_options_from_posted_form($data){
		$page_id = $data['page_id'];
		$options = array('page' => $page_id);
		return $options;
	}
	
}

WpakComponentsTypes::register_component_type('page', array('label'=> __('Wordpress page')));