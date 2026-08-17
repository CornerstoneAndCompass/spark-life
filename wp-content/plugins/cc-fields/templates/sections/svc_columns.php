<?php
if (!defined('ABSPATH')) exit;

/**
 * Service Columns — section head + 3-up grid of feature blocks.
 * Mirrors build.py services hub svc-columns (.svc-columns > .svc-block:
 * h3 title, optional p body, ul of items) + section head.
 * Fields (from class-cc-sections.php): bg, eyebrow, heading, intro,
 * columns[ title, body, items ]. 'items' is a textarea, one entry per line.
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$columns = (isset($section_data['columns']) && is_array($section_data['columns'])) ? $section_data['columns'] : array();
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap">
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
    <div class="svc-columns">
      <?php foreach ($columns as $col) :
        $title = isset($col['title']) ? $col['title'] : '';
        $body  = isset($col['body']) ? $col['body'] : '';
        $raw   = isset($col['items']) ? $col['items'] : '';
        // Split the items textarea on newlines, trim, drop blanks.
        $items = array();
        if ($raw !== '') {
          foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line !== '') {
              $items[] = $line;
            }
          }
        }
      ?>
      <div class="svc-block reveal">
        <?php if ($title !== '') : ?>
        <h3><?php echo esc_html($title); ?></h3>
        <?php endif; ?>
        <?php if ($body !== '') : ?>
        <p><?php echo esc_html($body); ?></p>
        <?php endif; ?>
        <?php if (!empty($items)) : ?>
        <ul>
          <?php foreach ($items as $item) : ?>
          <li><?php echo esc_html($item); ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
