<?php
/**
 * Help link for featured settings box.
 *
 * @since TBD
 *
 * @var Tribe__Tickets__Admin__Views    $this  Template object.
 * @var array                           $link  Array of link arguments.
 */

 $defaults = [
    'slug'     => 'help-1',
    'priority' => 10,
    'link'     => '',
    'html'     => '',
    'target'   => '_blank',
    'classes'  => [],
 ];

 $link = wp_parse_args( $link, $defaults );
 $link['classes'][] = 'tec-tickets__admin-settings-featured-link';
 
 $admin_views = tribe( Tribe__Tickets__Admin__Views::class );

?>
<div <?php tribe_classes( $link['classes'] ); ?> >
	<?php $admin_views->template( 'components/icons/lightbulb' ); ?>
	<a
		href="<?php echo esc_attr( $link['link'] ); ?>"
		target="<?php echo esc_attr( $link['target'] ); ?>"
		rel="noopener noreferrer"
		class="tec-tickets__admin-settings-featured-link-url"
	><?php esc_html_e( 'Learn more about configuring PayPal payments', 'event-tickets' ); ?></a>
</div>