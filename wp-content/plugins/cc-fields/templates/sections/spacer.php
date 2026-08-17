<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: spacer — vertical spacing / divider.
 * Fields (from class-cc-sections.php): bg (paper|white|ink, default paper), height (px, default 60).
 * Minimal: a single div whose inline height comes from the height field, carrying the
 * section--<bg> background variant class so it tints to match the static site palette
 * (.section--paper / .section--white / .section--ink in main.css).
 */

$bg     = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$height = isset($section_data['height']) && $section_data['height'] !== '' ? $section_data['height'] : 60;

// Coerce height to a clean integer pixel value.
$height = (int) $height;
?>
<div class="section--<?php echo esc_attr($bg); ?>" style="height:<?php echo esc_attr($height); ?>px"></div>
