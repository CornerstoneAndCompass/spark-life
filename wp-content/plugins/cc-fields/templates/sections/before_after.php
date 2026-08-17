<?php if (!defined('ABSPATH')) exit;

/**
 * Section: Before / After Slider
 * Mirrors the home page #emergency before/after block from build.py so that
 * script.js (#ba / #baHandle) drives the drag interaction unchanged.
 */

$bg = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
if (!in_array($bg, array('paper', 'white', 'ink'), true)) { $bg = 'paper'; }

$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';

$before_url = !empty($section_data['before_image'])
    ? Ccf_Renderer::get_image_url($section_data['before_image'])
    : (isset($section_data['before_image_url']) ? $section_data['before_image_url'] : '');

$after_url = !empty($section_data['after_image'])
    ? Ccf_Renderer::get_image_url($section_data['after_image'])
    : (isset($section_data['after_image_url']) ? $section_data['after_image_url'] : '');

$before_label = isset($section_data['before_label']) && $section_data['before_label'] !== ''
    ? $section_data['before_label'] : 'Before';
$after_label = isset($section_data['after_label']) && $section_data['after_label'] !== ''
    ? $section_data['after_label'] : 'After';

$caption = isset($section_data['caption']) ? $section_data['caption'] : '';
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
    <div class="section__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section-title"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="section-intro"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="reveal">
      <div class="ba" id="ba">
        <div class="ba__img ba__img--after">
          <?php if ($after_url !== '') : ?>
          <img src="<?php echo esc_url($after_url); ?>" alt="<?php echo esc_attr($after_label); ?>" draggable="false">
          <?php endif; ?>
          <span class="ba__tag ba__tag--after"><?php echo esc_html($after_label); ?></span>
        </div>
        <div class="ba__img ba__img--before">
          <?php if ($before_url !== '') : ?>
          <img src="<?php echo esc_url($before_url); ?>" alt="<?php echo esc_attr($before_label); ?>" draggable="false">
          <?php endif; ?>
          <span class="ba__tag ba__tag--before"><?php echo esc_html($before_label); ?></span>
        </div>
        <div class="ba__handle" id="baHandle" role="slider" tabindex="0" aria-label="Before and after slider" aria-valuemin="0" aria-valuemax="100" aria-valuenow="50">
          <span class="ba__line"></span>
          <span class="ba__grip"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="currentColor" d="M9 7 4 12l5 5zM15 7l5 5-5 5z"/></svg></span>
        </div>
      </div>
      <?php if ($caption !== '') : ?>
      <p class="work__caption"><?php echo esc_html($caption); ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>
