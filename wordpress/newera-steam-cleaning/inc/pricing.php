<?php
/**
 * Coupon price tables.
 *
 * Ported from SeedPriceTables() in PlaceholderDataService.cs. These are the
 * fallback shown when the Pricing page has no content of its own; the imported
 * Pricing page carries the same tables as core table blocks so prices can be
 * edited in the block editor.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * The seeded coupon price tables.
 *
 * Each table has a title and a list of [ service, description, price ] rows.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_default_price_tables() {
	return array(
		array(
			'title' => 'Carpet Cleaning',
			'rows'  => array(
				array(
					'service'     => 'Carpet cleaning minimum includes from 1 room, in room closets included',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating, STAIRS ARE NOT PRICED AS A ROOM',
					'price'       => '$139.00',
				),
				array(
					'service'     => 'Carpet cleaning 2-3 rooms, in room closets included',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating, STAIRS ARE NOT PRICED AS A ROOM',
					'price'       => '$159.00',
				),
				array(
					'service'     => '4 room clean, in room closets included',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating, STAIRS ARE NOT PRICED AS A ROOM',
					'price'       => '$199.00',
				),
				array(
					'service'     => '5 room clean, in room closets included',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating, STAIRS ARE NOT PRICED AS A ROOM',
					'price'       => '$229.00',
				),
				array(
					'service'     => 'Rooms over 12&times;13 can result in an additional charge',
					'description' => '',
					'price'       => '',
				),
				array(
					'service'     => '6 Rooms, including hallways and closets',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating, STAIRS ARE NOT PRICED AS A ROOM',
					'price'       => '$259.00',
				),
				array(
					'service'     => 'Whole House (Over 2000 sq ft, up to 3500sf)',
					'description' => 'Includes pre-treatment/steam cleaning',
					'price'       => 'TBD Priced by number of rooms, hallways and stairs',
				),
				array(
					'service'     => 'Synthetic rug',
					'description' => 'Includes pre-treatment/steam cleaning',
					'price'       => '$35-$65 (depending on size)',
				),
				array(
					'service'     => 'Wool rugs/specialty cleaning/most natural fiber rugs',
					'description' => '',
					'price'       => '$1.50 per square foot',
				),
				array(
					'service'     => 'Hallway',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating',
					'price'       => '$15.00 &ndash; $25.00',
				),
				array(
					'service'     => 'Stairs',
					'description' => 'Includes pre-treatment/steam cleaning, spot treating',
					'price'       => '$3 each or $45 up to 16 steps',
				),
				array(
					'service'     => 'Commercial Carpet',
					'description' => 'Must be during normal business hours',
					'price'       => '$0.25 per sq ft, $0.35 after business hours',
				),
				array(
					'service'     => 'Power scrub/heavy clean',
					'description' => 'Use of floor machine on very soiled areas',
					'price'       => '$45.00 minimum',
				),
				array(
					'service'     => 'Topical pet treatment',
					'description' => 'Includes urine stain treatment and enzyme disinfectant',
					'price'       => '$35 per room',
				),
				array(
					'service'     => 'Full Pet Treatment for heavily urine saturated carpets and padding',
					'description' => 'Two-sided carpet steam clean and disinfectant, pad replacement',
					'price'       => 'Starts at $250 per room',
				),
			),
		),
		array(
			'title' => 'Carpet Repair/Restoration',
			'rows'  => array(
				array(
					'service'     => 'Patches',
					'description' => 'Carpet patches cut and installed',
					'price'       => '$75.00 min',
				),
				array(
					'service'     => 'Water damage restoration',
					'description' => 'Complete dry out of flooring and structure',
					'price'       => '$125.00 minimum; priced on site',
				),
			),
		),
		array(
			'title' => 'Tile, Stone &amp; Grout Cleaning',
			'rows'  => array(
				array(
					'service'     => 'Tile/grout clean &mdash; ceramic/porcelain only',
					'description' => 'Regular price $1 a square foot.',
					'price'       => 'Starts at $0.85 per sq ft.',
				),
				array(
					'service'     => 'Stone restoration/clean',
					'description' => 'All stone floors, showers, backsplash and stone countertops',
					'price'       => 'Priced on site',
				),
				array(
					'service'     => 'Color staining grout/sealant',
					'description' => 'For damaged grout or just freshening up/changing color',
					'price'       => '$2.00',
				),
			),
		),
	);
}
