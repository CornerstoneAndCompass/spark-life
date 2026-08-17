<?php
/**
 * Fallback index.
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<section class="pagehero">
  <div class="pagehero__glow" aria-hidden="true"></div>
  <div class="wrap pagehero__inner">
    <span class="eyebrow eyebrow--light"><?php echo esc_html(get_bloginfo('name')); ?></span>
    <h1 class="pagehero__title"><?php echo esc_html(get_bloginfo('description') ?: __('Latest updates', 'sparklife')); ?></h1>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap prose measure">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article <?php post_class('postcard'); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p class="postcard__date"><?php echo esc_html(get_the_date()); ?></p>
        <?php the_excerpt(); ?>
        <a class="textlink" href="<?php the_permalink(); ?>"><?php esc_html_e('Read more', 'sparklife'); ?></a>
      </article>
    <?php endwhile; ?>
      <div class="pagination"><?php the_posts_pagination(array('mid_size' => 2)); ?></div>
    <?php else : ?>
      <p><?php esc_html_e('Nothing here yet.', 'sparklife'); ?>
        <a class="textlink" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back home', 'sparklife'); ?></a>.</p>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>
