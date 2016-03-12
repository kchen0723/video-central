jQuery( document ).ready( function( $ )
{
    // Add more file
    $( '.video-central-metaboxes-add-file' ).each( function()
    {
        var $this = $( this ),
            $uploads = $this.siblings( '.file-input' ),
            $first = $uploads.first(),
            uploadCount = $uploads.length,
            $fileList = $this.closest( '.video-central-metaboxes-input' ).find( '.video-central-metaboxes-uploaded' ),
            fileCount = $fileList.children( 'li' ).length,
            maxFileUploads = $fileList.data( 'max_file_uploads' );

        // Hide "Add New File" and input fields when loaded
        if ( maxFileUploads > 0 )
        {
            if ( uploadCount + fileCount >= maxFileUploads  )
                $this.hide();
            if ( fileCount >= maxFileUploads )
                $uploads.hide();
        }

        $this.click( function()
        {
            // Clone upload input only when needed
            if ( maxFileUploads <= 0 || uploadCount + fileCount < maxFileUploads )
            {
                $first.clone().insertBefore( $this );
                uploadCount++;

                // If there're too many upload inputs, hide "Add New File"
                if ( maxFileUploads > 0 && uploadCount + fileCount >= maxFileUploads  )
                    $this.hide();
            }

            return false;
        } );
    } );

    // Delete file via Ajax
    $( '.video-central-metaboxes-uploaded' ).on( 'click', '.video-central-metaboxes-delete-file', function()
    {
        var $this = $( this ),
            $parent = $this.parents( 'li' ),
            $container = $this.closest( '.video-central-metaboxes-uploaded' ),
            data = {
                action: 'video_central_metaboxes_delete_file',
                _ajax_nonce: $container.data( 'delete_nonce' ),
                post_id: $( '#post_ID' ).val(),
                field_id: $container.data( 'field_id' ),
                attachment_id: $this.data( 'attachment_id' ),
                force_delete: $container.data( 'force_delete' )
            };

        $.post( ajaxurl, data, function( r )
        {
            if ( !r.success )
            {
                alert( r.data );
                return;
            }

            $parent.addClass( 'removed' );

            // If transition events not supported
            if (
                !( 'ontransitionend' in window )
                && ( 'onwebkittransitionend' in window )
                && !( 'onotransitionend' in myDiv || navigator.appName == 'Opera' )
            )
            {
                $parent.remove();
                $container.trigger( 'update.VideoCentralMetaboxesFile' );
            }

            $( '.video-central-metaboxes-uploaded' ).on( 'transitionend webkitTransitionEnd otransitionend', 'li.removed', function()
            {
                $( this ).remove();
                $container.trigger( 'update.VideoCentralMetaboxesFile' );
            } );
        }, 'json' );

        return false;
    } );

    //Remove deleted file
    $( '.video-central-metaboxes-uploaded' ).on( 'transitionend webkitTransitionEnd otransitionend', 'li.removed', function() {
        $( this ).remove();
    });

    $( 'body' ).on( 'update.VideoCentralMetaboxesFile', '.video-central-metaboxes-uploaded', function()
    {
        var $fileList = $( this ),
            maxFileUploads = $fileList.data( 'max_file_uploads' ),
            $uploader = $fileList.siblings( '.new-files' ),
            numFiles = $fileList.children().length;

        numFiles > 0 ? $fileList.removeClass( 'hidden' ) : $fileList.addClass( 'hidden' );

        // Return if maxFileUpload = 0
        if ( maxFileUploads === 0 )
            return false;

        // Hide files button if reach max file uploads
        numFiles >= maxFileUploads ? $uploader.addClass( 'hidden' ) : $uploader.removeClass( 'hidden' );

        return false;
    } );
} );
