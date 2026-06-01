<?php
/**
 * Admin planning table for trope and genre page SEO.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_trope_page_seo_strlen(string $value): int {
	return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function bbb_trope_page_seo_rows(): array {
	$rows = array(
		array(
			'tag'         => 'age gap',
			'page'        => '/age-gap-romance-books/',
			'focus'       => 'age gap romance book list',
			'seo_title'   => 'age gap romance books — curated library',
			'description' => 'browse a curated age gap romance library sorted by spice level and trope. every book hand-picked and rated.',
			'note'        => '',
		),
		array(
			'tag'         => 'baseball romance',
			'page'        => '/baseball-romance-books/',
			'focus'       => 'baseball romance book list',
			'seo_title'   => 'baseball romance books — curated library',
			'description' => 'a curated baseball romance library sorted by spice level, trope, and series. every book worth reading, nothing filler.',
			'note'        => '',
		),
		array(
			'tag'         => 'billionaire romance',
			'page'        => '/billionaire-romance-books/',
			'focus'       => 'billionaire romance book list',
			'seo_title'   => 'billionaire romance books — curated library',
			'description' => 'browse a curated billionaire romance library sorted by spice and trope. powerful men, complicated women, zero filler.',
			'note'        => '',
		),
		array(
			'tag'         => 'bodyguard romance',
			'page'        => '/bodyguard-romance-books/',
			'focus'       => 'bodyguard romance book list',
			'seo_title'   => 'bodyguard romance books — curated library',
			'description' => 'a curated bodyguard romance library — he\'s supposed to protect her, not fall for her. sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'boss x employee',
			'page'        => '/boss-x-employee-romance-books/',
			'focus'       => 'boss employee romance book list',
			'seo_title'   => 'boss x employee romance books — curated library',
			'description' => 'browse a curated boss x employee romance library. every book sorted by spice level, trope, and how badly they break the rules.',
			'note'        => '',
		),
		array(
			'tag'         => 'brother\'s best friend',
			'page'        => '/brothers-best-friend-romance-books/',
			'focus'       => 'brother\'s best friend romance book list',
			'seo_title'   => 'brother\'s best friend romance books — curated',
			'description' => 'a curated brother\'s best friend romance library. he was always off limits — sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'bully romance',
			'page'        => '/bully-romance-books/',
			'focus'       => 'bully romance book list',
			'seo_title'   => 'bully romance books — curated library by spice',
			'description' => 'browse a curated bully romance library. he made her life hell — then fell first. sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'captor x captive',
			'page'        => '/captor-x-captive-romance-books/',
			'focus'       => 'captor captive romance book list',
			'seo_title'   => 'captor x captive romance books — curated library',
			'description' => 'a curated captor x captive romance library. dark, obsessive, and sorted by spice level — only the ones worth the read.',
			'note'        => '',
		),
		array(
			'tag'         => 'contemporary romance',
			'page'        => '/contemporary-romance-books/',
			'focus'       => 'contemporary romance book list',
			'seo_title'   => 'contemporary romance books — curated library',
			'description' => 'browse a curated contemporary romance library sorted by trope and spice level. real feelings, messy situations, no magic required.',
			'note'        => '',
		),
		array(
			'tag'         => 'dark academia',
			'page'        => '/dark-academia-books/',
			'focus'       => 'dark academia romance book list',
			'seo_title'   => 'dark academia romance books — curated library',
			'description' => 'a curated dark academia romance library — ivy walls, forbidden tension, morally gray everything. sorted by spice and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'dark romance',
			'page'        => '/dark-romance-books/',
			'focus'       => 'dark romance book list',
			'seo_title'   => 'dark romance books — curated library by trope & spice',
			'description' => 'browse a curated dark romance book library, sorted by trope, spice level, and series. every book is hand-picked and rated.',
			'note'        => '',
		),
		array(
			'tag'         => 'dystopian romance',
			'page'        => '/dystopian-romance-books/',
			'focus'       => 'dystopian romance book list',
			'seo_title'   => 'dystopian romance books — curated library',
			'description' => 'a curated dystopian romance library sorted by spice level, trope, and series. the worlds are broken — the love stories aren\'t.',
			'note'        => '',
		),
		array(
			'tag'         => 'enemies to lovers',
			'page'        => '/enemies-to-lovers-books/',
			'focus'       => 'enemies to lovers book list',
			'seo_title'   => 'enemies to lovers books — curated library by spice',
			'description' => 'a curated enemies-to-lovers book library sorted by spice level and trope. every book that made us fall for someone we shouldn\'t.',
			'note'        => '',
		),
		array(
			'tag'         => 'fake dating',
			'page'        => '/fake-dating-romance-books/',
			'focus'       => 'fake dating romance book list',
			'seo_title'   => 'fake dating romance books — curated library',
			'description' => 'browse a curated fake dating romance library. it was supposed to be pretend — sorted by spice level, trope, and how fast they caught feelings.',
			'note'        => '',
		),
		array(
			'tag'         => 'fated mates',
			'page'        => '/fated-mates-romance-books/',
			'focus'       => 'fated mates romance book list',
			'seo_title'   => 'fated mates romance books — curated library',
			'description' => 'a curated fated mates romance library sorted by spice and trope. the universe decided — now they have to deal with it.',
			'note'        => '',
		),
		array(
			'tag'         => 'forbidden love',
			'page'        => '/forbidden-love-romance-books/',
			'focus'       => 'forbidden love romance book list',
			'seo_title'   => 'forbidden love romance books — curated library',
			'description' => 'browse a curated forbidden love romance library. sorted by spice and trope — every book where they knew better and fell anyway.',
			'note'        => '',
		),
		array(
			'tag'         => 'forced proximity',
			'page'        => '/forced-proximity-romance-books/',
			'focus'       => 'forced proximity romance book list',
			'seo_title'   => 'forced proximity romance books — curated library',
			'description' => 'a curated forced proximity romance library. stuck together, feelings inevitable — sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'found family',
			'page'        => '/found-family-romance-books/',
			'focus'       => 'found family romance book list',
			'seo_title'   => 'found family romance books — curated library',
			'description' => 'browse a curated found family romance library. love stories where the family they built matters as much as the one they found.',
			'note'        => '',
		),
		array(
			'tag'         => 'friends to lovers',
			'page'        => '/friends-to-lovers-romance-books/',
			'focus'       => 'friends to lovers romance book list',
			'seo_title'   => 'friends to lovers romance books — curated library',
			'description' => 'a curated friends to lovers romance library. they were always more than friends — sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'grumpy x sunshine',
			'page'        => '/grumpy-x-sunshine-romance-books/',
			'focus'       => 'grumpy sunshine romance book list',
			'seo_title'   => 'grumpy x sunshine romance books — curated library',
			'description' => 'browse a curated grumpy x sunshine romance library. he\'s impossible, she\'s relentless — sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'he falls first',
			'page'        => '/he-falls-first-romance-books/',
			'focus'       => 'he falls first romance book list',
			'seo_title'   => 'he falls first romance books — curated library',
			'description' => 'a curated he-falls-first romance library. he was gone for her before she even noticed — sorted by spice level and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'historical romance',
			'page'        => '/historical-romance-books/',
			'focus'       => 'historical romance book list',
			'seo_title'   => 'historical romance books — curated library',
			'description' => 'browse a curated historical romance library sorted by spice level and trope. corsets, tension, and heroes who ruin you for modern men.',
			'note'        => '',
		),
		array(
			'tag'         => 'hockey romance',
			'page'        => '/hockey-romance-books/',
			'focus'       => 'hockey romance book list',
			'seo_title'   => 'hockey romance books — curated library by spice',
			'description' => 'a curated hockey romance library sorted by spice level, trope, and series. big men, big feelings, sorted by how unhinged they get.',
			'note'        => '',
		),
		array(
			'tag'         => 'mafia romance',
			'page'        => '/mafia-romance-books/',
			'focus'       => 'mafia romance book list',
			'seo_title'   => 'mafia romance books — curated library by trope & spice',
			'description' => 'browse a curated mafia romance book library, filtered by spice level, trope, and series order. no filler — only the best.',
			'note'        => '',
		),
		array(
			'tag'         => 'marriage of convenience',
			'page'        => '/marriage-of-convenience-romance-books/',
			'focus'       => 'marriage of convenience romance book list',
			'seo_title'   => 'marriage of convenience romance books — curated',
			'description' => 'a curated marriage of convenience romance library. it started as a deal — sorted by spice level, trope, and how fast it stopped being fake.',
			'note'        => '',
		),
		array(
			'tag'         => 'nanny',
			'page'        => '/nanny-romance-books/',
			'focus'       => 'nanny romance book list',
			'seo_title'   => 'nanny romance books — curated library by spice',
			'description' => 'browse a curated nanny romance library. she was just there for the kids — sorted by spice level, trope, and how quickly things got complicated.',
			'note'        => '',
		),
		array(
			'tag'         => 'one bed',
			'page'        => '/one-bed-romance-books/',
			'focus'       => 'one bed romance book list',
			'seo_title'   => 'one bed romance books — curated library by spice',
			'description' => 'a curated one bed romance library. there was only one bed — sorted by spice level, trope, and how long they lasted on their side.',
			'note'        => '',
		),
		array(
			'tag'         => 'opposites attract',
			'page'        => '/opposites-attract-books/',
			'focus'       => 'opposites attract romance book list',
			'seo_title'   => 'opposites attract romance books — curated library',
			'description' => 'browse a curated opposites attract romance library. different in every way, inevitable anyway — sorted by spice and trope.',
			'note'        => '',
		),
		array(
			'tag'         => 'paranormal romance',
			'page'        => '/paranormal-romance-books/',
			'focus'       => 'paranormal romance book list',
			'seo_title'   => 'paranormal romance books — curated library',
			'description' => 'a curated paranormal romance library sorted by spice level and trope. vampires, shifters, demons — and the women who wreck them.',
			'note'        => '',
		),
		array(
			'tag'         => 'romantasy',
			'page'        => '/romantasy-books/',
			'focus'       => 'romantasy book list',
			'seo_title'   => 'romantasy books — curated library by trope & spice',
			'description' => 'a hand-curated romantasy book library sorted by trope, spice level, and series. find your next fantasy romance obsession.',
			'note'        => '',
		),
		array(
			'tag'         => 'second chance',
			'page'        => '/second-chance-romance-books/',
			'focus'       => 'second chance romance book list',
			'seo_title'   => 'second chance romance books — curated library',
			'description' => 'browse a curated second chance romance library sorted by spice and trope. the ones where he has to earn her back — and does.',
			'note'        => '',
		),
		array(
			'tag'         => 'single dad',
			'page'        => '/single-dad-romance-books/',
			'focus'       => 'single dad romance book list',
			'seo_title'   => 'single dad romance books — curated library',
			'description' => 'a curated single dad romance library. he wasn\'t looking for this — sorted by spice level, trope, and how hard he fell anyway.',
			'note'        => '',
		),
		array(
			'tag'         => 'slow burn',
			'page'        => '/slow-burn-books/',
			'focus'       => 'slow burn romance book list',
			'seo_title'   => 'slow burn romance books — curated library by spice',
			'description' => 'browse a curated slow burn romance library. every book sorted by spice level and trope — all the tension, all worth the wait.',
			'note'        => '',
		),
		array(
			'tag'         => 'small town',
			'page'        => '/small-town-romance-books/',
			'focus'       => 'small town romance book list',
			'seo_title'   => 'small town romance books — curated library',
			'description' => 'browse a curated small town romance library. everybody knows everybody — sorted by spice level, trope, and how fast the gossip spread.',
			'note'        => '',
		),
		array(
			'tag'         => 'sports romance',
			'page'        => '/sports-romance-books/',
			'focus'       => 'sports romance book list',
			'seo_title'   => 'sports romance books — curated library by spice',
			'description' => 'a curated sports romance library sorted by sport, spice level, and trope. athletes with big egos and even bigger feelings.',
			'note'        => '',
		),
		array(
			'tag'         => 'stalker romance',
			'page'        => '/stalker-romance-books/',
			'focus'       => 'stalker romance book list',
			'seo_title'   => 'stalker romance books — curated library by spice',
			'description' => 'a curated stalker romance library. he was watching long before she knew — sorted by spice level, trope, and how unhinged he gets.',
			'note'        => '',
		),
		array(
			'tag'         => 'step siblings',
			'page'        => '/step-siblings-romance-books/',
			'focus'       => 'step siblings romance book list',
			'seo_title'   => 'step siblings romance books — curated library',
			'description' => 'browse a curated step siblings romance library. technically off limits — sorted by spice level, trope, and how badly they tried to resist.',
			'note'        => '',
		),
		array(
			'tag'         => 'touch her and die',
			'page'        => '/touch-her-and-die-books/',
			'focus'       => 'touch her and die book list',
			'seo_title'   => 'touch her and die romance books — curated library',
			'description' => 'a curated library of touch-her-and-die romance books — possessive, obsessive heroes who will do anything to protect her.',
			'note'        => '',
		),
		array(
			'tag'         => 'trauma bonding',
			'page'        => '/trauma-bonding-romance-books/',
			'focus'       => 'trauma bonding romance book list',
			'seo_title'   => 'trauma bonding romance books — curated library',
			'description' => 'a curated trauma bonding romance library. the kind of love that leaves marks — sorted by spice level, trope, and emotional damage.',
			'note'        => '',
		),
		array(
			'tag'         => 'villain gets the girl',
			'page'        => '/villain-gets-the-girl-books/',
			'focus'       => 'villain gets the girl romance book list',
			'seo_title'   => 'villain gets the girl romance books — curated',
			'description' => 'browse a curated villain gets the girl romance library. he shouldn\'t get her — sorted by spice level, trope, and moral corruption level.',
			'note'        => '',
		),
		array(
			'tag'         => 'who did this to you',
			'page'        => '/who-did-this-to-you-books/',
			'focus'       => 'who did this to you romance book list',
			'seo_title'   => 'who did this to you romance books — curated',
			'description' => 'a curated who-did-this-to-you romance library. he wants to fix what broke her — sorted by spice level, trope, and how gently he does it.',
			'note'        => '',
		),
		array(
			'tag'         => 'why choose',
			'page'        => '/why-choose-romance-books/',
			'focus'       => 'why choose romance book list',
			'seo_title'   => 'why choose romance books — curated library by spice',
			'description' => 'browse a curated why choose romance library. she doesn\'t have to pick — sorted by spice level, trope, and how chaotic it gets.',
			'note'        => '',
		),
	);

	foreach ($rows as $index => $row) {
		$rows[$index]['focus']       = (string) ($row['focus'] ?? '');
		$rows[$index]['seo_title']   = (string) ($row['seo_title'] ?? '');
		$rows[$index]['description'] = (string) ($row['description'] ?? '');
		$rows[$index]['note']        = (string) ($row['note'] ?? '');
		$rows[$index]['status']      = '' !== $rows[$index]['focus'] && '' !== $rows[$index]['seo_title'] && '' !== $rows[$index]['description'] ? 'complete' : 'needs-seo';
	}

	return $rows;
}

function bbb_trope_page_seo_filtered_rows(): array {
	$status = isset($_GET['bbb_trope_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_trope_seo_status'])) : '';
	$search = isset($_GET['s']) ? strtolower(sanitize_text_field((string) wp_unslash($_GET['s']))) : '';
	$rows   = bbb_trope_page_seo_rows();

	return array_values(
		array_filter(
			$rows,
			static function (array $row) use ($status, $search): bool {
				if (in_array($status, array('complete', 'needs-seo'), true) && $status !== $row['status']) {
					return false;
				}

				if ('' === $search) {
					return true;
				}

				$haystack = strtolower(implode(' ', array((string) $row['tag'], (string) $row['page'], (string) $row['focus'], (string) $row['seo_title'], (string) $row['description'])));

				return str_contains($haystack, $search);
			}
		)
	);
}

function bbb_trope_page_seo_export(): void {
	if (empty($_GET['bbb_trope_seo_export']) || !current_user_can('manage_options')) {
		return;
	}

	check_admin_referer('bbb_trope_page_seo_export');

	nocache_headers();
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=bybookishbabe-trope-page-seo-' . gmdate('Y-m-d') . '.csv');

	$output = fopen('php://output', 'w');
	if (false === $output) {
		exit;
	}

	fputcsv($output, array('trope page tag', 'page', 'new focus keyword', 'seo title (<=60 chars)', 'meta description (<=155 chars)', 'status', 'note'));
	foreach (bbb_trope_page_seo_filtered_rows() as $row) {
		fputcsv($output, array($row['tag'], $row['page'], $row['focus'], $row['seo_title'], $row['description'], $row['status'], $row['note']));
	}

	exit;
}
add_action('admin_init', 'bbb_trope_page_seo_export');

function bbb_trope_page_seo_admin_page(): void {
	$status       = isset($_GET['bbb_trope_seo_status']) ? sanitize_key((string) wp_unslash($_GET['bbb_trope_seo_status'])) : '';
	$search       = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
	$rows         = bbb_trope_page_seo_filtered_rows();
	$all_rows      = bbb_trope_page_seo_rows();
	$complete     = count(array_filter($all_rows, static fn(array $row): bool => 'complete' === $row['status']));
	$needs_seo    = count($all_rows) - $complete;
	$export_url   = wp_nonce_url(
		add_query_arg(
			array(
				'page'                 => 'bbb-trope-page-seo',
				'bbb_trope_seo_status' => $status,
				's'                    => $search,
				'bbb_trope_seo_export' => '1',
			),
			admin_url('tools.php')
		),
		'bbb_trope_page_seo_export'
	);
	?>
	<div class="wrap bbb-trope-page-seo">
		<h1>Trope Page SEO</h1>
		<p class="description">Planning table for trope and genre landing-page SEO. Blank SEO fields mean that trope page tag still needs copy.</p>

		<form method="get" class="bbb-trope-page-seo__filters">
			<input type="hidden" name="page" value="bbb-trope-page-seo">
			<label for="bbb_trope_seo_status">Status</label>
			<select id="bbb_trope_seo_status" name="bbb_trope_seo_status">
				<option value="">All rows</option>
				<option value="needs-seo" <?php selected($status, 'needs-seo'); ?>>Needs SEO</option>
				<option value="complete" <?php selected($status, 'complete'); ?>>Complete</option>
			</select>

			<label for="bbb_trope_seo_search">Search</label>
			<input id="bbb_trope_seo_search" type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Trope page tag or URL">
			<?php submit_button('Filter', 'secondary', '', false); ?>
			<a class="button" href="<?php echo esc_url(admin_url('tools.php?page=bbb-trope-page-seo')); ?>">Reset</a>
			<a class="button" href="<?php echo esc_url($export_url); ?>">Export CSV</a>
		</form>

		<p><strong><?php echo esc_html((string) count($all_rows)); ?></strong> trope page tags · <strong><?php echo esc_html((string) $complete); ?></strong> complete · <strong><?php echo esc_html((string) $needs_seo); ?></strong> need SEO</p>

		<div class="bbb-trope-page-seo__table-scroll" role="region" aria-label="Trope page SEO table" tabindex="0">
			<table class="widefat striped bbb-trope-page-seo__table">
				<thead>
					<tr>
						<th>Trope page tag</th>
						<th>Page</th>
						<th>Status</th>
						<th>New focus keyword</th>
						<th>SEO title</th>
						<th>Meta description</th>
						<th>Note</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!$rows) : ?>
						<tr><td colspan="7">No matching trope page tags found.</td></tr>
					<?php endif; ?>
					<?php foreach ($rows as $row) : ?>
						<?php
						$page_url   = home_url((string) $row['page']);
						$title_len  = bbb_trope_page_seo_strlen((string) $row['seo_title']);
						$desc_len   = bbb_trope_page_seo_strlen((string) $row['description']);
						$is_done    = 'complete' === $row['status'];
						?>
						<tr class="<?php echo $is_done ? 'is-complete' : 'needs-seo'; ?>">
							<td><strong><?php echo esc_html((string) $row['tag']); ?></strong></td>
							<td><a href="<?php echo esc_url($page_url); ?>" target="_blank" rel="noopener"><?php echo esc_html((string) $row['page']); ?></a></td>
							<td>
								<span class="bbb-trope-page-seo__status <?php echo $is_done ? 'is-complete' : 'needs-seo'; ?>">
									<?php echo $is_done ? 'Complete' : 'Needs SEO'; ?>
								</span>
							</td>
							<td><?php echo '' !== $row['focus'] ? esc_html((string) $row['focus']) : '<span class="bbb-trope-page-seo__blank">Blank</span>'; ?></td>
							<td>
								<?php echo '' !== $row['seo_title'] ? esc_html((string) $row['seo_title']) : '<span class="bbb-trope-page-seo__blank">Blank</span>'; ?>
								<?php if ('' !== $row['seo_title']) : ?>
									<div class="bbb-trope-page-seo__meta"><?php echo esc_html((string) $title_len); ?> chars</div>
								<?php endif; ?>
							</td>
							<td>
								<?php echo '' !== $row['description'] ? esc_html((string) $row['description']) : '<span class="bbb-trope-page-seo__blank">Blank</span>'; ?>
								<?php if ('' !== $row['description']) : ?>
									<div class="bbb-trope-page-seo__meta"><?php echo esc_html((string) $desc_len); ?> chars</div>
								<?php endif; ?>
							</td>
							<td><?php echo '' !== $row['note'] ? esc_html((string) $row['note']) : '<span class="bbb-trope-page-seo__muted">None</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<style>
		.bbb-trope-page-seo__filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:16px 0;padding:12px;background:#fff;border:1px solid #dcdcde;border-radius:6px}
		.bbb-trope-page-seo__filters label{font-weight:600}
		.bbb-trope-page-seo__filters select,.bbb-trope-page-seo__filters input[type="search"]{min-width:190px}
		.bbb-trope-page-seo__table-scroll{overflow-x:auto;margin-top:12px;border:1px solid #c3c4c7;background:#fff;-webkit-overflow-scrolling:touch}
		.bbb-trope-page-seo__table{min-width:1280px;border:0;table-layout:fixed}
		.bbb-trope-page-seo__table th:nth-child(1){width:180px}
		.bbb-trope-page-seo__table th:nth-child(2){width:230px}
		.bbb-trope-page-seo__table th:nth-child(3){width:110px}
		.bbb-trope-page-seo__table th:nth-child(4){width:200px}
		.bbb-trope-page-seo__table th:nth-child(5){width:280px}
		.bbb-trope-page-seo__table th:nth-child(6){width:360px}
		.bbb-trope-page-seo__table th:nth-child(7){width:190px}
		.bbb-trope-page-seo__table td{vertical-align:top}
		.bbb-trope-page-seo__status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:700}
		.bbb-trope-page-seo__status.is-complete{background:#edfaef;color:#008a20}
		.bbb-trope-page-seo__status.needs-seo{background:#fcf0f1;color:#b32d2e}
		.bbb-trope-page-seo__blank{color:#b32d2e;font-weight:700}
		.bbb-trope-page-seo__muted,.bbb-trope-page-seo__meta{color:#646970}
		.bbb-trope-page-seo__meta{margin-top:5px;font-size:12px}
	</style>
	<?php
}

function bbb_trope_page_seo_admin_menu(): void {
	add_management_page(
		__('Trope Page SEO', 'bybookishbabe-shopify-port'),
		__('Trope Page SEO', 'bybookishbabe-shopify-port'),
		'manage_options',
		'bbb-trope-page-seo',
		'bbb_trope_page_seo_admin_page'
	);
}
add_action('admin_menu', 'bbb_trope_page_seo_admin_menu');
