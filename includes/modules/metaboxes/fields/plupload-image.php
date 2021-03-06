<?php
/**
 * Image upload field which uses plupload library to drag and drop files to upload.
 */
class Video_Central_Metaboxes_Plupload_Image_Field extends Video_Central_Metaboxes_Image_Field
{
	/**
	 * Add field actions.
	 */
	static function add_actions()
	{
		parent::add_actions();
		add_action( 'wp_ajax_video_central_metaboxes_plupload_image_upload', array( __CLASS__, 'handle_upload' ) );
	}

	/**
	 * Upload ajax callback function.
	 */
	static function handle_upload()
	{
		$post_id  = (int) filter_input( INPUT_POST, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$field_id = (string) filter_input( INPUT_POST, 'field_id' );

		check_ajax_referer( "video-central-metaboxes-upload-images_{$field_id}" );

		$file       = $_FILES['async-upload'];
		$file_attr  = wp_handle_upload( $file, array( 'test_form' => false ) );
		$attachment = array(
			'guid'           => $file_attr['url'],
			'post_mime_type' => $file_attr['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file['name'] ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Adds file as attachment to WordPress
		$attachment_id = wp_insert_attachment( $attachment, $file_attr['file'], $post_id );
		if ( is_wp_error( $attachment_id ) )
		{
			wp_send_json_error();
		}
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $file_attr['file'] ) );

		// Save file ID in meta field
		add_post_meta( $post_id, $field_id, $attachment_id, false );
		wp_send_json_success( self::img_html( $attachment_id ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	static function admin_enqueue_scripts()
	{
		// Enqueue same scripts and styles as for file field
		parent::admin_enqueue_scripts();
		wp_enqueue_style( 'video-central-metaboxes-plupload-image', Video_Central_Metaboxes_CSS_URL . 'plupload-image.css', array( 'wp-admin' ), Video_Central_Metaboxes_VER );
		wp_enqueue_script( 'video-central-metaboxes-plupload-image', Video_Central_Metaboxes_JS_URL . 'plupload-image.js', array( 'jquery-ui-sortable', 'wp-ajax-response', 'plupload-all' ), Video_Central_Metaboxes_VER, true );
		wp_localize_script( 'video-central-metaboxes-plupload-image', 'RWMB', array( 'url' => Video_Central_Metaboxes_URL ) );
	}

	/**
	 * Get field HTML.
	 *
	 * @param mixed $meta
	 * @param array $field
	 * @return string
	 */
	static function html( $meta, $field )
	{
		if ( ! is_array( $meta ) )
			$meta = ( array ) $meta;

		// Filter to change the drag & drop box background string
		$i18n_drop   = apply_filters( 'video_central_metaboxes_plupload_image_drop_string', _x( 'Drop images here', 'image upload', 'meta-box' ), $field );
		$i18n_or     = apply_filters( 'video_central_metaboxes_plupload_image_or_string', _x( 'or', 'image upload', 'meta-box' ), $field );
		$i18n_select = apply_filters( 'video_central_metaboxes_plupload_image_select_string', _x( 'Select Files', 'image upload', 'meta-box' ), $field );

		// Uploaded images

		// Check for max_file_uploads
		$classes = array( 'video-central-metaboxes-drag-drop', 'drag-drop', 'hide-if-no-js', 'new-files' );
		if ( ! empty( $field['max_file_uploads'] ) && count( $meta ) >= (int) $field['max_file_uploads'] )
			$classes[] = 'hidden';

		$html = self::get_uploaded_images( $meta, $field );

		// Show form upload
		$html .= sprintf(
			'<div id="%s-dragdrop" class="%s" data-upload_nonce="%s" data-js_options="%s">
				<div class = "drag-drop-inside">
					<p class="drag-drop-info">%s</p>
					<p>%s</p>
					<p class="drag-drop-buttons"><input id="%s-browse-button" type="button" value="%s" class="button" /></p>
				</div>
			</div>',
			$field['id'],
			implode( ' ', $classes ),
			wp_create_nonce( "video-central-metaboxes-upload-images_{$field['id']}" ),
			esc_attr( wp_json_encode( $field['js_options'] ) ),
			$i18n_drop,
			$i18n_or,
			$field['id'],
			$i18n_select
		);

		return $html;
	}

	/**
	 * Get field value.
	 * It's the combination of new (uploaded) images and saved images
	 *
	 * @param array $new
	 * @param array $old
	 * @param int   $post_id
	 * @param array $field
	 *
	 * @return array
	 */
	static function value( $new, $old, $post_id, $field )
	{
		$new = (array) $new;
		return array_unique( array_merge( $old, $new ) );
	}

	/**
	 * Normalize parameters for field.
	 *
	 * @param array $field
	 * @return array
	 */
	static function normalize( $field )
	{
		$field['js_options'] = array(
			'runtimes'            => 'html5,silverlight,flash,html4',
			'file_data_name'      => 'async-upload',
			//'container'				=> $field['id'] . '-container',
			'browse_button'       => $field['id'] . '-browse-button',
			'drop_element'        => $field['id'] . '-dragdrop',
			'multiple_queues'     => true,
			'max_file_size'       => wp_max_upload_size() . 'b',
			'url'                 => admin_url( 'admin-ajax.php' ),
			'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
			'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
			'multipart'           => true,
			'urlstream_upload'    => true,
			'filters'             => array(
				array(
					'title'      => _x( 'Allowed Image Files', 'image upload', 'meta-box' ),
					'extensions' => 'jpg,jpeg,gif,png',
				),
			),
			'multipart_params'    => array(
				'field_id' => $field['id'],
				'action'   => 'video_central_metaboxes_plupload_image_upload',
			),
		);
		$field               = parent::normalize( $field );

		return $field;
	}
}
