<?php

if ( !class_exists( 'Radium_Video_Metaboxes_Field_Select' ) )
{
    class Radium_Video_Metaboxes_Field_Select extends Radium_Video_Metaboxes_Field
    {
        /**
         * Enqueue scripts and styles
         *
         * @return void
         */
        static function admin_enqueue_scripts()
        {
            wp_enqueue_style( 'video-central-admin-select', video_central()->admin->css_url  . 'metaboxes/select.css', array(), video_central()->version );
        }

        /**
         * Get field HTML
         *
         * @param mixed  $meta
         * @param array  $field
         *
         * @return string
         */
        static function html( $meta, $field )
        {
            $html = sprintf(
                '<select class="video-central-metaboxes-select" name="%s" id="%s" size="%s"%s>',
                $field['field_name'],
                $field['id'],
                $field['size'],
                $field['multiple'] ? ' multiple="multiple"' : ''
            );

            $html .= self::options_html( $field, $meta );

            $html .= '</select>';

            return $html;
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
            $single = $field['clone'] || !$field['multiple'];
            $meta = get_post_meta( $post_id, $field['id'], $single );
            $meta = ( !$saved && '' === $meta || array() === $meta ) ? $field['std'] : $meta;

            $meta = array_map( 'esc_attr', (array) $meta );

            return $meta;
        }

        /**
         * Save meta value
         * If field is cloneable, value is saved as a single entry in DB
         * Otherwise value is saved as multiple entries (for backward compatibility)
         *
         * @param $new
         * @param $old
         * @param $post_id
         * @param $field
         */
        static function save( $new, $old, $post_id, $field )
        {
            if ( !$field['clone'] )
            {
                parent::save( $new, $old, $post_id, $field );
                return;
            }

            if ( empty( $new ) )
                delete_post_meta( $post_id, $field['id'] );
            else
                update_post_meta( $post_id, $field['id'], $new );
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
                'desc'        => '',
                'name'        => $field['id'],
                'size'        => $field['multiple'] ? 5 : 0,
                'placeholder' => '',
            ) );
            if ( !$field['clone'] && $field['multiple'] )
                $field['field_name'] .= '[]';
            return $field;
        }

        /**
         * Creates html for options
         *
         * @param array $field
         * @param mixed $meta
         *
         * @return array
         */
        static function options_html( $field, $meta )
        {
            $html = '';
            if ( $field['placeholder'] )
                $html = 'select' == $field['type'] ? "<option value=''>{$field['placeholder']}</option>" : '<option></option>';

            $option = '<option value="%s"%s>%s</option>';

            foreach ( $field['options'] as $value => $label )
            {
                $html .= sprintf(
                    $option,
                    $value,
                    selected( in_array( $value, (array)$meta ), true, false ),
                    $label
                );
            }

            return $html;
        }
    }
}
