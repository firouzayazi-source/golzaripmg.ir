<?php
/**
 * متغیرهای مشترکِ صفحه‌های مجموعه.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$l       = 'golzari_label';
$h       = 'Golzari_Brand_Home';
$booking = esc_url( home_url( golzari_label( 'booking_path' ) ) );
$insta   = esc_url( Golzari_Brand::instagram_url() );
$phone   = golzari_label( 'phone' );
$phone_f = Golzari_Brand::fa_digits( $phone );
$nav     = array(
	'top'       => 'nav_home',
	'about'     => 'nav_about',
	'services'  => 'nav_services',
	'works'     => 'nav_works',
	'academy'   => 'nav_academy',
	'workshops' => 'nav_workshops',
	'contact'   => 'nav_contact',
);
$icon    = array(
	'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.6a1 1 0 0 1-.25 1z"/></svg>',
	'insta' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM17.5 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
	'pin'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 1 7 7c0 5.2-7 13-7 13S5 14.2 5 9a7 7 0 0 1 7-7zm0 4.5A2.5 2.5 0 1 0 12 11.5 2.5 2.5 0 0 0 12 6.5z"/></svg>',
	'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
	'award' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 1 4.6 12.3L18 22l-6-3-6 3 1.4-7.7A7 7 0 0 1 12 2zm0 2a5 5 0 1 0 0 10 5 5 0 0 0 0-10z"/></svg>',
	'gem'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12l4 6-10 12L2 9l4-6zm1.1 2L4.6 8.7h3.6L9.6 5H7.1zm4.6 0-1.4 3.7h3.4L12.3 5h-.6zm2.7 0 1.4 3.7h3.6L16.9 5h-2.5zM5 10.7l5.5 6.6-2.2-6.6H5zm5.4 0L12 15.5l1.6-4.8h-3.2zm5.3 0-2.2 6.6 5.5-6.6h-3.3z"/></svg>',
	'chat'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 1-2zm3 6v2h10V9H7zm0 4v2h7v-2H7z"/></svg>',
	'shield'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3zm-1 13.4-3.7-3.7 1.4-1.4 2.3 2.3 4.9-4.9 1.4 1.4-6.3 6.3z"/></svg>',
	'leaf'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 3S9 2 5.5 7.5C3 11.4 4.6 16 6 18l-3 3 1.4 1.4 3-3c2 1.4 6.6 3 10.5.5C23.5 16.4 20 3 20 3zM8 16c2-4 5-6.5 9-8-3.5 2.2-6 4.8-7.6 9L8 16z"/></svg>',
	'brow'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12c4-5 13-6 20-2l-1 1.8C15 8.6 7.8 9.4 3.6 13.3L2 12zm5 4h10v2H7z"/></svg>',
	'eye'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5 0 9 4.5 10 7-1 2.5-5 7-10 7S3 14.5 2 12c1-2.5 5-7 10-7zm0 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm0 2a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/></svg>',
	'lip'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 7c1.3-1.4 3-2 4.5-2C19 5 21.6 7.6 23 11c-2 5-6.3 8-11 8S3 16 1 11c1.4-3.4 4-6 6.5-6C9 5 10.7 5.6 12 7zm-8.6 4.4c2 1.6 5.1 2.4 8.6 2.4s6.6-.8 8.6-2.4c-1-1.8-2.6-3.4-4.1-3.4-1 0-2.3.6-3.7 2L12 11l-.8-1C9.8 8.6 8.5 8 7.5 8c-1.5 0-3.1 1.6-4.1 3.4z"/></svg>',
	'lash'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 13c5 5 15 5 20 0l1.4 1.4-1.6 1.4 1.7 2.5-1.7 1.1-1.7-2.6-1.9.9.9 2.9-1.9.6-.9-3-2.1.3V22h-2v-3.5l-2.1-.3-.9 3-1.9-.6.9-2.9-1.9-.9-1.7 2.6-1.7-1.1 1.7-2.5L.6 14.4 2 13z"/></svg>',
	'erase' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m16.2 3.6 4.2 4.2a2 2 0 0 1 0 2.8L11 20h9v2H8.4l-5-5a2 2 0 0 1 0-2.8L13.4 3.6a2 2 0 0 1 2.8 0zM9.6 9.6 4.8 14.4l4.2 4.2 4.8-4.8-4.2-4.2z"/></svg>',
	'cal'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h2v2h6V2h2v2h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3V2zm12 8H5v9h14v-9z"/></svg>',
);
