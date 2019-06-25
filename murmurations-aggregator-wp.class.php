<?php

/* Environment-specific functions for the aggregator */

class Murmurations_Aggregator_WP{

  /* Functions called from core class */

  /* Saves a node as WP post */

  public function save_node($node_data){

    llog($node_data,"Saving node data");

    // This shouldn't be here at all...
    $node_data = json_decode($node_data,true);

    $post_data = array();

    $post_data['post_title'] = $node_data['name'];
    $post_data['post_content'] = $node_data['name'];
    $post_data['post_excerpt'] = $node_data['tagline'];
    $post_data['post_type'] = 'murmurations_node';
    $post_data['post_status'] = 'publish';

    // Check if node exists. If yes, update using existing post ID
    $existing_post = $this->load_node($node_data['url']);

    if($existing_post){
      $post_data['ID'] = $existing_post->ID;
    }

    llog($post_data, "Post data");

    // Insert the post
    $result = wp_insert_post($post_data,true);

    if($result === false){
      llog($result,"Failed to insert post");
    }else{
      llog($result,"Inserted post");
      $result === true ? $id = $post_data['ID'] : $id = $result;
    }

    // And use the ID to update meta
    update_post_meta($id,'murmurations_node_url',$node_data['url']);
    update_post_meta($id,'murmurations_node_data',$node_data);

  }

  /* Load a murmurations_node post from WP */

  public function load_node($url){
    llog($url,"retrieving posts with URL");

    $args = array(
      'post_type' => 'murmurations_node',
       'meta_query' => array(
           array(
               'key' => 'murmurations_node_url',
               'value' => $url,
               'compare' => '=',
           )
        )
    );

    $posts = get_posts( $args );

    llog($posts,"Posts found in load_posts");

    if(count($posts) > 0){
      llog("Posts found");
      return $posts[0];
    }else{
      llog("No posts found");
      return false;
    }

  }

  /* Load multiple nodes from DB */
  public function load_nodes($limit = 1000){
    $args = array(
      'post_type'      => 'murmurations_node',
      'posts_per_page' => $limit,
    );

    $result = get_posts( $args );

    foreach ($result as $key => $post) {
      $result[$key]->murmurations = get_post_meta( $post->ID, 'murmurations_node_data', true );
    }

    return $result;
  }

  /* Save a setting to the WP options table */
  public function load_setting($setting){
    return get_option('murmurations_'.$setting);
  }

  /* Retrieve a setting from the WP options table */
  public function save_setting($setting,$value){
    return update_option('murmurations_'.$setting,$value);
  }

  /* Activate the plugin */
  public function activate(){
    // For now, we'll stuff a bunch of defaults in here...
    $default_settings = array(
      'node_cron_interval' => 'week',
      'feed_cron_interval' => 'day',
      'index_url' => 'http://localhost/projects/murmurations/murmurations-index.php',
      'filters' => array(
        array('nodeTypes','includes','Co-op'),
        //array('location','isInCountry','UK')
      ),
      'template' => 'default'
    );

    foreach ($default_settings as $key => $value) {
      $this->save_setting($key,$value);
    }

  }

  /* Deactivate the plugin */
  public function deactivate(){
    //
  }

  /* Generate the HTML for directory output */

  public function format_nodes($nodes){
    llog($nodes,"Nodes");
    //return "<pre>".print_r($nodes_info,true)."</pre>";
    $html = '<div id="murmurations-directory">';
    foreach ($nodes as $key => $node) {
      $html .= $this->format_node($node);
    }
    $html .= "</div>";
    echo $html;
  }

  public function format_node($node, $template = 'default'){

    $org_types_array = explode(', ',$node->murmurations['nodeTypes']);

    $data_classes = 'org-type-'.join(' org-type-',$org_types_array);

    if(file_exists(get_stylesheet_directory().'/murmurations-aggregator-templates/'.$template.'.php')){
      ob_start();
      include get_stylesheet_directory().'/murmurations-aggregator-templates/'.$template.'.php';
      $html = ob_get_clean();
    }else if(file_exists(dirname( __FILE__ ).'/templates/'.$template.'.php')){
      ob_start();
      include dirname( __FILE__ ).'/templates/' . $template . '.php';
      $html = ob_get_clean();
    }else{
      exit("Missing template file");
    }

    return $html;
  }
}
?>