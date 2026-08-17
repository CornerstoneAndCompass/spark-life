<?php
/**
 * Section: marquee — the blue scrolling band.
 *
 * With no items configured it falls back to the live Services list, so the band
 * stays in step with what the business actually offers.
 */
if (!defined('ABSPATH')) exit;

$items = array();
if (!empty($section_data['items']) && is_array($section_data['items'])) {
    foreach ($section_data['items'] as $row) {
        $text = isset($row['text']) ? trim($row['text']) : '';
        if ($text !== '') $items[] = $text;
    }
}
if (!$items) {
    $items = wp_list_pluck(sl_get_services(array('limit' => 8)), 'title');
}
if (!$items) return;

// The CSS translates the track by -50%, so the list is printed twice to loop
// seamlessly regardless of how many services there are.
$loop = array_merge($items, $items);
?>
<div class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <?php foreach ($loop as $text) : ?>
      <span><?php echo esc_html($text); ?></span><span class="sep">✦</span>
    <?php endforeach; ?>
  </div>
</div>
