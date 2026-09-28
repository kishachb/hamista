<?php
/**
 * Developer style guide (?hamista-styleguide=1, users with edit_theme_options only).
 *
 * Shows the §4.1 tokens and every §4.2 primitive, once in an RTL / Persian section and once in an
 * LTR / English section, for visual QA in both colour modes. Sample copy is fixed demo content,
 * so it is not translatable.
 *
 * @package Hamista\Theme
 */

defined( 'ABSPATH' ) || exit;

use Hamista\Theme\Icons;

$hamista_sg_copy = [
	'fa' => [
		'dir'         => 'rtl',
		'heading'     => 'مؤلفه‌ها — راست‌به‌چپ (فارسی)',
		'eyebrow'     => 'استودیوی خلاق کسب‌وکار',
		'title'       => 'هر آنچه برند شما',
		'highlight'   => 'برای رشد',
		'title_end'   => 'نیاز دارد',
		'subtitle'    => 'افزونه، قالب، نرم‌افزار و خدمات برندینگ؛ طراحی‌شده با دقت، ساخته‌شده برای سرعت.',
		'body'        => 'هامیستا یک استودیوی خلاق است که ابزارهای دیجیتال حرفه‌ای را با طراحی دقیق و پشتیبانی واقعی ارائه می‌کند. متن فارسی با فاصله‌ی سطر سخاوتمندانه و بدون فاصله‌گذاری حروف نمایش داده می‌شود تا اتصال حروف حفظ شود.',
		'buttons'     => [ 'شروع پروژه', 'تیره', 'ثانویه', 'شبح', 'پیوند متنی', 'کوچک', 'بزرگ', 'در حال ارسال', 'غیرفعال', 'تمام‌عرض' ],
		'badges'      => [ 'فعال', 'در انتظار', 'منقضی', 'اطلاعات', 'خنثی', 'جدید' ],
		'card_title'  => 'طراحی لوگو و هویت بصری',
		'card_text'   => 'هویتی ماندگار که داستان برند شما را در هر نقطه‌ی تماس روایت می‌کند.',
		'card_price'  => 'از ۴٬۹۰۰٬۰۰۰ تومان',
		'card_more'   => 'جزئیات',
		'card_labels' => [ 'پیش‌فرض (سایه‌دار)', 'حاشیه‌دار', 'شیشه‌ای', 'ویژه (حاشیه‌ی گرادیانی)' ],
		'form'        => [ 'نام و نام خانوادگی', 'مثلاً سارا محمدی', 'ایمیل', 'ما ایمیل شما را منتشر نمی‌کنیم.', 'لطفاً یک ایمیل معتبر وارد کنید.', 'نوع پروژه', [ 'طراحی لوگو', 'توسعه‌ی وب‌سایت', 'افزونه‌ی اختصاصی' ], 'توضیحات', 'چند خط درباره‌ی پروژه بنویسید…', 'شرایط و قوانین را می‌پذیرم', 'خبرنامه را دریافت کنم', 'فایل‌ها را اینجا رها کنید یا برای انتخاب کلیک کنید', 'JPG، PNG یا PDF تا ۱۰ مگابایت', 'ارسال درخواست' ],
		'alerts'      => [ 'سفارش شما با موفقیت ثبت شد.', 'پرداخت انجام نشد. لطفاً دوباره تلاش کنید.', 'لایسنس شما تا ۷ روز دیگر منقضی می‌شود.', 'نسخه‌ی ۲٫۴ منتشر شد؛ تغییرات را ببینید.', 'بستن' ],
		'table'       => [ [ 'محصول', 'نسخه', 'وضعیت', 'انقضا' ], [ [ 'افزونه‌ی فرم‌ساز', '۲٫۴٫۱', 'فعال', '۱۲ اسفند ۱۴۰۵' ], [ 'قالب فروشگاهی', '۱٫۹٫۰', 'در انتظار', '۳ خرداد ۱۴۰۶' ], [ 'اپلیکیشن حسابداری', '۳٫۰٫۲', 'منقضی', '۲۰ مهر ۱۴۰۵' ] ] ],
		'tabs'        => [ 'توضیحات', 'تغییرات', 'مستندات', 'محتوای زبانه‌ی توضیحات: ویژگی‌ها، پیش‌نیازها و راهنمای نصب.' ],
		'steps'       => [ 'ثبت سفارش', 'پرداخت', 'در حال انجام', 'تحویل' ],
		'code'        => [ 'کلید لایسنس شما:', 'کپی کلید' ],
		'stats'       => [ [ 'درآمد این ماه', '۱۲٫۴ میلیون', '٪۱۸+ نسبت به ماه قبل', 'wallet' ], [ 'لایسنس‌های فعال', '۳۴۸', '۲۱ فعال‌سازی امروز', 'key' ], [ 'تیکت‌های باز', '۷', 'میانگین پاسخ ۲ ساعت', 'message-circle' ] ],
		'empty'       => [ 'هنوز تیکتی ندارید', 'هر وقت سؤالی داشتید، تیم پشتیبانی کنار شماست.', 'ارسال تیکت' ],
		'thread'      => [ [ 'سارا محمدی', '۲ ساعت پیش', 'سلام، بعد از به‌روزرسانی به نسخه‌ی ۲٫۴ فرم تماس نمایش داده نمی‌شود.', 'screenshot.png' ], [ 'پشتیبانی هامیستا', '۱ ساعت پیش', 'سلام سارا جان، مشکل پیدا شد؛ نسخه‌ی ۲٫۴٫۱ را نصب کنید.', 'پشتیبان' ] ],
		'initials'    => [ 'س', 'ع', 'م' ],
		'dialog'      => [ 'حذف این دامنه؟', 'دامنه‌ی example.com از لایسنس جدا می‌شود و یک فعال‌سازی آزاد خواهد شد.', 'حذف دامنه', 'انصراف' ],
		'section'     => 'بخش خاکستری (hm-section--muted)',
	],
	'en' => [
		'dir'         => 'ltr',
		'heading'     => 'Primitives — left to right (English)',
		'eyebrow'     => 'Creative business studio',
		'title'       => 'Everything your brand',
		'highlight'   => 'needs to grow',
		'title_end'   => '',
		'subtitle'    => 'Plugins, themes, software and branding services — designed with care, built for speed.',
		'body'        => 'HAMISTA is a creative studio shipping professional digital tools with meticulous design and real support. English text sets tighter, with a slight negative tracking on display headings only.',
		'buttons'     => [ 'Start a project', 'Dark', 'Secondary', 'Ghost', 'Text link', 'Small', 'Large', 'Sending', 'Disabled', 'Full width' ],
		'badges'      => [ 'Active', 'Pending', 'Expired', 'Info', 'Neutral', 'New' ],
		'card_title'  => 'Logo and visual identity',
		'card_text'   => 'A lasting identity that tells your brand story at every touchpoint.',
		'card_price'  => 'From 4,900,000 IRT',
		'card_more'   => 'Details',
		'card_labels' => [ 'Default (elevated)', 'Bordered', 'Glass', 'Highlight (gradient border)' ],
		'form'        => [ 'Full name', 'e.g. Sarah Miller', 'Email', 'We never publish your email.', 'Please enter a valid email.', 'Project type', [ 'Logo design', 'Website development', 'Custom plugin' ], 'Details', 'Tell us about the project…', 'I accept the terms and conditions', 'Send me the newsletter', 'Drop files here or click to choose', 'JPG, PNG or PDF up to 10 MB', 'Send request' ],
		'alerts'      => [ 'Your order was placed successfully.', 'The payment failed. Please try again.', 'Your license expires in 7 days.', 'Version 2.4 is out — see what changed.', 'Dismiss' ],
		'table'       => [ [ 'Product', 'Version', 'Status', 'Expires' ], [ [ 'Form builder plugin', '2.4.1', 'Active', '3 Mar 2027' ], [ 'Store theme', '1.9.0', 'Pending', '24 May 2027' ], [ 'Accounting app', '3.0.2', 'Expired', '12 Oct 2026' ] ] ],
		'tabs'        => [ 'Description', 'Changelog', 'Documentation', 'Description tab content: features, requirements and the installation guide.' ],
		'steps'       => [ 'Order placed', 'Payment', 'In progress', 'Delivered' ],
		'code'        => [ 'Your license key:', 'Copy key' ],
		'stats'       => [ [ 'Revenue this month', '12.4M', '+18% vs last month', 'wallet' ], [ 'Active licenses', '348', '21 activations today', 'key' ], [ 'Open tickets', '7', 'Median reply 2 h', 'message-circle' ] ],
		'empty'       => [ 'No tickets yet', 'Whenever you have a question, our support team is here.', 'Open a ticket' ],
		'thread'      => [ [ 'Sarah Miller', '2 hours ago', 'Hi, after updating to 2.4 the contact form no longer shows.', 'screenshot.png' ], [ 'HAMISTA Support', '1 hour ago', 'Hi Sarah, found it — please install 2.4.1.', 'Staff' ] ],
		'initials'    => [ 'S', 'A', 'M' ],
		'dialog'      => [ 'Remove this domain?', 'example.com will be detached from the license and one activation freed.', 'Remove domain', 'Cancel' ],
		'section'     => 'Muted section (hm-section--muted)',
	],
];

$hamista_icon = static function ( string $name ): string {
	return Icons::get( $name );
};

$hamista_sg = static function ( string $lang, array $t ) use ( $hamista_icon ): void {
	$status = [ 'success', 'warning', 'danger', 'info', 'neutral', 'brand' ];
	?>
	<section class="hm-section hm-sg" dir="<?php echo esc_attr( $t['dir'] ); ?>" lang="<?php echo esc_attr( $lang ); ?>">
		<div class="hm-container hm-stack" style="--hm-stack-gap:0">
			<h2 class="hm-sg-title"><?php echo esc_html( $t['heading'] ); ?></h2>

			<div class="hm-sg-block">
				<h3>Headings · hm-section-head · hm-gradient-text</h3>
				<header class="hm-section-head">
					<p class="hm-section-head__eyebrow"><?php echo esc_html( $t['eyebrow'] ); ?></p>
					<h2 class="hm-section-head__title"><?php echo esc_html( $t['title'] ); ?> <span class="hm-gradient-text"><?php echo esc_html( $t['highlight'] ); ?></span> <?php echo esc_html( $t['title_end'] ); ?></h2>
					<p class="hm-section-head__subtitle"><?php echo esc_html( $t['subtitle'] ); ?></p>
				</header>
				<p class="hm-sg-measure"><?php echo esc_html( $t['body'] ); ?> <a href="#top"><?php echo esc_html( $t['card_more'] ); ?></a></p>
				<div class="hm-sg-type">
					<?php foreach ( [ '6xl', '5xl', '4xl', '3xl', '2xl', 'xl', 'lg', 'base', 'sm', 'xs' ] as $size ) : ?>
						<div><code>--hm-text-<?php echo esc_html( $size ); ?></code><span style="font-size:var(--hm-text-<?php echo esc_attr( $size ); ?>)"><?php echo esc_html( $t['card_title'] ); ?></span></div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="hm-sg-block">
				<h3>Buttons · hm-btn</h3>
				<div class="hm-cluster">
					<a class="hm-btn hm-btn--primary" href="#top"><?php echo esc_html( $t['buttons'][0] ); ?> <?php echo $hamista_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<button type="button" class="hm-btn hm-btn--dark"><?php echo esc_html( $t['buttons'][1] ); ?></button>
					<button type="button" class="hm-btn hm-btn--secondary"><?php echo esc_html( $t['buttons'][2] ); ?></button>
					<button type="button" class="hm-btn hm-btn--ghost"><?php echo esc_html( $t['buttons'][3] ); ?></button>
					<a class="hm-btn hm-btn--link" href="#top"><?php echo esc_html( $t['buttons'][4] ); ?></a>
				</div>
				<div class="hm-cluster">
					<button type="button" class="hm-btn hm-btn--primary hm-btn--sm"><?php echo esc_html( $t['buttons'][5] ); ?></button>
					<button type="button" class="hm-btn hm-btn--primary hm-btn--lg"><?php echo esc_html( $t['buttons'][6] ); ?></button>
					<button type="button" class="hm-btn hm-btn--primary is-loading" aria-busy="true"><?php echo esc_html( $t['buttons'][7] ); ?></button>
					<button type="button" class="hm-btn hm-btn--secondary" disabled><?php echo esc_html( $t['buttons'][8] ); ?></button>
					<button type="button" class="hm-btn hm-btn--secondary hm-btn--icon"><span class="hm-sr-only">Search</span><?php echo $hamista_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon"><span class="hm-sr-only">Next</span><?php echo $hamista_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
				<div style="max-width:24rem"><button type="button" class="hm-btn hm-btn--primary hm-btn--block"><?php echo esc_html( $t['buttons'][9] ); ?></button></div>
			</div>

			<div class="hm-sg-block">
				<h3>Badges · hm-badge</h3>
				<div class="hm-cluster">
					<?php foreach ( $t['badges'] as $index => $label ) : ?>
						<span class="hm-badge hm-badge--<?php echo esc_attr( $status[ $index ] ); ?>"><?php echo esc_html( $label ); ?></span>
					<?php endforeach; ?>
					<span class="hm-badge">hm-badge</span>
				</div>
			</div>

			<div class="hm-sg-block">
				<h3>Cards · hm-card (+ --hover)</h3>
				<div class="hm-grid hm-grid--4 hm-sg-backdrop">
					<?php foreach ( [ '', 'hm-card--bordered', 'hm-card--glass', 'hm-card--highlight' ] as $index => $variant ) : ?>
						<article class="hm-card hm-card--hover <?php echo esc_attr( $variant ); ?>">
							<div class="hm-card__media hm-sg-media"></div>
							<div class="hm-card__body">
								<span class="hm-badge hm-badge--<?php echo 3 === $index ? 'brand' : 'neutral'; ?>"><?php echo esc_html( $t['card_labels'][ $index ] ); ?></span>
								<h4 class="hm-card__title"><a href="#top"><?php echo esc_html( $t['card_title'] ); ?></a></h4>
								<p class="hm-card__text"><?php echo esc_html( $t['card_text'] ); ?></p>
							</div>
							<div class="hm-card__footer">
								<strong><?php echo esc_html( $t['card_price'] ); ?></strong>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="hm-sg-block">
				<h3>Forms · hm-form · hm-field · hm-input / hm-select / hm-textarea · hm-check · hm-dropzone</h3>
				<?php $f = $t['form']; ?>
				<form class="hm-form hm-sg-measure" action="#top">
					<div class="hm-form-row">
						<div class="hm-field">
							<label class="hm-field__label" for="sg-name-<?php echo esc_attr( $lang ); ?>"><?php echo esc_html( $f[0] ); ?> <abbr class="hm-required" title="required">*</abbr></label>
							<input class="hm-input" id="sg-name-<?php echo esc_attr( $lang ); ?>" type="text" placeholder="<?php echo esc_attr( $f[1] ); ?>">
						</div>
						<div class="hm-field">
							<label class="hm-field__label" for="sg-mail-<?php echo esc_attr( $lang ); ?>"><?php echo esc_html( $f[2] ); ?></label>
							<input class="hm-input" id="sg-mail-<?php echo esc_attr( $lang ); ?>" type="email" value="sara@" dir="ltr" aria-invalid="true" aria-describedby="sg-mail-err-<?php echo esc_attr( $lang ); ?>">
							<p class="hm-field__error" id="sg-mail-err-<?php echo esc_attr( $lang ); ?>"><?php echo $hamista_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $f[4] ); ?></p>
						</div>
					</div>
					<div class="hm-field">
						<label class="hm-field__label" for="sg-type-<?php echo esc_attr( $lang ); ?>"><?php echo esc_html( $f[5] ); ?></label>
						<select class="hm-select" id="sg-type-<?php echo esc_attr( $lang ); ?>">
							<?php foreach ( $f[6] as $option ) : ?>
								<option><?php echo esc_html( $option ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="hm-field__help"><?php echo esc_html( $f[3] ); ?></p>
					</div>
					<div class="hm-field">
						<label class="hm-field__label" for="sg-text-<?php echo esc_attr( $lang ); ?>"><?php echo esc_html( $f[7] ); ?></label>
						<textarea class="hm-textarea" id="sg-text-<?php echo esc_attr( $lang ); ?>" rows="3" placeholder="<?php echo esc_attr( $f[8] ); ?>"></textarea>
					</div>
					<label class="hm-check"><input type="checkbox" checked> <span><?php echo esc_html( $f[9] ); ?></span></label>
					<label class="hm-check"><input type="radio" name="sg-radio-<?php echo esc_attr( $lang ); ?>"> <span><?php echo esc_html( $f[10] ); ?></span></label>
					<label class="hm-dropzone">
						<?php echo $hamista_icon( 'arrow-up' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<strong><?php echo esc_html( $f[11] ); ?></strong>
						<span><?php echo esc_html( $f[12] ); ?></span>
						<input class="hm-sr-only" type="file" multiple>
					</label>
					<div><button type="submit" class="hm-btn hm-btn--primary"><?php echo esc_html( $f[13] ); ?></button></div>
				</form>
			</div>

			<div class="hm-sg-block">
				<h3>Alerts · hm-alert</h3>
				<div class="hm-stack hm-sg-measure">
					<?php foreach ( [ [ 'success', 'check' ], [ 'error', 'x' ], [ 'warning', 'clock' ], [ 'info', 'bell' ] ] as $index => [ $type, $icon ] ) : ?>
						<div class="hm-alert hm-alert--<?php echo esc_attr( $type ); ?>" role="<?php echo 'error' === $type ? 'alert' : 'status'; ?>">
							<?php echo $hamista_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<p><?php echo esc_html( $t['alerts'][ $index ] ); ?></p>
							<button type="button" class="hm-btn hm-btn--ghost hm-btn--icon hm-btn--sm" data-hm-dismiss><span class="hm-sr-only"><?php echo esc_html( $t['alerts'][4] ); ?></span><?php echo $hamista_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="hm-sg-block">
				<h3>Tables · hm-table-wrap · hm-table (stacks below 40em)</h3>
				<div class="hm-table-wrap">
					<table class="hm-table">
						<thead><tr>
						<?php
						foreach ( $t['table'][0] as $heading ) :
							?>
							<th scope="col"><?php echo esc_html( $heading ); ?></th><?php endforeach; ?></tr></thead>
						<tbody>
							<?php foreach ( $t['table'][1] as $row_index => $row ) : ?>
								<tr>
									<?php foreach ( $row as $cell_index => $cell ) : ?>
										<td data-label="<?php echo esc_attr( $t['table'][0][ $cell_index ] ); ?>">
											<?php if ( 2 === $cell_index ) : ?>
												<span class="hm-badge hm-badge--<?php echo esc_attr( [ 'success', 'warning', 'danger' ][ $row_index ] ); ?>"><?php echo esc_html( $cell ); ?></span>
											<?php elseif ( 1 === $cell_index ) : ?>
												<span class="hm-code"><?php echo esc_html( $cell ); ?></span>
											<?php else : ?>
												<?php echo esc_html( $cell ); ?>
											<?php endif; ?>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="hm-sg-grid2">
				<div class="hm-sg-block">
					<h3>Tabs · hm-tabs</h3>
					<div class="hm-tabs" data-hm-tabs>
						<div class="hm-tabs__list" role="tablist">
							<button type="button" class="hm-tabs__tab" role="tab" aria-selected="true"><?php echo esc_html( $t['tabs'][0] ); ?></button>
							<button type="button" class="hm-tabs__tab" role="tab" aria-selected="false"><?php echo esc_html( $t['tabs'][1] ); ?></button>
							<button type="button" class="hm-tabs__tab" role="tab" aria-selected="false"><?php echo esc_html( $t['tabs'][2] ); ?></button>
						</div>
						<div class="hm-tabs__panel" role="tabpanel"><p><?php echo esc_html( $t['tabs'][3] ); ?></p></div>
					</div>
				</div>
				<div class="hm-sg-block">
					<h3>Code · hm-code</h3>
					<p><?php echo esc_html( $t['code'][0] ); ?></p>
					<p><span class="hm-code">HMST-7K2QF-9XH4M-B3R8T-WZ5NC <button type="button" class="hm-btn hm-btn--ghost hm-btn--icon hm-btn--sm" data-hm-copy="HMST-7K2QF-9XH4M-B3R8T-WZ5NC"><span class="hm-sr-only"><?php echo esc_html( $t['code'][1] ); ?></span><?php echo $hamista_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button></span></p>
				</div>
			</div>

			<div class="hm-sg-block">
				<h3>Steps · hm-steps</h3>
				<ol class="hm-steps">
					<?php foreach ( $t['steps'] as $index => $step ) : ?>
						<li class="hm-steps__item<?php echo $index < 2 ? ' is-done' : ( 2 === $index ? ' is-current' : '' ); ?>"<?php echo 2 === $index ? ' aria-current="step"' : ''; ?>><?php echo esc_html( $step ); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>

			<div class="hm-sg-block">
				<h3>Stats · hm-stat</h3>
				<div class="hm-grid hm-grid--3">
					<?php foreach ( $t['stats'] as [ $label, $value, $meta, $icon ] ) : ?>
						<div class="hm-stat">
							<span class="hm-stat__icon"><?php echo $hamista_icon( Icons::has( $icon ) || function_exists( 'hamista_icon' ) ? $icon : 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="hm-stat__label"><?php echo esc_html( $label ); ?></span>
							<span class="hm-stat__value"><?php echo esc_html( $value ); ?></span>
							<span class="hm-stat__meta"><?php echo esc_html( $meta ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="hm-sg-grid2">
				<div class="hm-sg-block">
					<h3>Empty · hm-empty</h3>
					<div class="hm-empty">
						<span class="hm-empty__icon"><?php echo $hamista_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<h4 class="hm-empty__title"><?php echo esc_html( $t['empty'][0] ); ?></h4>
						<p class="hm-empty__text"><?php echo esc_html( $t['empty'][1] ); ?></p>
						<a class="hm-btn hm-btn--primary" href="#top"><?php echo esc_html( $t['empty'][2] ); ?></a>
					</div>
				</div>
				<div class="hm-sg-block">
					<h3>Thread · hm-thread · Avatar · hm-avatar</h3>
					<ol class="hm-thread">
						<?php foreach ( $t['thread'] as $index => [ $name, $time, $message, $extra ] ) : ?>
							<li class="hm-thread__item<?php echo 1 === $index ? ' hm-thread__item--staff' : ''; ?>">
								<div class="hm-thread__meta">
									<span class="hm-avatar hm-avatar--sm"><?php echo esc_html( $t['initials'][ $index ] ); ?></span>
									<strong><?php echo esc_html( $name ); ?></strong>
									<?php if ( 1 === $index ) : ?>
										<span class="hm-badge hm-badge--brand"><?php echo esc_html( $extra ); ?></span>
									<?php endif; ?>
									<time><?php echo esc_html( $time ); ?></time>
								</div>
								<div class="hm-thread__body"><p><?php echo esc_html( $message ); ?></p></div>
								<?php if ( 0 === $index ) : ?>
									<ul class="hm-thread__files"><li><a href="#top"><?php echo $hamista_icon( 'external-link' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $extra ); ?></a></li></ul>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
					<div class="hm-cluster">
						<span class="hm-avatar hm-avatar--sm"><?php echo esc_html( $t['initials'][0] ); ?></span>
						<span class="hm-avatar"><?php echo esc_html( $t['initials'][1] ); ?></span>
						<span class="hm-avatar hm-avatar--lg"><?php echo esc_html( $t['initials'][2] ); ?></span>
					</div>
				</div>
			</div>

			<div class="hm-sg-grid2">
				<div class="hm-sg-block">
					<h3>Pagination · hm-pagination</h3>
					<nav class="hm-pagination" aria-label="Pagination">
						<a class="prev page-numbers" href="#top"><?php echo $hamista_icon( 'arrow-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span class="hm-sr-only">Previous</span></a>
						<a class="page-numbers" href="#top"><?php echo esc_html( 'fa' === $lang ? '۱' : '1' ); ?></a>
						<span aria-current="page" class="page-numbers current"><?php echo esc_html( 'fa' === $lang ? '۲' : '2' ); ?></span>
						<a class="page-numbers" href="#top"><?php echo esc_html( 'fa' === $lang ? '۳' : '3' ); ?></a>
						<span class="page-numbers dots">…</span>
						<a class="page-numbers" href="#top"><?php echo esc_html( 'fa' === $lang ? '۸' : '8' ); ?></a>
						<a class="next page-numbers" href="#top"><span class="hm-sr-only">Next</span><?php echo $hamista_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					</nav>
				</div>
				<div class="hm-sg-block">
					<h3>Dialog · hm-dialog (shown inline)</h3>
					<dialog class="hm-dialog hm-sg-dialog" open aria-labelledby="sg-dialog-<?php echo esc_attr( $lang ); ?>">
						<h4 id="sg-dialog-<?php echo esc_attr( $lang ); ?>"><?php echo esc_html( $t['dialog'][0] ); ?></h4>
						<p><?php echo esc_html( $t['dialog'][1] ); ?></p>
						<div class="hm-cluster">
							<button type="button" class="hm-btn hm-btn--dark"><?php echo esc_html( $t['dialog'][2] ); ?></button>
							<button type="button" class="hm-btn hm-btn--ghost"><?php echo esc_html( $t['dialog'][3] ); ?></button>
						</div>
					</dialog>
				</div>
			</div>

			<div class="hm-sg-block">
				<h3>Layout · hm-section--muted · hm-grid · hm-stack · hm-cluster · icons</h3>
				<div class="hm-section hm-section--muted hm-sg-muted">
					<p><?php echo esc_html( $t['section'] ); ?></p>
					<div class="hm-cluster hm-sg-icons">
						<?php foreach ( Icons::names() as $name ) : ?>
							<span title="<?php echo esc_attr( $name ); ?>"><?php echo Icons::get( $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
};
?>
<style>
	.hm-sg-title { font-size: var(--hm-text-3xl); margin: 0 0 .5rem; }
	.hm-sg-block { display: grid; gap: 1.25rem; padding-block: 2.5rem; border-block-start: 1px solid var(--hm-color-border); min-width: 0; }
	.hm-sg-block > h3 { margin: 0; font-size: .8125rem; font-weight: 600; color: var(--hm-color-text-subtle); direction: ltr; text-align: start; font-family: var(--hm-font-mono); }
	.hm-sg-grid2 { display: grid; gap: 0 2.5rem; }
	@media (min-width: 64em) { .hm-sg-grid2 { grid-template-columns: 1fr 1fr; } }
	.hm-sg-measure { max-width: 42rem; }
	.hm-sg-type { display: grid; gap: .5rem; }
	.hm-sg-type div { display: grid; grid-template-columns: 9rem 1fr; align-items: baseline; gap: 1rem; overflow: hidden; }
	.hm-sg-type code { font-size: .75rem; color: var(--hm-color-text-subtle); background: none; padding: 0; }
	.hm-sg-type span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: var(--hm-heading-weight); line-height: 1.4; }
	.hm-sg-backdrop { position: relative; padding: 1.5rem; border-radius: var(--hm-radius-xl); background: var(--hm-gradient-soft); }
	.hm-sg-media { background: radial-gradient(120% 90% at 20% 10%, rgba(255,122,26,.55), transparent 55%), radial-gradient(90% 80% at 90% 90%, rgba(139,92,246,.6), transparent 60%), var(--hm-gradient-brand); }
	.hm-sg-dialog { position: static; margin: 0; max-width: 100%; }
	.hm-sg-muted { padding: 2rem; border-radius: var(--hm-radius-lg); }
	.hm-sg-icons { font-size: 1.5rem; color: var(--hm-color-text-muted); }
	.hm-sg-swatches { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: 1rem; }
	.hm-sg-swatch { margin: 0; overflow: hidden; border: 1px solid var(--hm-color-border); border-radius: var(--hm-radius-md); background: var(--hm-color-elevated); }
	.hm-sg-swatch span { display: block; height: 4.5rem; }
	.hm-sg-swatch figcaption { padding: .5rem .75rem; font: 500 .75rem/1.4 var(--hm-font-mono); color: var(--hm-color-text-muted); direction: ltr; text-align: left; overflow-wrap: anywhere; }
	.hm-sg-scale { display: flex; flex-wrap: wrap; gap: 1.25rem; align-items: flex-end; }
	.hm-sg-scale div { display: grid; gap: .5rem; justify-items: center; font: .75rem var(--hm-font-mono); color: var(--hm-color-text-subtle); }
	.hm-sg-scale i { display: block; width: 5rem; height: 5rem; background: var(--hm-color-elevated); border: 1px solid var(--hm-color-border); }
	.hm-sg-hero { position: relative; overflow: hidden; }
	.hm-sg-hero::before { content: ""; position: absolute; inset: -30% -10% auto; height: 120%; background: radial-gradient(40% 50% at 70% 40%, rgba(255,59,92,.16), transparent 70%), radial-gradient(35% 45% at 25% 30%, rgba(139,92,246,.16), transparent 70%), radial-gradient(30% 40% at 50% 70%, rgba(255,122,26,.14), transparent 70%); pointer-events: none; }
	.hm-sg-hero .hm-container { position: relative; }
</style>

<section class="hm-section hm-sg-hero">
	<div class="hm-container">
		<header class="hm-section-head hm-section-head--center">
			<p class="hm-section-head__eyebrow">HAMISTA · Design system</p>
			<h1 class="hm-section-head__title" style="font-size:var(--hm-text-6xl)">Tokens <span class="hm-gradient-text">&amp; primitives</span></h1>
			<p class="hm-section-head__subtitle">Spec §4.1 tokens and every §4.2 primitive, rendered right-to-left (Persian) and left-to-right (English). Switch the colour mode from the header to review the dark palette.</p>
		</header>

		<div class="hm-sg-block">
			<h3>Colour tokens (current mode)</h3>
			<div class="hm-sg-swatches">
				<?php
				foreach ( [ 'bg', 'surface', 'surface-2', 'elevated', 'text', 'text-muted', 'text-subtle', 'border', 'border-strong', 'primary', 'primary-hover', 'link', 'link-hover', 'focus', 'success', 'success-soft', 'warning', 'warning-soft', 'danger', 'danger-soft', 'info', 'info-soft', 'brand-orange', 'brand-red', 'brand-purple' ] as $hamista_token ) :
					?>
					<figure class="hm-sg-swatch"><span style="background:var(--hm-color-<?php echo esc_attr( $hamista_token ); ?>)"></span><figcaption>--hm-color-<?php echo esc_html( $hamista_token ); ?></figcaption></figure>
				<?php endforeach; ?>
				<?php foreach ( [ 'brand', 'action', 'soft', 'text' ] as $hamista_token ) : ?>
					<figure class="hm-sg-swatch"><span style="background:var(--hm-gradient-<?php echo esc_attr( $hamista_token ); ?>)"></span><figcaption>--hm-gradient-<?php echo esc_html( $hamista_token ); ?></figcaption></figure>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="hm-sg-block">
			<h3>Radius · shadow</h3>
			<div class="hm-sg-scale">
				<?php foreach ( [ 'sm', 'md', 'lg', 'xl', 'pill' ] as $hamista_token ) : ?>
					<div><i style="border-radius:var(--hm-radius-<?php echo esc_attr( $hamista_token ); ?>)"></i>radius-<?php echo esc_html( $hamista_token ); ?></div>
				<?php endforeach; ?>
				<?php foreach ( [ 'xs', 'sm', 'md', 'lg', 'glow' ] as $hamista_token ) : ?>
					<div><i style="border-radius:var(--hm-radius-lg);box-shadow:var(--hm-shadow-<?php echo esc_attr( $hamista_token ); ?>)"></i>shadow-<?php echo esc_html( $hamista_token ); ?></div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<?php
$hamista_sg( 'fa', $hamista_sg_copy['fa'] );
$hamista_sg( 'en', $hamista_sg_copy['en'] );
