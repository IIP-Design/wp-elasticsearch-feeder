<?php
// abort if not called via Wordpress
if (! defined( 'ABSPATH' )) {
    exit;
}

if (!class_exists('ELASTICSEARCH_WP_REST_API_Controller')) {
    class ELASTICSEARCH_WP_REST_API_Controller extends WP_REST_Controller
    {

        public function __construct($post_type)
        {
            $this -> namespace = 'elasticsearch/v1';
            $this -> resource_name = $post_type;
        }

        public function register_routes()
        {
            register_rest_route($this -> namespace, '/' . $this -> resource_name, array(
            array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_items'),
            'args' => array(
            'per_page' => array(
            'validate_callback' => function ($param, $request, $key) {
                return is_numeric($param);
            }
            ),
            'page' => array(
            'validate_callback' => function ($param, $request, $key) {
                return is_numeric($param);
            }
            )
            ),
            'permission_callback' => array($this, 'get_items_permissions_check')
            ),
            ));

            register_rest_route( $this->namespace, '/' . $this -> resource_name . '/(?P<id>[\d]+)', array(
            array(
            'methods'   => WP_REST_Server::READABLE,
            'callback'  => array( $this, 'get_item' ),
            'args' => array(
            'id' => array(
            'validate_callback' => function ($param, $request, $key) {
                return is_numeric($param);
            }
            )
            ),
            'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            ));
        }

        public function get_items($request)
        {
            $ref = array(
                'posts' => 'post',
                'pages' => 'page',
                'media' => 'attachment'
            );

            $page = (int) $request -> get_param('page');
            $per_page = (int) $request -> get_param('per_page');

            if ($this -> resource_name == 'posts' || $this -> resource_name == 'pages') {
                $args['post_type'] = $ref[$this -> resource_name];
                if ($per_page) {
                    $args['posts_per_page'] = $per_page;
                } else {
                    $args['posts_per_page'] = 25;
                }
            } elseif ($this -> resource_name == 'media') {
                $args['post_type'] = $ref[$this -> resource_name];
                if ($per_page) {
                    $args['posts_per_page'] = $per_page;
                } else {
                    $args['posts_per_page'] = 25;
                }
            }

            if (is_numeric($page)) {
                if ($page == 1) {
                    $args['offset'] = 0;
                } elseif ($page > 1) {
                    $args['offset'] = ($page * $args['posts_per_page']) - $args['posts_per_page'];
                }
            }

            $posts = get_posts($args);

            if (empty( $posts )) {
                return rest_ensure_response( $data );
            }

            foreach ($posts as $post) {
                $response = $this->prepare_item_for_response( $post, $request );
                $data[] = $this->prepare_response_for_collection( $response );
            }

            // Return all of our comment response data.
            return rest_ensure_response( $data );
        }

        public function get_item($request)
        {
            $id = (int) $request['id'];

            // probably should refactor
            $post = $this -> resource_name === 'pages' ? get_page( $id ) : get_post( $id );

            if (empty( $post )) {
                return rest_ensure_response( array() );
            }

            $response = $this -> prepare_item_for_response( $post, $request);
            // // Return all of our post response data.
            return $response;
        }

        public function prepare_response_for_collection($response)
        {
            if (! ( $response instanceof WP_REST_Response )) {
                return $response;
            }

            $data = (array) $response->get_data();
            $server = rest_get_server();

            if (method_exists( $server, 'get_compact_response_links' )) {
                $links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
            } else {
                $links = call_user_func( array( $server, 'get_response_links' ), $response );
            }

            if (! empty( $links )) {
                $data['_links'] = $links;
            }

            return $data;
        }

        public function prepare_item_for_response($post, $request)
        {
            $post_data = array();

            // if atachment return right away
            if ($post->post_type == 'attachment') {
                $post_data = wp_prepare_attachment_for_js( $post->ID );
                return rest_ensure_response( $post_data );
            }

            // We are also renaming the fields to more understandable names.
            if (isset( $post -> ID )) {
                $post_data['id'] = (int) $post -> ID;
            }

            $post_data['post_type'] = get_post_type($post -> ID);

            if (isset($post -> post_date)) {
                $post_data['@timestamp'] = get_the_date('c', $post -> ID);
            }

            if (isset($post -> post_modified)) {
                $post_data['post_modified'] = get_the_modified_date('c', $post -> ID);
            }

            if (isset( $post -> post_author)) {
                $post_data['author'] = $this -> get_author($post -> post_author);
            }

            // pre-approved
            $post_data['link'] = get_permalink($post -> ID);

            if (isset($post -> post_title)) {
                $post_data['title'] = $post -> post_title;
            }

            if (isset($post -> post_name)) {
                $post_data['title_slug'] = $post -> post_name;
            }

            if (isset( $post -> post_content )) {
                $post_data['content'] = apply_filters( 'the_content', $post -> post_content, $post );
            }

            if (isset( $post -> post_excerpt )) {
                $post_data['excerpt'] = $post -> post_excerpt;
            }

            // pre-approved
            $post_data['categories'] = $this -> get_categories($post -> ID);
            $post_data['tags'] = $this -> get_tags($post -> ID);
            $post_data['language'] = $this -> get_language($post -> ID);

            $feature_image_exists = has_post_thumbnail($post -> ID);
            if ($feature_image_exists) {
                $post_data['featured_image'] = $this -> get_featured_image(get_post_thumbnail_id($post -> ID));
            }

            if (isset( $post -> comment_count)) {
                $post_data['comment_count'] = (int) $post -> comment_count;
            }

            return rest_ensure_response( $post_data );
        }

        protected function get_featured_image($id)
        {
            $image = wp_prepare_attachment_for_js( $id );
            $sizes = $image['sizes'];
            $sizeArray = array();
            $srcArray = array();
            if (!empty($sizes)) {
                foreach ($sizes as $size) {
                    if ($size['width'] <= 770) {
                        if (empty($srcArray) || $srcArray['width'] < $size['width']) {
                            $srcArray = array(
                            "width"=>$size['width'],
                            "height"=>$size['height'],
                            "src"=>$size['url']
                            );
                        }
                    }
                    $sizeArray[] = array(
                    "width"=>$size['width'],
                    "height"=>$size['height'],
                    "src"=>$size['url']
                    );
                }
            }
            $data = array(
            "id"=>$image['id'],
            "src"=>$srcArray['src'],
            "width"=>$srcArray['width'],
            "height"=>$srcArray['height'],
            "title"=>$image['title'],
            "alt"=>$image['alt'],
            "caption"=>$image['caption'],
            "srcset"=>$sizeArray
            );
            return $data;
        }

        protected function get_language($id)
        {
            global $sitepress;
            if ($sitepress) {
                return apply_filters( 'wpml_post_language_details', null, $id );
            } else {
                return array(
                'locale' => get_bloginfo('language')
                );
            }
        }

        protected function get_categories($id)
        {
            $categories = wp_get_post_categories( $id, array('fields' => 'all') );
            $catArray = array(
                'id' => array(),
                'slug' => array(),
                'title' => array()
            );

            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $catArray['id'][] = (int) $category -> term_id;
                    $catArray['slug'][] = $category -> slug;
                    $catArray['name'][] = $category -> name;
                }
            }
            return $catArray;
        }

        protected function get_tags($id)
        {
            $tags = wp_get_post_tags( $id );
            $tagArray = array(
                'id' => array(),
                'slug' => array(),
                'title' => array()
            );
            if (!empty($tags)) {
                foreach ($tags as $tag) {
                    $tagArray['id'][] = $tag -> term_id;
                    $tagArray['slug'][] = $tag -> slug;
                    $tagArray['name'][] = $tag -> name;
                }
            }
            return $tagArray;
        }

        protected function get_author($id)
        {
            $data = array(
            'id' => (int) $id,
            'name' => get_the_author_meta('nicename', $id)
            );
            return $data;
        }

        public function get_items_permissions_check($request)
        {
            return true;
        }

        public function get_item_permissions_check($request)
        {
            return true;
        }

        public function authorization_status_code()
        {
            $status = 401;
            if (is_user_logged_in()) {
                $status = 403;
            }
            return $status;
        }
    }
}

function prefix_register_my_rest_routes()
{
    $post_types = get_post_types(array('show_in_rest' => true));
    $defaults = array(
        'post' => 'posts',
        'page' => 'pages',
        'attachment' => 'media'
    );

    if (is_array($post_types) && count($post_types) > 0) {
        foreach ($post_types as $type) {
            if ($defaults[$type]) {
                $controller = new ELASTICSEARCH_WP_REST_API_Controller($defaults[$type]);
                $controller -> register_routes();
            }
        }
    }
}

add_action( 'rest_api_init', 'prefix_register_my_rest_routes' );
