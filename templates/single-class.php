<?php
/**
 * Template for displaying single class posts
 *
 * @package Amelia_Class_Extension
 */

get_header(); ?>

<div class="wrap">
    <div id="primary" class="content-area">
        <main id="main" class="site-main">
            <?php
            while (have_posts()) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>

                    <div class="entry-content">
                        <?php
                        the_content();

                        // This div will be where our React component renders
                        ?>
                        <div id="class-details" data-post-id="<?php echo esc_attr(get_the_ID()); ?>"></div>
                    </div>
                </article>
            <?php
            endwhile;
            ?>
        </main>
    </div>
</div>

<?php get_footer(); ?>