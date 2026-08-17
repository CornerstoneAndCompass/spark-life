<?php
/**
 * Template: Single post.
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
  <?php if (sl_has_sections(get_the_ID())) : ?>
    <?php sl_render_sections(get_the_ID()); ?>
  <?php else : ?>
    <section class="pagehero">
      <div class="pagehero__glow" aria-hidden="true"></div>
      <div class="wrap pagehero__inner">
        <span class="eyebrow eyebrow--light"><?php echo esc_html(get_the_date()); ?></span>
        <h1 class="pagehero__title"><?php the_title(); ?></h1>
      </div>
    </section>
    <section class="section section--white">
      <div class="wrap prose measure"><?php the_content(); ?></div>
    </section>
  <?php endif; ?>
<?php endwhile; ?>

<?php get_footer(); ?>
