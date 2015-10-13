<?php
// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

class Radium_Video_Metaboxes_Field_VideoSelect extends Radium_Video_Metaboxes_Field_Select {
    /**
     * Enqueue scripts and styles
     *
     * @return void
     */
    static function admin_enqueue_scripts()
    {
        wp_enqueue_style( 'rwmb-select', video_central()->admin->css_url  . 'metaboxes/select.css', array(), video_central()->version );

    }

    /**
     * Get field HTML
     *
     * @param mixed $meta
     * @param array $field
     *
     * @return string
     */
    static function html( $meta, $field )
    {
        $field['options'] = self::get_options( $field );
        switch ( $field['field_type'] )
        {
            case 'select':
                return RWMB_Select_Field::html( $meta, $field );
            case 'select_advanced':
            default:
                return RWMB_Select_Advanced_Field::html( $meta, $field );
        }
    }

    /**
     * Normalize parameters for field
     *
     * @param array $field
     *
     * @return array
     */
    static function normalize_field( $field )
    {
        $field = wp_parse_args( $field, array(
            'post_type'  => $field['post_type'],
            'field_type' => 'select_advanced',
            'parent'     => false,
            'query_args' => array(),
        ) );

        /**
         * Set default placeholder
         * - If multiple post types: show 'Select a post'
         * - If single post type: show 'Select a %post_type_name%'
         */
        if ( empty( $field['placeholder'] ) )
        {
            $label = __( 'Select a post', 'meta-box' );
            if ( is_string( $field['post_type'] ) && post_type_exists( $field['post_type'] ) )
            {
                $post_type_object = get_post_type_object( $field['post_type'] );
                $label            = sprintf( __( 'Select a %s', 'meta-box' ), $post_type_object->labels->singular_name );
            }
            $field['placeholder'] = $label;
        }

        if ( $field['parent'] )
        {
            $field['multiple']   = false;
            $field['field_name'] = 'parent_id';
        }

        $field['query_args'] = wp_parse_args( $field['query_args'], array(
            'post_type'      => $field['post_type'],
            'post_status'    => 'publish',
            'posts_per_page' => - 1,
        ) );

        return RWMB_Select_Field::normalize_field( $field );

    }

    /**
     * Get meta value
     * If field is cloneable, value is saved as a single entry in DB
     * Otherwise value is saved as multiple entries (for backward compatibility)
     *
     * @see "save" method for better understanding
     *
     * @param $post_id
     * @param $saved
     * @param $field
     *
     * @return array
     */
    static function meta( $post_id, $saved, $field )
    {
        if ( isset( $field['parent'] ) && $field['parent'] )
        {
            $post = get_post( $post_id );

            return $post->post_parent;
        }

        return parent::meta( $post_id, $saved, $field );
    }

    /**
     * Get posts
     *
     * @param array $field
     *
     * @return array
     */
    static function get_options( $field )
    {
        $options = array();
        $query   = new WP_Query( $field['query_args'] );
        if ( $query->have_posts() )
        {
            while ( $query->have_posts() )
            {
                $post               = $query->next_post();
                $title 				= apply_filters( 'rwmb_post_field_title', $post->post_title, $post );
                $title 				= apply_filters( "rwmb_{$field['id']}_field_title", $title, $post );
                $options[$post->ID] = $post->post_title;
            }
        }

        return $options;
    }

    /**
     * Get post link to display in the frontend
     *
     * @param int   $value Option value, e.g. post ID
     * @param int   $index Array index
     * @param array $field Field parameter
     *
     * @return string
     */
    static function get_option_label( &$value, $index, $field )
    {
        $value = sprintf(
            '<a href="%s" title="%s">%s</a>',
            esc_url( get_permalink( $value ) ),
            the_title_attribute( array(
                'post' => $value,
                'echo' => false,
            ) ),
            get_the_title( $value )
        );
    }
}