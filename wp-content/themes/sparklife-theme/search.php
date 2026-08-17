<?php
/**
 * Template: Search results.
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<section class="pagehero">
  <div class="pagehero__glow" aria-hidden="true"></div>
  <div class="wrap pagehero__inner">
    <span class="eyebrow eyebrow--light"><?php esc_html_e('Search', 'sparklife'); ?></span>
    <h1 class="pagehero__title"><?php printf(esc_html__('Results for “%s”', 'sparklife'), esc_html(get_search_query())); ?></h1>
  </div>
</section>

<section class="section section--paper">
  <div class="wrap prose measure">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
      <article <?php post_class('postcard'); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <?php the_excerpt(); ?>
      </article>
    <?php endwhile; else : ?>
      <p><?php esc_html_e('No results found.', 'sparklife'); ?>
        <a class="textlink" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back home', 'sparklife'); ?></a>.</p>
    <?php endif; ?>
  </div>
</section>

<?php get_footer(); ?>
