<?php
    $show_thumbnail = apply_filters( 'nm_blog_search_show_thumbnail', true );
?>
<div id="nm-blog-list" class="nm-search-results">
    <?php while ( have_posts() ) : the_post(); // Start the Loop ?>
    <div id="post-<?php esc_attr( the_ID() ); ?>" <?php post_class(); ?>>
        <div class="nm-row">
            <div class="nm-divider-col col-xs-12">
               <div class="nm-post-divider">&nbsp;</div>
            </div>
            
            <div class="nm-title-col col-xs-5">
                <?php
                if ( $show_thumbnail && has_post_thumbnail() ) :
                
                // Image size slug
                $image_size = apply_filters( 'nm_blog_image_size', '' );
                ?>
                <div class="nm-post-thumbnail">
                    <a href="<?php echo esc_url( get_permalink() ); ?>">
                        <?php the_post_thumbnail( $image_size ); ?>
                    </a>
                </div>
                <?php endif; ?>
                <div class="nm-post-header">
                    <h1 class="nm-post-title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h1>
                    <div class="nm-post-meta">
                        <span><?php the_time( get_option( 'date_format' ) ); ?></span>
                    </div>
                </div>
            </div>

            <div class="nm-content-col col-xs-7">
                <div class="nm-post-content">
                    <?php the_excerpt(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>