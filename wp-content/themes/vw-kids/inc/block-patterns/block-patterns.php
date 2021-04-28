<?php
/**
 * VW Kids: Block Patterns
 *
 * @package VW Kids
 * @since   1.0.0
 */

/**
 * Register Block Pattern Category.
 */
if ( function_exists( 'register_block_pattern_category' ) ) {

	register_block_pattern_category(
		'vw-kids',
		array( 'label' => __( 'VW Kids', 'vw-kids' ) )
	);
}

/**
 * Register Block Patterns.
 */
if ( function_exists( 'register_block_pattern' ) ) {
	register_block_pattern(
		'vw-kids/banner-section',
		array(
			'title'      => __( 'Banner Section', 'vw-kids' ),
			'categories' => array( 'vw-kids' ),
			'content'    => "<!-- wp:cover {\"customOverlayColor\":\"#f7f7f7\",\"align\":\"full\",\"className\":\"main-banner\"} -->\n<div class=\"wp-block-cover alignfull has-background-dim main-banner\" style=\"background-color:#f7f7f7\"><div class=\"wp-block-cover__inner-container\"><!-- wp:columns {\"align\":\"wide\",\"className\":\"mx-lg-5 px-lg-4 m-0\"} -->\n<div class=\"wp-block-columns alignwide mx-lg-5 px-lg-4 m-0\"><!-- wp:column {\"width\":\"22.22%\",\"className\":\"cats\"} -->\n<div class=\"wp-block-column cats\" style=\"flex-basis:22.22%\"><!-- wp:heading {\"textAlign\":\"center\",\"level\":5,\"textColor\":\"white\"} -->\n<h5 class=\"has-text-align-left has-white-color has-text-color\">ALL CATEGORIES</h5>\n<!-- /wp:heading -->\n\n<!-- wp:woocommerce/product-categories /-->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:column -->\n\n<!-- wp:column {\"width\":\"77.77%\",\"className\":\"banner-box\"} -->\n<div class=\"wp-block-column banner-box\" style=\"flex-basis:77.77%\"><!-- wp:cover {\"url\":\"" . esc_url(get_template_directory_uri()) . "/inc/block-patterns/images/banner.png\",\"id\":9524,\"dimRatio\":0,\"minHeight\":410,\"customGradient\":\"linear-gradient(135deg,rgba(6,147,229,0) 0%,rgb(255,255,255) 100%)\",\"className\":\"bannner-content\"} -->\n<div class=\"wp-block-cover has-background-gradient bannner-content\" style=\"background-image:url(" . esc_url(get_template_directory_uri()) . "/inc/block-patterns/images/banner.png);min-height:410px\"><div class=\"wp-block-cover__inner-container\"><!-- wp:columns {\"className\":\"m-0\"} -->\n<div class=\"wp-block-columns m-0\"><!-- wp:column {\"verticalAlignment\":\"center\",\"width\":\"66.66%\",\"className\":\"pl-md-4\"} -->\n<div class=\"wp-block-column is-vertically-aligned-center pl-md-4\" style=\"flex-basis:66.66%\"><!-- wp:heading {\"textAlign\":\"left\",\"level\":4,\"className\":\"mb-2\",\"style\":{\"color\":{\"text\":\"#343c49\"},\"typography\":{\"fontSize\":15}}} -->\n<h4 class=\"has-text-align-left mb-2 has-text-color\" style=\"color:#343c49;font-size:15px\">LOREM IPSUM</h4>\n<!-- /wp:heading -->\n\n<!-- wp:heading {\"textAlign\":\"left\",\"level\":1,\"className\":\"mt-2\",\"style\":{\"color\":{\"text\":\"#343c49\"},\"typography\":{\"fontSize\":50}}} -->\n<h1 class=\"has-text-align-left mt-2 has-text-color\" style=\"color:#343c49;font-size:50px\">LOREM IPSUM IS SIMPLY</h1>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph {\"align\":\"left\",\"className\":\"text-left mt-2\",\"style\":{\"color\":{\"text\":\"#5b616c\"},\"typography\":{\"fontSize\":15}}} -->\n<p class=\"has-text-align-left text-left mt-2 has-text-color\" style=\"color:#5b616c;font-size:15px\">Lorem Ipsum has been the industrys standard.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:buttons -->\n<div class=\"wp-block-buttons\"><!-- wp:button {\"style\":{\"color\":{\"background\":\"#9cc44e\"}},\"textColor\":\"white\"} -->\n<div class=\"wp-block-button\"><a class=\"wp-block-button__link has-white-color has-text-color has-background\" style=\"background-color:#9cc44e\">SHOP NOW</a></div>\n<!-- /wp:button --></div>\n<!-- /wp:buttons --></div>\n<!-- /wp:column -->\n\n<!-- wp:column {\"width\":\"33.33%\"} -->\n<div class=\"wp-block-column\" style=\"flex-basis:33.33%\"></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns --></div></div>\n<!-- /wp:cover --></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns --></div></div>\n<!-- /wp:cover -->",
		)
	);

	register_block_pattern(
		'vw-kids/products-section',
		array(
			'title'      => __( 'Popular Products Section', 'vw-kids' ),
			'categories' => array( 'vw-kids' ),
			'content'    => "<!-- wp:cover {\"overlayColor\":\"white\",\"align\":\"wide\",\"className\":\"product-section m-0\"} -->\n<div class=\"wp-block-cover alignwide has-white-background-color has-background-dim product-section m-0\"><div class=\"wp-block-cover__inner-container\"><!-- wp:heading {\"textAlign\":\"left\",\"className\":\"text-left\",\"style\":{\"color\":{\"text\":\"#343c49\"}}} -->\n<h2 class=\"has-text-align-left text-left has-text-color\" style=\"color:#343c49\">MOST POPULAR TOYS</h2>\n<!-- /wp:heading -->\n\n<!-- wp:woocommerce/product-category {\"columns\":4,\"rows\":1,\"categories\":[32],\"contentVisibility\":{\"title\":true,\"price\":true,\"rating\":false,\"button\":true},\"align\":\"wide\",\"className\":\"mx-md-2\"} /--></div></div>\n<!-- /wp:cover -->",
		)
	);
}