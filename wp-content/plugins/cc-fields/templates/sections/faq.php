<?php
if (!defined('ABSPATH')) exit;

/**
 * Section: faq — accordion of questions and answers.
 * Fields (from class-cc-sections.php): bg, eyebrow, heading, intro, faqs[ question, answer (wysiwyg) ].
 *
 * Mirrors faq_section() in build.py: .faq__in > .faq__head (eyebrow, section-title,
 * section-intro, a .textlink call) + .faq__list of <details class="qa"> items.
 */

$bg      = isset($section_data['bg']) ? $section_data['bg'] : 'white';
$eyebrow = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading = isset($section_data['heading']) ? $section_data['heading'] : '';
$intro   = isset($section_data['intro']) ? $section_data['intro'] : '';
$faqs    = (isset($section_data['faqs']) && is_array($section_data['faqs'])) ? $section_data['faqs'] : array();

// On the ink (dark) background the heading flips to light.
$is_ink    = ($bg === 'ink');
$title_cls = 'section-title' . ($is_ink ? ' section-title--light' : '');

// Global phone for the "Call …" textlink (build.py uses PHONE / tel:TEL).
$company_phone = ccf_get_var('company_phone');
$company_tel   = ccf_get_var('company_tel');
$tel_href      = 'tel:' . preg_replace('/[^0-9+]/', '', $company_tel);

// Inline arrow icon — copied verbatim from ICON_ARROW in build.py.
$icon_arrow = '<svg viewBox="0 0 24 24" aria-hidden="true" width="16" height="16">'
    . '<path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" '
    . 'stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>';
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal">
  <div class="wrap faq__in">
    <div class="faq__head reveal">
      <?php if ($eyebrow !== '') : ?>
      <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
      <?php endif; ?>
      <?php if ($heading !== '') : ?>
      <h2 class="<?php echo esc_attr($title_cls); ?>"><?php echo esc_html($heading); ?></h2>
      <?php endif; ?>
      <?php if ($intro !== '') : ?>
      <p class="section-intro"><?php echo esc_html($intro); ?></p>
      <?php endif; ?>
      <?php if ($company_phone !== '') : ?>
      <a class="textlink" href="<?php echo esc_attr($tel_href); ?>"><?php echo esc_html('Call ' . $company_phone) . ' ' . $icon_arrow; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — static inline SVG icon ?></a>
      <?php endif; ?>
    </div>
    <div class="faq__list reveal">
      <?php foreach ($faqs as $faq) :
        $question = isset($faq['question']) ? $faq['question'] : '';
        $answer   = isset($faq['answer']) ? $faq['answer'] : '';
        if ($question === '' && $answer === '') { continue; }
      ?>
      <details class="qa"><summary><?php echo esc_html($question); ?><span class="qa__icon" aria-hidden="true"></span></summary>
      <div class="qa__body"><?php echo wp_kses_post(wpautop($answer)); ?></div></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
