<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: tick_list — "what's included" two-column checklist.
 * Fields (from class-cc-sections.php): bg, eyebrow, heading, intro, items[ text ].
 * Markup mirrors build.py: .section__head (eyebrow + section-title [+ section-intro])
 * then <ul class="ticklist ticklist--2">; each <li> is plain text, the check
 * bullet is drawn by CSS via .ticklist li::before.
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'white';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$items   = (isset($section_data['items']) && is_array($section_data['items'])) ? $section_data['items'] : array();

// On the ink (dark) background the heading flips to the light variant.
$title_cls = 'section-title' . ($bg === 'ink' ? ' section-title--light' : '');
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <?php if ($eyebrow !== '' || $heading !== '' || $intro !== '') : ?>
    <div class="section__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="section-intro"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <ul class="ticklist ticklist--2 reveal" style="margin-bottom:0">
      <?php foreach ($items as $item) :
        $text = isset($item['text']) ? $item['text'] : '';
        if ($text === '') continue;
      ?>
      <li><?php echo esc_html($text); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
