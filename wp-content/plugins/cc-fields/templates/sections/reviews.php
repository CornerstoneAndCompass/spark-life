<?php if (!defined('ABSPATH')) exit;
/**
 * Section: Reviews
 * Google score plus a featured review and supporting review cards.
 * Mirrors reviews_section() in calltheplumberguy/build.py.
 */

$bg          = isset($section_data['bg']) ? $section_data['bg'] : 'paper';
$eyebrow     = isset($section_data['eyebrow']) ? $section_data['eyebrow'] : '';
$heading     = isset($section_data['heading']) ? $section_data['heading'] : '';
$score       = isset($section_data['score']) ? $section_data['score'] : '';
$score_count = isset($section_data['score_count']) ? $section_data['score_count'] : '';
$reviews     = isset($section_data['reviews']) && is_array($section_data['reviews']) ? $section_data['reviews'] : array();

// score / score_count fall back to the global vars, then build.py defaults.
if ($score === '') {
    $score = ccf_get_var('review_score');
    if ($score === '' || $score === null) { $score = '5.0'; }
}
if ($score_count === '') {
    $score_count = ccf_get_var('review_count');
    if ($score_count === '' || $score_count === null) { $score_count = '36'; }
}

// build.py defaults.
if (empty($eyebrow)) { $eyebrow = 'Reviews'; }
if (empty($heading)) { $heading = 'Trusted by homeowners across Melbourne.'; }

// build.py default reviews when the repeater is empty.
if (empty($reviews)) {
    $reviews = array(
        array('text' => "David and Blake deserve the highest accolade for the outstanding job they did at our home in fixing and repairing our plumbing issues. Their communication and workmanship is of the highest standard, and I have no hesitation in recommending their services.", 'name' => 'Josephine Addamo', 'subtitle' => 'Google review', 'featured' => '1'),
        array('text' => "I recently had David complete some work at my house and couldn't be happier. He arrived in time and carried out the repairs promptly. Will recommend him to anyone I know who needs plumbing work.", 'name' => 'Luke Owen', 'subtitle' => 'Google review', 'featured' => '0'),
        array('text' => "Very good, highly trusted plumbing company. I will highly recommend this company for any job. Very satisfied with the job David completed.", 'name' => 'Ran Malla', 'subtitle' => 'Google review', 'featured' => '0'),
        array('text' => "David was great. I was able to book a time for him to come out through his website. He installed a range hood vent through the roof. Really happy with the work he did. Would definitely use him again.", 'name' => 'Sandy Currie', 'subtitle' => 'Google review', 'featured' => '0'),
    );
}
?>
<section class="section section--<?php echo esc_attr($bg); ?> reveal" id="reviews">
  <div class="wrap">
    <div class="reviews__head reveal">
      <div>
        <?php if ($eyebrow !== '') : ?>
        <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
        <?php endif; ?>
        <?php if ($heading !== '') : ?>
        <h2 class="section-title"><?php echo esc_html($heading); ?></h2>
        <?php endif; ?>
      </div>
      <div class="reviews__score" aria-label="Rated <?php echo esc_attr($score); ?> out of 5">
        <span class="reviews__score-num"><?php echo esc_html($score); ?></span>
        <span class="stars stars--lg" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
        <span class="reviews__score-txt">From <?php echo esc_html($score_count); ?> Google reviews</span>
      </div>
    </div>
    <div class="reviews__grid">
      <?php foreach ($reviews as $review) :
        $text     = isset($review['text']) ? $review['text'] : '';
        $name     = isset($review['name']) ? $review['name'] : '';
        $subtitle = isset($review['subtitle']) ? $review['subtitle'] : '';
        $featured = !empty($review['featured']);
        $cls      = $featured ? 'review review--feature reveal' : 'review reveal';
      ?>
      <blockquote class="<?php echo esc_attr($cls); ?>">
      <span class="stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
      <p>&ldquo;<?php echo esc_html($text); ?>&rdquo;</p>
      <footer><strong><?php echo esc_html($name); ?></strong><span><?php echo esc_html($subtitle); ?></span></footer>
    </blockquote>
      <?php endforeach; ?>
    </div>
  </div>
</section>
