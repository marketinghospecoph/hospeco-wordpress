<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package 'Clerina'
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}

$comments_number = get_comments_number();
$comments_class  = $comments_number ? 'has-comments' : '';
?>

<div id="comments" class="comments-area">

	<?php
	// You can start editing here -- including this comment!
	if ( have_comments() ) :
		?>
        <h2 class="comments-title <?php echo esc_attr( $comments_class ); ?>">
			<?php
			$_s_comment_count = get_comments_number();
			if ( '1' === $_s_comment_count ) {
				printf(
				/* translators: 1: title. */
					esc_html__( 'One Comment', 'clerina' )
				);
			} else {
				printf(
					esc_html( '%s Comments', 'clerina' ), number_format_i18n( $_s_comment_count ) );
			}
			?>
        </h2><!-- .comments-title -->

		<?php the_comments_navigation(); ?>

        <ol class="comment-list <?php echo esc_attr( $comments_class ); ?>">
			<?php
			wp_list_comments( array(
				'avatar_size' => 80,
				'short_ping'  => true,
				'callback'   => 'clerina_comment'
			) );
			?>
        </ol><!-- .comment-list -->

		<?php
		the_comments_navigation();

		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() ) :
			?>
            <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'clerina' ); ?></p>
		<?php
		endif;

	endif; // Check for have_comments().
	$comment_field = '<p class="comment-form-comment"><textarea required id="comment" placeholder="' . esc_attr__( 'Content', 'clerina' ) . '" name="comment" cols="45" rows="7" aria-required="true"></textarea></p>';
	comment_form(
		array(
			'format'        => 'xhtml',
			'comment_field' => $comment_field,
			'label_submit'=>'Submit Comment',
		)
	)
	?>

</div><!-- #comments -->
