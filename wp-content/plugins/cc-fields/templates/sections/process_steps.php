<?php if (!defined('ABSPATH')) exit;
/**
 * Section: Process Steps
 * Numbered step cards (01 / 02 / 03) in a 3-up grid.
 * Mirrors process_section() in calltheplumberguy/build.py.
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'white';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$steps   = isset($section_data['steps']) && is_array($section_data['steps']) ? $section_data['steps'] : array();

// Honour bg: ink variant lights up the eyebrow and section title.
$is_ink     = ($bg === 'ink');
$eyebrow_cls = 'eyebrow' . ($is_ink ? ' eyebrow--lime' : '');
$title_cls   = 'section-title' . ($is_ink ? ' section-title--light' : '');

// build.py defaults.
if (empty($eyebrow)) { $eyebrow = 'How it works'; }
if (empty($heading)) { $heading = 'Booking a plumber, made simple.'; }

// build.py default 3 steps when the repeater is empty.
if (empty($steps)) {
    $steps = array(
        array('title' => 'Call or book online', 'desc' => "Tell us what's going on. We'll talk it through and lock in a time that suits you."),
        array('title' => 'Honest, upfront advice', 'desc' => 'Your plumber assesses the job and explains your options plainly, with a fair price.'),
        array('title' => 'Done properly', 'desc' => 'Quality workmanship, tidy work and friendly service. We leave things better than we found them.'),
    );
}
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
    <div class="section__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="<?php echo esc_attr($eyebrow_cls); ?>"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="section-intro"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
    </div>
    <div class="process__steps">
      <?php foreach ($steps as $i => $step) :
        $t = isset($step['title']) ? $step['title'] : '';
        $d = isset($step['desc']) ? $step['desc'] : '';
        $num = sprintf('%02d', $i + 1);
      ?>
      <div class="step reveal"><span class="step__num"><?php echo esc_html($num); ?></span>
        <h3><?php echo esc_html($t); ?></h3><p><?php echo esc_html($d); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
