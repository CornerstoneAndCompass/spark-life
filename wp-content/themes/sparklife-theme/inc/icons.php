<?php
/**
 * Inline SVG icons — no icon-font dependency.
 *
 * sl_icon('name')             echoes/returns a 24×24 SVG using currentColor, so
 *                             size and colour are inherited from CSS.
 * sl_icon_names()             the full set (used to populate the Service icon picker).
 * sl_service_icon_for('…')    picks a sensible icon from a service title.
 *
 * The stroke icons mirror the ones drawn inline in the original static build.
 */
if (!defined('ABSPATH')) exit;

function sl_icon_names() {
    $s = 'fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"';
    return array(
        'bolt'       => '<path fill="currentColor" d="M13 2 4 14h6l-1 8 9-12h-6z"/>',
        'switchboard'=> '<rect ' . $s . ' x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path ' . $s . ' d="M8 8v3M12 8v3M16 8v3M6.5 15h11"/>',
        'downlight'  => '<path ' . $s . ' d="M12 3a6 6 0 0 0-3 11v3h6v-3a6 6 0 0 0-3-11Z"/><path ' . $s . ' d="M9.5 21h5"/>',
        'powerpoint' => '<rect ' . $s . ' x="4" y="3" width="16" height="18" rx="2"/><path ' . $s . ' d="M10 9h.01M14 9h.01M12 13v3"/>',
        'fan'        => '<circle ' . $s . ' cx="12" cy="12" r="2.2"/><path ' . $s . ' d="M12 9.8c0-3 .8-5.3 3-5.3s2.4 3.2 0 4.4M14.2 12c3 0 5.3.8 5.3 3s-3.2 2.4-4.4 0M9.8 12c-3 0-5.3-.8-5.3-3s3.2-2.4 4.4 0M12 14.2c0 3-.8 5.3-3 5.3s-2.4-3.2 0-4.4"/>',
        'ev'         => '<path ' . $s . ' d="M5 11h9v8H5z"/><path ' . $s . ' d="M14 7h3l3 4v8h-6"/><circle ' . $s . ' cx="8" cy="19" r="2"/><circle ' . $s . ' cx="17" cy="19" r="2"/>',
        'shield'     => '<path ' . $s . ' d="M12 3 5 6v6c0 4.4 3 8.3 7 9 4-.7 7-4.6 7-9V6Z"/><path ' . $s . ' d="m9.2 12 2 2 3.6-3.6"/>',
        'alarm'      => '<circle ' . $s . ' cx="12" cy="12" r="7"/><circle ' . $s . ' cx="12" cy="12" r="2"/><path ' . $s . ' d="M12 2v1.5M12 20.5V22M2 12h1.5M20.5 12H22"/>',
        'search'     => '<circle ' . $s . ' cx="11" cy="11" r="7"/><line ' . $s . ' x1="16.2" y1="16.2" x2="21" y2="21"/>',
        'house'      => '<path ' . $s . ' d="M3 10.5 12 3l9 7.5V20a1.5 1.5 0 0 1-1.5 1.5h-4V14h-7v7.5h-4A1.5 1.5 0 0 1 3 20Z"/>',
        'wrench'     => '<path ' . $s . ' d="M14.7 6.3a4 4 0 0 0-5.2 5.2L3 18v3h3l6.5-6.5a4 4 0 0 0 5.2-5.2l-2.9 2.9-2.2-.5-.5-2.2Z"/>',
        'heat'       => '<path ' . $s . ' d="M12 4a2.5 2.5 0 0 0-2.5 2.5v7a4 4 0 1 0 5 0v-7A2.5 2.5 0 0 0 12 4Z"/><line ' . $s . ' x1="12" y1="9" x2="12" y2="15"/>',
        'aircon'     => '<rect ' . $s . ' x="3" y="4.5" width="18" height="7" rx="2"/><path ' . $s . ' d="M6.5 8h11M7.5 14.5c0 1.6 1.3 1.6 1.3 3.2M12 14.5c0 1.6 1.3 1.6 1.3 3.2M16.5 14.5c0 1.6 1.3 1.6 1.3 3.2"/>',
        'data'       => '<rect ' . $s . ' x="3" y="4" width="18" height="12" rx="2"/><path ' . $s . ' d="M8 20h8M12 16v4"/>',
        'outdoor'    => '<path ' . $s . ' d="M12 3v3M12 21v-8"/><path ' . $s . ' d="M7 9.5 12 6l5 3.5-1.6 4.5H8.6Z"/>',
        'solar'      => '<path ' . $s . ' d="M4 15h16l-1.6-8H5.6Z"/><path ' . $s . ' d="M9.5 7 8.5 15M14.5 7l1 8M4.8 11h14.4M12 15v5M9 20h6"/>',
        'building'   => '<path ' . $s . ' d="M4 21V5.5L13 3v18"/><path ' . $s . ' d="M13 10h7v11M7.5 7.5h2M7.5 11h2M7.5 14.5h2M16 14h1M16 17.5h1"/>',
        'clock'      => '<circle ' . $s . ' cx="12" cy="12" r="9"/><path ' . $s . ' d="M12 7v5l3.5 2"/>',
        'phone'      => '<path fill="currentColor" d="M6.6 10.8a15.5 15.5 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.58 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1 11.4 11.4 0 0 0 .58 3.6 1 1 0 0 1-.25 1Z"/>',
        'mail'       => '<rect ' . $s . ' x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path ' . $s . ' d="m3 6 9 6 9-6"/>',
        'pin'        => '<path ' . $s . ' d="M20 10c0 5.5-8 12-8 12s-8-6.5-8-12a8 8 0 0 1 16 0Z"/><circle ' . $s . ' cx="12" cy="10" r="2.6"/>',
        'check'      => '<path ' . $s . ' stroke-width="2.6" d="m5 13 4 4L19 7"/>',
        'arrow'      => '<path ' . $s . ' stroke-width="2.4" d="M5 12h14M13 6l6 6-6 6"/>',
        'chevron'    => '<path ' . $s . ' stroke-width="2.6" d="m6 9 6 6 6-6"/>',
        'star'       => '<path fill="currentColor" d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/>',
        'facebook'   => '<path fill="currentColor" d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H7v3h3v7h3v-7h3l1-3h-4v-2c0-.6.4-1 1-1Z"/>',
        'instagram'  => '<path fill="currentColor" d="M12 7.5A4.5 4.5 0 1 0 16.5 12 4.5 4.5 0 0 0 12 7.5Zm0 7.4A2.9 2.9 0 1 1 14.9 12 2.9 2.9 0 0 1 12 14.9ZM16.7 6.3a1.05 1.05 0 1 0 1.05 1.05A1.05 1.05 0 0 0 16.7 6.3ZM21 8.3a5.6 5.6 0 0 0-1.5-3.8A5.6 5.6 0 0 0 15.7 3C14.2 2.95 9.8 2.95 8.3 3a5.6 5.6 0 0 0-3.8 1.5A5.6 5.6 0 0 0 3 8.3C2.95 9.8 2.95 14.2 3 15.7a5.6 5.6 0 0 0 1.5 3.8A5.6 5.6 0 0 0 8.3 21c1.5.05 5.9.05 7.4 0a5.6 5.6 0 0 0 3.8-1.5 5.6 5.6 0 0 0 1.5-3.8c.05-1.5.05-5.9 0-7.4Zm-2 9a3 3 0 0 1-1.7 1.7c-1.2.5-4 .35-5.3.35s-4.1.15-5.3-.35A3 3 0 0 1 5 17.3c-.5-1.2-.35-4-.35-5.3S4.5 7.9 5 6.7A3 3 0 0 1 6.7 5c1.2-.5 4-.35 5.3-.35s4.1-.15 5.3.35A3 3 0 0 1 19 6.7c.5 1.2.35 4 .35 5.3s.15 4.1-.35 5.3Z"/>',
    );
}

if (!function_exists('sl_icon')) {
    /** Return a 24×24 inline SVG for the named icon (falls back to 'bolt'). */
    function sl_icon($name, $size = 24) {
        $icons = sl_icon_names();
        if (!isset($icons[$name])) $name = 'bolt';
        return '<svg viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" aria-hidden="true">' . $icons[$name] . '</svg>';
    }
}

if (!function_exists('sl_service_icon_for')) {
    /** Guess an icon from a service title, so new services look right immediately. */
    function sl_service_icon_for($label) {
        $l = strtolower((string) $label);
        $map = array(
            'switchboard'  => 'switchboard',
            'safety switch'=> 'switchboard',
            'downlight'    => 'downlight',
            'led'          => 'downlight',
            'lighting'     => 'downlight',
            'powerpoint'   => 'powerpoint',
            'power point'  => 'powerpoint',
            'outlet'       => 'powerpoint',
            'rewir'        => 'powerpoint',
            'wiring'       => 'powerpoint',
            'ceiling fan'  => 'fan',
            'fan'          => 'fan',
            'ev '          => 'ev',
            'ev charger'   => 'ev',
            'electric vehicle' => 'ev',
            'smoke alarm'  => 'alarm',
            'alarm'        => 'alarm',
            'inspection'   => 'shield',
            'safety'       => 'shield',
            'fault'        => 'search',
            'repair'       => 'wrench',
            'emergency'    => 'bolt',
            'hot water'    => 'heat',
            'air condition'=> 'aircon',
            'cooling'      => 'aircon',
            'heating'      => 'heat',
            'data'         => 'data',
            'tv'           => 'data',
            'outdoor'      => 'outdoor',
            'garden'       => 'outdoor',
            'solar'        => 'solar',
            'battery'      => 'solar',
            'commercial'   => 'building',
            'office'       => 'building',
            'renovation'   => 'house',
            'new home'     => 'house',
        );
        foreach ($map as $needle => $icon) {
            if (strpos($l, $needle) !== false) return $icon;
        }
        return 'bolt';
    }
}
