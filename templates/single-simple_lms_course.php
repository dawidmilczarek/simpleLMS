<?php
/**
 * Single course template.
 *
 * This template can be overridden by copying it to yourtheme/simple-lms/single-simple_lms_course.php
 *
 * @package SimpleLMS
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <?php
        while ( have_posts() ) :
            the_post();
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header>

                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
            </article>

            <?php
        endwhile;
        ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
