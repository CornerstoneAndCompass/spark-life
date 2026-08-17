<?php
/**
 * Section: faq — accordion of questions and answers, plus FAQPage schema.
 * Behaviour (open/close, max-height animation) lives in assets/js/main.js.
 */
if (!defined('ABSPATH')) exit;

$bg        = sl_field($section_data, 'bg', 'white');
$kicker    = sl_field($section_data, 'kicker');
$heading   = sl_field($section_data, 'heading');
$highlight = sl_field($section_data, 'heading_highlight');
$intro     = sl_field($section_data, 'intro');
$is_ink    = ($bg === 'ink' || $bg === 'blue');

$faqs = (isset($section_data['faqs']) && is_array($section_data['faqs'])) ? $section_data['faqs'] : array();
if (!$faqs) return;

$schema = array();
?>
<section class="section section--<?php echo esc_attr($bg); ?> faq" id="faq">
  <div class="wrap">
    <div class="section__head">
      <?php if ($kicker !== '') : ?>
      <span class="kicker<?php echo $is_ink ? ' kicker--light' : ''; ?>"><?php echo esc_html($kicker); ?></span>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="section__title<?php echo $is_ink ? ' section__title--light' : ''; ?>"><?php echo wp_kses_post(sl_highlight($heading, $highlight)); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?><p class="section__sub"><?php echo esc_html($intro); ?></p><?php endif; ?>
    </div>
    <div class="acc">
      <?php foreach ($faqs as $faq) :
        $q = isset($faq['question']) ? $faq['question'] : '';
        $a = isset($faq['answer']) ? $faq['answer'] : '';
        if ($q === '') continue;
        $schema[] = array(
            '@type'          => 'Question',
            'name'           => wp_strip_all_tags($q),
            'acceptedAnswer' => array('@type' => 'Answer', 'text' => wp_strip_all_tags($a)),
        );
      ?>
      <div class="acc__item">
        <button class="acc__q" type="button" aria-expanded="false">
          <?php echo esc_html($q); ?>
          <span class="acc__ic" aria-hidden="true"></span>
        </button>
        <div class="acc__a"><?php echo wp_kses_post(wpautop($a)); ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php if ($schema) : ?>
<script type="application/ld+json"><?php echo wp_json_encode(array(
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => $schema,
), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<?php endif; ?>
