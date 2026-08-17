<?php
/**
 * Template: Archive listing.
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<section class="pagehero">
  <div class="pagehero__glow" aria-hidden="true"></div>
  <div class="wrap pagehero__inner">
    <h1 class="pagehero__title"><?php the_archive_title(); ?></h1>
    <?php if (get_the_archive_description()) : ?>
      <div class="pagehero__lead"><?php echo wp_kses_post(get_the_archive_description()); ?></div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap prose measure">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article <?php post_class('postcard'); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p class="postcard__date"><?php echo esc_html(get_the_date()); ?></p>
        <?php the_excerpt(); ?>
      </article>
    <?php endwhile; ?>
      <div class="pagination"><?php the_posts_pagination(array('mid_size' => 2)); ?></div>
    <?php else : ?>
      <p><?php esc_html_e('Nothing here yet.', 'sparklife'); ?></p>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>
