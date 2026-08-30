<?php if (!defined('ABSPATH')) exit;
/**
 * Section: Service Areas
 * Dark section listing service regions and their suburbs.
 * Mirrors areas_section() in calltheplumberguy/build.py.
 * Fields (from class-cc-sections.php): bg (default ink), eyebrow, heading,
 * intro, columns[ title, suburbs (textarea, one suburb per line) ].
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'ink';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$columns = (isset($section_data['columns']) && is_array($section_data['columns'])) ? $section_data['columns'] : array();

// Honour bg: ink (the default) lights the eyebrow + section title.
$is_ink      = ($bg === 'ink');
$eyebrow_cls = 'eyebrow' . ($is_ink ? ' eyebrow--lime' : '');
$title_cls   = 'section-title' . ($is_ink ? ' section-title--light' : '');

// Global phone for the call button. House style is no em dashes in copy, so
// the separator here is a comma; a site wanting different wording overrides
// this template from its theme rather than editing the plugin.
$company_phone = ccf_get_var('company_phone');
$company_tel   = ccf_get_var('company_tel');
$tel_href      = 'tel:' . preg_replace('/[^0-9+]/', '', $company_tel);

// build.py defaults (used when the section is left empty in the editor).
if ($eyebrow === '') { $eyebrow = 'Service areas'; }
if ($heading === '') { $heading = 'Plumbers across metropolitan Melbourne.'; }
if ($intro === '')   { $intro = "We look after residential and commercial properties through the inner-east, northern, eastern and south-eastern suburbs, and plenty in between. If your suburb isn't listed, call us anyway."; }
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal" id="areas">
  <div class="wrap areas__in">
    <div class="areas__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="<?php echo esc_attr($eyebrow_cls); ?>"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="areas__lead"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
      <?php if ($company_phone !== '') : ?>
      <a class="btn btn--primary" href="<?php echo esc_attr($tel_href); ?>">Check your suburb, <?php echo esc_html($company_phone); ?></a>
      <?php endif; ?>
    </div>
    <div class="areas__cols reveal">
      <?php foreach ($columns as $col) :
        $title   = isset($col['title']) ? $col['title'] : '';
        $suburbs = isset($col['suburbs']) ? $col['suburbs'] : '';
        // Split the suburbs textarea on newlines into <li> items.
        $lines = preg_split('/\r\n|\r|\n/', (string) $suburbs);
        $lines = array_filter(array_map('trim', $lines), 'strlen');
      ?>
      <div class="areacol">
        <?php if ($title !== '') : ?>
        <h3 class="areacol__title"><?php echo esc_html($title); ?></h3>
        <?php endif; ?>
        <ul>
          <?php foreach ($lines as $suburb) : ?>
          <li><?php echo esc_html($suburb); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
