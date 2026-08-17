<?php
/**
 * Template: Page. Renders CC Fields sections, or a tidy fallback for pages that
 * have none yet (e.g. a page created by hand in wp-admin).
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <?php if (sl_has_sections(get_the_ID())) : ?>
    <?php sl_render_sections(get_the_ID()); ?>
  <?php else : ?>
    <section class="pagehero">
      <div class="pagehero__glow" aria-hidden="true"></div>
      <div class="wrap pagehero__inner">
        <h1 class="pagehero__title"><?php the_title(); ?></h1>
      </div>
    </section>
    <section class="section section--white">
      <div class="wrap prose measure"><?php the_content(); ?></div>
    </section>
  <?php endif; ?>
<?php endwhile; endif; ?>

<?php get_footer(); ?>
