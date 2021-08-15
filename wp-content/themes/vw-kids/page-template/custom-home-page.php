<?php
/**
 * Template Name: Custom Home Page
 */

get_header(); ?>

<main id="maincontent" role="main">
  <?php do_action( 'vw_kids_before_slider' ); ?>
  
  <section id="cate_slider">
    <div class="container">
      <div class="row">
        <div class="col-lg-3 col-md-3">
          <div class="categry-title">
            <strong><i class="fa fa-bars" aria-hidden="true"></i><?php echo esc_html_e('ALL CATEGORIES','vw-kids'); ?></strong>
          </div>
          <?php if(class_exists('woocommerce')){ ?>
            <div class="product-cat" id="style-2">
              <?php
                $args = array(                  
                  'orderby'    => 'title',
                  'order'      => 'ASC',
                  'hide_empty' => 0,
                  'parent'  => 0
                );
                $product_categories = get_terms( 'product_cat', $args );
                $count = count($product_categories);
                if ( $count > 0 ){
                    foreach ( $product_categories as $product_category ) {
                      $kids_cat_id   = $product_category->term_id;
                      $cat_link = get_category_link( $kids_cat_id );
                      if ($product_category->category_parent == 0) { ?>
                    <li class="drp_dwn_menu"><a href="<?php echo esc_url(get_term_link( $product_category ) ); ?>">
                    <?php
                  }
                    echo esc_html( $product_category->name ); ?></a><i class="fas fa-caret-right"></i></li>
                    <?php
                    }
                  }
              ?>
            </div>
          <?php }?>
        </div>
        <div class="col-lg-9 col-md-9">
          <?php if( get_theme_mod( 'vw_kids_slider_hide_show', false) != '' || get_theme_mod( 'vw_kids_resp_slider_hide_show', false) != '') { ?>
            <section id="slider">
              <div id="carouselExampleCaptions" class="carousel slide" data-bs-ride="carousel" data-bs-interval="<?php echo esc_attr(get_theme_mod( 'vw_kids_slider_speed',4000)) ?>"> 
                <?php $vw_kids_slider_pages = array();
                  for ( $count = 1; $count <= 4; $count++ ) {
                    $mod = intval( get_theme_mod( 'vw_kids_slider_page' . $count ));
                    if ( 'page-none-selected' != $mod ) {
                      $vw_kids_slider_pages[] = $mod;
                    }
                  }
                  if( !empty($vw_kids_slider_pages) ) :
                    $args = array(
                      'post_type' => 'page',
                      'post__in' => $vw_kids_slider_pages,
                      'orderby' => 'post__in'
                    );
                    $query = new WP_Query( $args );
                    if ( $query->have_posts() ) :
                      $i = 1;
                ?>     
                <div class="carousel-inner" role="listbox">
                  <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                    <div <?php if($i == 1){echo 'class="carousel-item active"';} else{ echo 'class="carousel-item"';}?>>
                      <?php the_post_thumbnail(); ?>
                      <div class="carousel-caption">
                        <div class="inner_carousel">
                          <h1><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
                          <hr>
                          <p><?php $excerpt = get_the_excerpt(); echo esc_html( vw_kids_string_limit_words( $excerpt, esc_attr(get_theme_mod('vw_kids_slider_excerpt_number','30')))); ?></p>
                          <?php if( get_theme_mod('vw_kids_slider_button_text','SHOP NOW') != ''){ ?>
                            <div class="more-btn">
                              <a class="view-more" href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_theme_mod('vw_kids_slider_button_text',__('SHOP NOW','vw-kids')));?><i class="<?php echo esc_attr(get_theme_mod('vw_kids_slider_button_icon','fa fa-angle-right')); ?>"></i><span class="screen-reader-text"><?php echo esc_html(get_theme_mod('vw_kids_slider_button_text',__('SHOP NOW','vw-kids')));?></span></a>
                            </div>
                          <?php } ?>
                        </div>
                      </div>
                    </div>
                  <?php $i++; endwhile; 
                  wp_reset_postdata();?>
                </div>
                <?php else : ?>
                    <div class="no-postfound"></div>
                <?php endif;
                endif;?>
                <a class="carousel-control-prev" data-bs-target="#carouselExampleCaptions" data-bs-slide="prev" role="button">
                  <span class="carousel-control-prev-icon w-auto h-auto" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
                  <span class="screen-reader-text"><?php esc_html_e( 'Previous','vw-kids' );?></span>
                </a>
                <a class="carousel-control-next" data-bs-target="#carouselExampleCaptions" data-bs-slide="next" role="button">
                  <span class="carousel-control-next-icon w-auto h-auto" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                  <span class="screen-reader-text"><?php esc_html_e( 'Next','vw-kids' );?></span>
                </a>
              </div>
              <div class="clearfix"></div>
            </section>
          <?php } ?>
        </div>
      </div>    
    </div>
  </section>

  <?php do_action( 'vw_kids_after_slider' ); ?>

  <section id="popular-toys">
    <div class="container">
      <?php $vw_kids_popular_page = array();
        $mod = absint( get_theme_mod( 'vw_kids_popular_product','vw-kids'));
        if ( 'page-none-selected' != $mod ) {
          $vw_kids_popular_page[] = $mod;
        }
        if( !empty($vw_kids_popular_page) ) :
          $args = array(
            'post_type' => 'page',
            'post__in' => $vw_kids_popular_page,
            'orderby' => 'post__in'
          );
          $query = new WP_Query( $args );
          if ( $query->have_posts() ) :
            $count = 0;
            while ( $query->have_posts() ) : $query->the_post(); ?>
              <h2><?php the_title(); ?></h2>
              <hr>
              <?php the_content(); ?>
            <?php $count++; endwhile; ?>
          <?php else : ?>
            <div class="no-postfound"></div>
          <?php endif;
        endif;
        wp_reset_postdata()
      ?>
    </div>  
  </section>

  <?php do_action( 'vw_kids_after_popular_toys' ); ?>

  <div class="content-vw">
    <div class="container">
      <?php while ( have_posts() ) : the_post(); ?>
        <?php the_content(); ?>
      <?php endwhile; // end of the loop. ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>