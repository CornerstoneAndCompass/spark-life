<?php
/**
 * Front page — renders the Home page's CC Fields sections.
 */
if (!defined('ABSPATH')) exit;
get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <?php if (sl_has_sections(get_the_ID())) : ?>
    <?php sl_render_sections(get_the_ID()); ?>
  <?php else : ?>
    <section class="section section--paper">
      <div class="wrap prose measure">
        <h1 class="section__title"><?php the_title(); ?></h1>
        <?php the_content(); ?>
      </div>
    </section>
  <?php endif; ?>
<?php endwhile; endif; ?>

<?php get_footer(); ?>
