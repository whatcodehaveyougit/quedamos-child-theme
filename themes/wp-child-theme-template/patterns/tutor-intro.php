<?php
/**
 * Title: Meet your tutor
 * Slug: quedamos/tutor-intro
 * Categories: about, featured, columns, call-to-action
 * Keywords: tutor, teacher, about, intro, bio, portrait
 * Viewport width: 1200
 * Description: An introduction band for the person behind the school — eyebrow, heading, intro paragraph, three emoji-led lines and a CTA on the left, a portrait with a quote card overlapping its lower-left on the right.
 *
 * Composed from core blocks only and left entirely unlocked: this is a plain
 * (unsynced) pattern, so every word in it — the eyebrow, the heading, the intro,
 * all three feature lines, the button label *and* its href, and the quote card —
 * is inserted once and then edited freely in Gutenberg. Nothing is baked into
 * markup an editor cannot reach.
 *
 * Every class name below is a styling hook for
 * assets/styles/scss/components/tutor-intro.scss — the structure and the look are
 * versioned here, the words are not. The copy that ships is placeholder: it
 * mirrors the band on the About page so the pattern previews as the real thing,
 * but it is there to be replaced.
 *
 * Every text node carries a hook even where the stylesheet has nothing to say
 * about it yet. Inserting a pattern *copies* this markup into the post, so a
 * class left off here can never be added later by editing the theme — it would
 * mean hand-editing every page the pattern was ever inserted on.
 *
 * The card over the portrait is deliberately ONE paragraph of quote text. The
 * band on the About page today stacks a big red figure over a smaller grey line,
 * which is two tiers of type doing the work of one sentence; the second tier goes
 * and the card carries a single quote instead.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The portrait.
 *
 * A theme file rather than a media library ID, for the reason patterns/404.php
 * records: an attachment ID is a per-environment value, so a pattern carrying one
 * inserts a broken image on every site but the one it was written on. The URL is
 * resolved through get_stylesheet_directory_uri() and never hardcoded
 * (writing-php §7).
 *
 * Deliberately NOT inc/helpers/author-identity.php's QUEDAMOS_AUTHOR_PHOTO. That
 * constant is the *byline avatar* — a 256px square that the schema and every post
 * byline resolve to — whereas this is a 900x1200 portrait sized for the band.
 * Binding the two would mean changing the author's photo silently changed every
 * tutor band on the site, and would put a square avatar in a 3:4 frame.
 */
$quedamos_tutor_portrait = get_stylesheet_directory_uri() . '/images/sara-carrillo-portrait.webp';

/**
 * The placeholder CTA target.
 *
 * home_url() rather than a literal, so the link resolves on localhost as well as
 * on live. /contact/ is a placeholder like the label beside it — an editor points
 * it wherever this particular insert should go.
 */
$quedamos_tutor_cta = home_url( '/contact/' );
?>

<!-- wp:group {"className":"tutor-intro","align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull tutor-intro">

	<!-- wp:columns {"verticalAlignment":"center","className":"tutor-intro__grid","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center tutor-intro__grid">

		<!-- wp:column {"verticalAlignment":"center","className":"tutor-intro__copy"} -->
		<div class="wp-block-column is-vertically-aligned-center tutor-intro__copy">

			<!-- wp:paragraph {"className":"tutor-intro__eyebrow"} -->
			<p class="tutor-intro__eyebrow">Meet your tutor</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"tutor-intro__title"} -->
			<h2 class="wp-block-heading tutor-intro__title">¡Hola! I'm Sara 👋</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"tutor-intro__lede"} -->
			<p class="tutor-intro__lede">A Spanish tutor living between Mallorca and Edinburgh — Spanish by birth, Scottish at heart. You'll be speaking Spanish from your very first lesson.</p>
			<!-- /wp:paragraph -->

			<!-- wp:list {"className":"tutor-intro__features"} -->
			<ul class="wp-block-list tutor-intro__features">
				<!-- wp:list-item -->
				<li>📍 Edinburgh &amp; Mallorca, in person</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>💻 Anywhere else, online over Zoom</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>🎓 Certified ELE tutor — all levels, A1 to DELE</li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->

			<!-- wp:buttons {"className":"tutor-intro__cta"} -->
			<div class="wp-block-buttons tutor-intro__cta">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $quedamos_tutor_cta ); ?>">Book your free trial lesson</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","className":"tutor-intro__media"} -->
		<div class="wp-block-column is-vertically-aligned-center tutor-intro__media">

			<!-- wp:image {"sizeSlug":"large","className":"tutor-intro__portrait"} -->
			<figure class="wp-block-image size-large tutor-intro__portrait"><img src="<?php echo esc_url( $quedamos_tutor_portrait ); ?>" alt="A portrait of the tutor" width="900" height="1200"/></figure>
			<!-- /wp:image -->

			<!-- wp:paragraph {"className":"tutor-intro__quote"} -->
			<p class="tutor-intro__quote">Replace this with a line worth quoting — what a lesson feels like, or why you teach.</p>
			<!-- /wp:paragraph -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->

<?php
unset( $quedamos_tutor_portrait, $quedamos_tutor_cta );
