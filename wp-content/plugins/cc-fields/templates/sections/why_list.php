<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: why_list — sticky intro on the left, numbered reasons grid on the right.
 * Fields (from class-cc-sections.php): bg, eyebrow, heading, intro, items[ title, desc ].
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'ink';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$items   = (isset($section_data['items']) && is_array($section_data['items'])) ? $section_data['items'] : array();

// On the ink (dark) background the heading is light and reasons render dark.
// On paper/white the .why--light modifier flips item borders/colours to suit.
$is_ink   = ($bg === 'ink');
$why_mod  = $is_ink ? '' : ' why--light';
$title_cls = 'section-title' . ($is_ink ? ' section-title--light' : '');
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap why__in<?php echo esc_attr($why_mod); ?>">
    <div class="why__intro reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow eyebrow--lime"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="why__lead"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>
    <ol class="why__list">
      <?php foreach ($items as $i => $item) :
        $title = isset($item['title']) ? $item['title'] : '';
        $desc  = isset($item['desc']) ? $item['desc'] : '';
        $num   = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
      ?>
      <li class="why__item reveal"><span class="why__num"><?php echo esc_html($num); ?></span>
      <div><h3><?php echo esc_html($title); ?></h3><p><?php echo esc_html($desc); ?></p></div></li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
