<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: Marquee — scrolling lime credentials band.
 * Mirrors marquee() in build.py: .marquee > .marquee__track with each item as
 * <span>text</span><span class="marquee__dot">&bull;</span>, the run duplicated
 * twice for the seamless infinite scroll.
 *
 * Fields: items (repeater of { text }).
 */

$items = isset($section_data['items']) && is_array($section_data['items']) ? $section_data['items'] : array();

// Collect the credential strings.
$bits = array();
foreach ($items as $item) {
    if (!is_array($item)) continue;
    $text = isset($item['text']) ? trim($item['text']) : '';
    if ($text !== '') {
        $bits[] = $text;
    }
}

// Fall back to the six default credentials from build.py.
if (empty($bits)) {
    $bits = array(
        'Licensed Victorian Plumbers',
        'Fully Insured',
        'NDIS Registered Provider',
        'Working With Children Checked',
        'Residential & Commercial',
        'Family Business Since 2007',
    );
}

// Build one run of items (text + dot), then duplicate it for the loop.
$run = '';
foreach ($bits as $bit) {
    $run .= '<span>' . esc_html($bit) . '</span><span class="marquee__dot">&bull;</span>';
}
?>
<div class="marquee" aria-label="Our credentials"><div class="marquee__track"><?php echo $run . $run; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — each span text already passed through esc_html() above ?></div></div>
