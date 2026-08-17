<?php
/**
 * Section: spacer — vertical space between sections.
 */
if (!defined('ABSPATH')) exit;

$bg     = sl_field($section_data, 'bg', 'white');
$height = max(0, (int) sl_field($section_data, 'height', '60'));
?>
<div class="section--<?php echo esc_attr($bg); ?>" style="height:<?php echo esc_attr($height); ?>px" aria-hidden="true"></div>
