<?php
//about theme info
add_action( 'admin_menu', 'vw_kids_gettingstarted' );
function vw_kids_gettingstarted() {
	add_theme_page( esc_html__('About VW Kids', 'vw-kids'), esc_html__('About VW Kids', 'vw-kids'), 'edit_theme_options', 'vw_kids_guide', 'vw_kids_mostrar_guide');
}

// Add a Custom CSS file to WP Admin Area
function vw_kids_admin_theme_style() {
   wp_enqueue_style('vw-kids-custom-admin-style', esc_url(get_template_directory_uri()) . '/inc/getstart/getstart.css');
   wp_enqueue_script('vw-kids-tabs', esc_url(get_template_directory_uri()) . '/inc/getstart/js/tab.js');
   wp_enqueue_style( 'font-awesome-css', esc_url(get_template_directory_uri()).'/assets/css/fontawesome-all.css' );
}
add_action('admin_enqueue_scripts', 'vw_kids_admin_theme_style');

//guidline for about theme
function vw_kids_mostrar_guide() { 
	//custom function about theme customizer
	$return = add_query_arg( array()) ;
	$theme = wp_get_theme( 'vw-kids' );
?>

<div class="wrapper-info">
    <div class="col-left">
    	<h2><?php esc_html_e( 'Welcome to VW Kids Theme', 'vw-kids' ); ?> <span class="version">Version: <?php echo esc_html($theme['Version']);?></span></h2>
    	<p><?php esc_html_e('All our WordPress themes are modern, minimalist, 100% responsive, seo-friendly,feature-rich, and multipurpose that best suit designers, bloggers and other professionals who are working in the creative fields.','vw-kids'); ?></p>
    </div>
    <div class="col-right">
    	<div class="logo">
			<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/final-logo.png" alt="" />
		</div>
		<div class="update-now">
			<h4><?php esc_html_e('Buy VW Kids at 20% Discount','vw-kids'); ?></h4>
			<h4><?php esc_html_e('Use Coupon','vw-kids'); ?> ( <span><?php esc_html_e('vwpro20','vw-kids'); ?></span> ) </h4>
			<div class="info-link">
				<a href="<?php echo esc_url( VW_KIDS_BUY_NOW ); ?>" target="_blank"> <?php esc_html_e( 'Upgrade to Pro', 'vw-kids' ); ?></a>
			</div>
		</div>
    </div>

    <div class="tab-sec">
		<div class="tab">
			<button class="tablinks" onclick="vw_kids_open_tab(event, 'gutenberg_editor')"><?php esc_html_e( 'Setup With Gutunberg Block', 'vw-kids' ); ?></button>
			<button class="tablinks" onclick="vw_kids_open_tab(event, 'block_pattern')"><?php esc_html_e( 'Setup With Block Pattern', 'vw-kids' ); ?></button>
		  	<button class="tablinks" onclick="vw_kids_open_tab(event, 'lite_theme')"><?php esc_html_e( 'Setup With Customizer', 'vw-kids' ); ?></button>
		  	<button class="tablinks" onclick="vw_kids_open_tab(event, 'theme_pro')"><?php esc_html_e( 'Get Premium', 'vw-kids' ); ?></button>
		  	<button class="tablinks" onclick="vw_kids_open_tab(event, 'free_pro')"><?php esc_html_e( 'Support', 'vw-kids' ); ?></button>
		</div>

		<!-- Tab content -->
		<?php
			$vw_kids_plugin_custom_css = '';
			if(class_exists('Ibtana_Visual_Editor_Menu_Class')){
				$vw_kids_plugin_custom_css ='display: block';
			}
		?>
		<div id="gutenberg_editor" class="tabcontent open">
			<?php if(!class_exists('Ibtana_Visual_Editor_Menu_Class')){ 
			$plugin_ins = VW_Kids_Plugin_Activation_Settings::get_instance();
			$vw_kids_actions = $plugin_ins->recommended_actions;
			?>
				<div class="vw-kids-recommended-plugins">
				    <div class="vw-kids-action-list">
				        <?php if ($vw_kids_actions): foreach ($vw_kids_actions as $key => $vw_kids_actionValue): ?>
				                <div class="vw-kids-action" id="<?php echo esc_attr($vw_kids_actionValue['id']);?>">
			                        <div class="action-inner plugin-activation-redirect">
			                            <h3 class="action-title"><?php echo esc_html($vw_kids_actionValue['title']); ?></h3>
			                            <div class="action-desc"><?php echo esc_html($vw_kids_actionValue['desc']); ?></div>
			                            <?php echo wp_kses_post($vw_kids_actionValue['link']); ?>
			                        </div>
				                </div>
				            <?php endforeach;
				        endif; ?>
				    </div>
				</div>
			<?php }else{ ?>
				<h3><?php esc_html_e( 'Gutunberg Blocks', 'vw-kids' ); ?></h3>
				<hr class="h3hr">
				<div class="vw-kids-pattern-page">
			    	<a href="<?php echo esc_url( admin_url( 'admin.php?page=ibtana-visual-editor-templates' ) ); ?>" class="vw-pattern-page-btn ibtana-dashboard-page-btn button-primary button"><?php esc_html_e('Ibtana Settings','vw-kids'); ?></a>
			    </div>

			    <div class="link-customizer-with-guternberg-ibtana">
					<h3><?php esc_html_e( 'Link to customizer', 'vw-kids' ); ?></h3>
					<hr class="h3hr">
					<div class="first-row">
						<div class="row-box">
							<div class="row-box1">
								<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','vw-kids'); ?></a>
							</div>
							<div class="row-box2">
								<span class="dashicons dashicons-networking"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_social_icon_settings') ); ?>" target="_blank"><?php esc_html_e('Social Icons','vw-kids'); ?></a>
							</div>
						</div>
						<div class="row-box">
							<div class="row-box1">
								<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','vw-kids'); ?></a>
							</div>
							
							<div class="row-box2">
								<span class="dashicons dashicons-text-page"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_footer') ); ?>" target="_blank"><?php esc_html_e('Footer Text','vw-kids'); ?></a>
							</div>
						</div>

						<div class="row-box">
							<div class="row-box1">
								<span class="dashicons dashicons-format-gallery"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_post_settings') ); ?>" target="_blank"><?php esc_html_e('Post settings','vw-kids'); ?></a>
							</div>
							 <div class="row-box2">
								<span class="dashicons dashicons-align-center"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_woocommerce_section') ); ?>" target="_blank"><?php esc_html_e('WooCommerce Layout','vw-kids'); ?></a>
							</div> 
						</div>
						
						<div class="row-box">
							<div class="row-box1">
								<span class="dashicons dashicons-admin-generic"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_left_right') ); ?>" target="_blank"><?php esc_html_e('General Settings','vw-kids'); ?></a>
							</div>
							 <div class="row-box2">
								<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','vw-kids'); ?></a>
							</div> 
						</div>
					</div>
				</div>
			<?php } ?>
		</div>

		<div id="block_pattern" class="tabcontent">
			<?php if(!class_exists('Ibtana_Visual_Editor_Menu_Class')){ 
				$plugin_ins = VW_Kids_Plugin_Activation_Settings::get_instance();
				$vw_kids_actions = $plugin_ins->recommended_actions;
				?>
				<div class="vw-kids-recommended-plugins">
				    <div class="vw-kids-action-list">
				        <?php if ($vw_kids_actions): foreach ($vw_kids_actions as $key => $vw_kids_actionValue): ?>
				                <div class="vw-kids-action" id="<?php echo esc_attr($vw_kids_actionValue['id']);?>">
			                        <div class="action-inner">
			                            <h3 class="action-title"><?php echo esc_html($vw_kids_actionValue['title']); ?></h3>
			                            <div class="action-desc"><?php echo esc_html($vw_kids_actionValue['desc']); ?></div>
			                            <?php echo wp_kses_post($vw_kids_actionValue['link']); ?>
			                            <a class="ibtana-skip-btn" href="javascript:void(0);" get-start-tab-id="gutenberg-editor-tab"><?php esc_html_e('Skip','vw-kids'); ?></a>
			                        </div>
				                </div>
				            <?php endforeach;
				        endif; ?>
				    </div>
				</div>
			<?php } ?>
			<div class="gutenberg-editor-tab" style="<?php echo esc_attr($vw_kids_plugin_custom_css); ?>">
				<div class="block-pattern-img">
				  	<h3><?php esc_html_e( 'Block Patterns', 'vw-kids' ); ?></h3>
					<hr class="h3hr">
					<p><?php esc_html_e('Follow the below instructions to setup Home page with Block Patterns.','vw-kids'); ?></p>
	              	<p><b><?php esc_html_e('Click on Below Add new page button >> Click on "+" Icon >> Click Pattern Tab >> Click on homepage sections >> Publish.','vw-kids'); ?></span></b></p>
	              	<div class="vw-kids-pattern-page">
				    	<a href="javascript:void(0)" class="vw-pattern-page-btn button-primary button"><?php esc_html_e('Add New Page','vw-kids'); ?></a>
				    </div>

	          		<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/block-pattern.png" alt="" />	
	             </div>

              	<div class="block-pattern-link-customizer">
	              	<div class="link-customizer-with-block-pattern">
						<h3><?php esc_html_e( 'Link to customizer', 'vw-kids' ); ?></h3>
						<hr class="h3hr">
						<div class="first-row">
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','vw-kids'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-networking"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_social_icon_settings') ); ?>" target="_blank"><?php esc_html_e('Social Icons','vw-kids'); ?></a>
								</div>
							</div>
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','vw-kids'); ?></a>
								</div>
								
								<div class="row-box2">
									<span class="dashicons dashicons-text-page"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_footer') ); ?>" target="_blank"><?php esc_html_e('Footer Text','vw-kids'); ?></a>
								</div>
							</div>

							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-format-gallery"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_post_settings') ); ?>" target="_blank"><?php esc_html_e('Post settings','vw-kids'); ?></a>
								</div>
								 <div class="row-box2">
									<span class="dashicons dashicons-align-center"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_woocommerce_section') ); ?>" target="_blank"><?php esc_html_e('WooCommerce Layout','vw-kids'); ?></a>
								</div> 
							</div>
							
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-admin-generic"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_left_right') ); ?>" target="_blank"><?php esc_html_e('General Settings','vw-kids'); ?></a>
								</div>
								 <div class="row-box2">
									<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','vw-kids'); ?></a>
								</div> 
							</div>
						</div>
					</div>
				</div>	

	        </div>
		</div>

		<div id="lite_theme" class="tabcontent">
			<?php if(!class_exists('Ibtana_Visual_Editor_Menu_Class')){ 
				$plugin_ins = VW_Kids_Plugin_Activation_Settings::get_instance();
				$vw_kids_actions = $plugin_ins->recommended_actions;
				?>
				<div class="vw-kids-recommended-plugins">
				    <div class="vw-kids-action-list">
				        <?php if ($vw_kids_actions): foreach ($vw_kids_actions as $key => $vw_kids_actionValue): ?>
				                <div class="vw-kids-action" id="<?php echo esc_attr($vw_kids_actionValue['id']);?>">
			                        <div class="action-inner">
			                            <h3 class="action-title"><?php echo esc_html($vw_kids_actionValue['title']); ?></h3>
			                            <div class="action-desc"><?php echo esc_html($vw_kids_actionValue['desc']); ?></div>
			                            <?php echo wp_kses_post($vw_kids_actionValue['link']); ?>
			                            <a class="ibtana-skip-btn" get-start-tab-id="lite-theme-tab" href="javascript:void(0);"><?php esc_html_e('Skip','vw-kids'); ?></a>
			                        </div>
				                </div>
				            <?php endforeach;
				        endif; ?>
				    </div>
				</div>
			<?php } ?>
			<div class="lite-theme-tab" style="<?php echo esc_attr($vw_kids_plugin_custom_css); ?>">
				<h3><?php esc_html_e( 'Lite Theme Information', 'vw-kids' ); ?></h3>
				<hr class="h3hr">
			  	<p><?php esc_html_e('VW Kids is a colourful, youthful, fresh and versatile kids WordPress theme for kindergartens, creches, play schools, preschools, day care centres, babysitters, primary and secondary schools, education and training centres, art classes and nurseries. It is a multipurpose theme which can be used for kids toys and games store, kids skin care products, clothing and shoes mall, kids book store, baby food store, baby care range supplier, kids school bags and stationery store, toy gifts shop and kids fashion store. It can be used by kids health care blogger and portfolio designers. It is a totally responsive, SEO enabled, multilingual, cross-browser compatible and retina ready theme with multiple header and footer styles and various blog layouts. VW Kids performs all the advanced functions without ever bloating the website. It has a range of social media icons to promote your services. It has smart placement of call to action (CTA) button and other components throughout the theme to make a user-friendly website. Its design is made attractive with eye-catching colours and beautiful fonts to impress visitors at the first sight. This kids theme is fully customizable and compatible with the new WordPress version. It has clean and bug-free codes making it a high quality theme.','vw-kids'); ?></p>
			  	<div class="col-left-inner">
			  		<h4><?php esc_html_e( 'Theme Documentation', 'vw-kids' ); ?></h4>
					<p><?php esc_html_e( 'If you need any assistance regarding setting up and configuring the Theme, our documentation is there.', 'vw-kids' ); ?></p>
					<div class="info-link">
						<a href="<?php echo esc_url( VW_KIDS_FREE_THEME_DOC ); ?>" target="_blank"> <?php esc_html_e( 'Documentation', 'vw-kids' ); ?></a>
					</div>
					<hr>
					<h4><?php esc_html_e('Theme Customizer', 'vw-kids'); ?></h4>
					<p> <?php esc_html_e('To begin customizing your website, start by clicking "Customize".', 'vw-kids'); ?></p>
					<div class="info-link">
						<a target="_blank" href="<?php echo esc_url( admin_url('customize.php') ); ?>"><?php esc_html_e('Customizing', 'vw-kids'); ?></a>
					</div>
					<hr>				
					<h4><?php esc_html_e('Having Trouble, Need Support?', 'vw-kids'); ?></h4>
					<p> <?php esc_html_e('Our dedicated team is well prepared to help you out in case of queries and doubts regarding our theme.', 'vw-kids'); ?></p>
					<div class="info-link">
						<a href="<?php echo esc_url( VW_KIDS_SUPPORT ); ?>" target="_blank"><?php esc_html_e('Support Forum', 'vw-kids'); ?></a>
					</div>
					<hr>
					<h4><?php esc_html_e('Reviews & Testimonials', 'vw-kids'); ?></h4>
					<p> <?php esc_html_e('All the features and aspects of this WordPress Theme are phenomenal. I\'d recommend this theme to all.', 'vw-kids'); ?>  </p>
					<div class="info-link">
						<a href="<?php echo esc_url( VW_KIDS_REVIEW ); ?>" target="_blank"><?php esc_html_e('Reviews', 'vw-kids'); ?></a>
					</div>
			  		<div class="link-customizer">
						<h3><?php esc_html_e( 'Link to customizer', 'vw-kids' ); ?></h3>
						<hr class="h3hr">
						<div class="first-row">
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','vw-kids'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-welcome-write-blog"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_topbar') ); ?>" target="_blank"><?php esc_html_e('Topbar Settings','vw-kids'); ?></a>
								</div>
							</div>
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-slides"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_slidersettings') ); ?>" target="_blank"><?php esc_html_e('Slider Section','vw-kids'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-text-page"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_footer') ); ?>" target="_blank"><?php esc_html_e('Footer Text','vw-kids'); ?></a>
								</div>
							</div>
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','vw-kids'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-admin-customizer"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=vw_kids_typography') ); ?>" target="_blank"><?php esc_html_e('Typography','vw-kids'); ?></a>
								</div>
							</div>

							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-format-gallery"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_post_settings') ); ?>" target="_blank"><?php esc_html_e('Post settings','vw-kids'); ?></a>
								</div>
								 <div class="row-box2">
									<span class="dashicons dashicons-align-center"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_woocommerce_section') ); ?>" target="_blank"><?php esc_html_e('WooCommerce Layout','vw-kids'); ?></a>
								</div> 
							</div>
							
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-admin-generic"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=vw_kids_left_right') ); ?>" target="_blank"><?php esc_html_e('General Settings','vw-kids'); ?></a>
								</div>
								 <div class="row-box2">
									<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','vw-kids'); ?></a>
								</div> 
							</div>
						</div>
					</div>
			  	</div>
				<div class="col-right-inner">
					<h3 class="page-template"><?php esc_html_e('How to set up Home Page Template','vw-kids'); ?></h3>
				  	<hr class="h3hr">
					<p><?php esc_html_e('Follow these instructions to setup Home page.','vw-kids'); ?></p>
	                <ul>
	                  	<p><span class="strong"><?php esc_html_e('1. Create a new page :','vw-kids'); ?></span><?php esc_html_e(' Go to ','vw-kids'); ?>
					  	<b><?php esc_html_e(' Dashboard >> Pages >> Add New Page','vw-kids'); ?></b></p>

	                  	<p><?php esc_html_e('Name it as "Home" then select the template "Custom Home Page".','vw-kids'); ?></p>
	                  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/home-page-template.png" alt="" />
	                  	<p><span class="strong"><?php esc_html_e('2. Set the front page:','vw-kids'); ?></span><?php esc_html_e(' Go to ','vw-kids'); ?>
					  	<b><?php esc_html_e(' Settings >> Reading ','vw-kids'); ?></b></p>
					  	<p><?php esc_html_e('Select the option of Static Page, now select the page you created to be the homepage, while another page to be your default page.','vw-kids'); ?></p>
	                  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/set-front-page.png" alt="" />
	                  	<p><?php esc_html_e(' Once you are done with this, then follow the','vw-kids'); ?> <a class="doc-links" href="https://www.vwthemesdemo.com/docs/free-vw-kids/" target="_blank"><?php esc_html_e('Documentation','vw-kids'); ?></a></p>
	                </ul>
			  	</div>
			</div>
		</div>	

		<div id="theme_pro" class="tabcontent">
		  	<h3><?php esc_html_e( 'Premium Theme Information', 'vw-kids' ); ?></h3>
			<hr class="h3hr">
		    <div class="col-left-pro">
		    	<p><?php esc_html_e('Designing a kids website is not a kids play. It should be bright, intuitive, fresh, colourful and interesting to see, perfectly reflecting the enthusiasm of the audience it is made for. All these and many more things are offered by our premium quality kids WordPress theme that makes it the best match for the diverse niches in kids business. It can be used as a kids health blog, nanny and babysitter portfolio and as a website for kindergartens, day care centres, play schools, art and craft schools, sports centres, baby clothing, skin care and food products selling eCommerce store, toy shop, play station and all the kids website. This kids WordPress theme has a variety of layouts to alter the placement of website components according to your wish. It has simple backend interface which is very easy to understand so you can set the website even if you do not have any coding knowledge.','vw-kids'); ?></p>
		    	<div class="pro-links">
			    	<a href="<?php echo esc_url( VW_KIDS_LIVE_DEMO ); ?>" target="_blank"><?php esc_html_e('Live Demo', 'vw-kids'); ?></a>
					<a href="<?php echo esc_url( VW_KIDS_BUY_NOW ); ?>"><?php esc_html_e('Buy Pro', 'vw-kids'); ?></a>
					<a href="<?php echo esc_url( VW_KIDS_PRO_DOC ); ?>" target="_blank"><?php esc_html_e('Pro Documentation', 'vw-kids'); ?></a>
				</div>
		    </div>
		    <div class="col-right-pro">
		    	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/responsive.png" alt="" />
		    </div>
		    <div class="featurebox">
			    <h3><?php esc_html_e( 'Theme Features', 'vw-kids' ); ?></h3>
				<hr class="h3hr">
				<div class="table-image">
					<table class="tablebox">
						<thead>
							<tr>
								<th></th>
								<th><?php esc_html_e('Free Themes', 'vw-kids'); ?></th>
								<th><?php esc_html_e('Premium Themes', 'vw-kids'); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php esc_html_e('Theme Customization', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Responsive Design', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Logo Upload', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Social Media Links', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Slider Settings', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Number of Slides', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('4', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('Unlimited', 'vw-kids'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Template Pages', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('3', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('6', 'vw-kids'); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Home Page Template', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('1', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('1', 'vw-kids'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Theme sections', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('2', 'vw-kids'); ?></td>
								<td class="table-img"><?php esc_html_e('12', 'vw-kids'); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Contact us Page Template', 'vw-kids'); ?></td>
								<td class="table-img">0</td>
								<td class="table-img"><?php esc_html_e('1', 'vw-kids'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Blog Templates & Layout', 'vw-kids'); ?></td>
								<td class="table-img">0</td>
								<td class="table-img"><?php esc_html_e('3(Full width/Left/Right Sidebar)', 'vw-kids'); ?></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Page Templates & Layout', 'vw-kids'); ?></td>
								<td class="table-img">0</td>
								<td class="table-img"><?php esc_html_e('2(Left/Right Sidebar)', 'vw-kids'); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Color Pallete For Particular Sections', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Global Color Option', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Section Reordering', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Demo Importer', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Allow To Set Site Title, Tagline, Logo', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Enable Disable Options On All Sections, Logo', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Full Documentation', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Latest WordPress Compatibility', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Woo-Commerce Compatibility', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Support 3rd Party Plugins', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Secure and Optimized Code', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Exclusive Functionalities', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Section Enable / Disable', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Section Google Font Choices', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Gallery', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Simple & Mega Menu Option', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Support to add custom CSS / JS ', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Shortcodes', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Custom Background, Colors, Header, Logo & Menu', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Premium Membership', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Budget Friendly Value', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('Priority Error Fixing', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Custom Feature Addition', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr class="odd">
								<td><?php esc_html_e('All Access Theme Pass', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Seamless Customer Support', 'vw-kids'); ?></td>
								<td class="table-img"><i class="fas fa-times"></i></td>
								<td class="table-img"><i class="fas fa-check"></i></td>
							</tr>
							<tr>
								<td></td>
								<td class="table-img"></td>
								<td class="update-link"><a href="<?php echo esc_url( VW_KIDS_BUY_NOW ); ?>" target="_blank"><?php esc_html_e('Upgrade to Pro', 'vw-kids'); ?></a></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div id="free_pro" class="tabcontent">
		  	<div class="col-3">
		  		<h4><span class="dashicons dashicons-star-filled"></span><?php esc_html_e('Pro Version', 'vw-kids'); ?></h4>
				<p> <?php esc_html_e('To gain access to extra theme options and more interesting features, upgrade to pro version.', 'vw-kids'); ?></p>
				<div class="info-link">
					<a href="<?php echo esc_url( VW_KIDS_BUY_NOW ); ?>" target="_blank"><?php esc_html_e('Get Pro', 'vw-kids'); ?></a>
				</div>
		  	</div>
		  	<div class="col-3">
		  		<h4><span class="dashicons dashicons-cart"></span><?php esc_html_e('Pre-purchase Queries', 'vw-kids'); ?></h4>
				<p> <?php esc_html_e('If you have any pre-sale query, we are prepared to resolve it.', 'vw-kids'); ?></p>
				<div class="info-link">
					<a href="<?php echo esc_url( VW_KIDS_CONTACT ); ?>" target="_blank"><?php esc_html_e('Question', 'vw-kids'); ?></a>
				</div>
		  	</div>
		  	<div class="col-3">		  		
		  		<h4><span class="dashicons dashicons-admin-customizer"></span><?php esc_html_e('Child Theme', 'vw-kids'); ?></h4>
				<p> <?php esc_html_e('For theme file customizations, make modifications in the child theme and not in the main theme file.', 'vw-kids'); ?></p>
				<div class="info-link">
					<a href="<?php echo esc_url( VW_KIDS_CHILD_THEME ); ?>" target="_blank"><?php esc_html_e('About Child Theme', 'vw-kids'); ?></a>
				</div>
		  	</div>

		  	<div class="col-3">
		  		<h4><span class="dashicons dashicons-admin-comments"></span><?php esc_html_e('Frequently Asked Questions', 'vw-kids'); ?></h4>
				<p> <?php esc_html_e('We have gathered top most, frequently asked questions and answered them for your easy understanding. We will list down more as we get new challenging queries. Check back often.', 'vw-kids'); ?></p>
				<div class="info-link">
					<a href="<?php echo esc_url( VW_KIDS_FAQ ); ?>" target="_blank"><?php esc_html_e('View FAQ','vw-kids'); ?></a>
				</div>
		  	</div>

		  	<div class="col-3">
		  		<h4><span class="dashicons dashicons-sos"></span><?php esc_html_e('Support Queries', 'vw-kids'); ?></h4>
				<p> <?php esc_html_e('If you have any queries after purchase, you can contact us. We are eveready to help you out.', 'vw-kids'); ?></p>
				<div class="info-link">
					<a href="<?php echo esc_url( VW_KIDS_SUPPORT ); ?>" target="_blank"><?php esc_html_e('Contact Us', 'vw-kids'); ?></a>
				</div>
		  	</div>
		</div>
	</div>
</div>
<?php } ?>