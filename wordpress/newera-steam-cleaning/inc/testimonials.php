<?php
/**
 * Customer testimonials.
 *
 * Real reviews collected from the New Era Yelp and Angi profiles, ported from
 * the seed data in Business Tools Suite.Client/Services/PlaceholderDataService.cs.
 * Used as the fallback until the `newera_testimonial` posts are imported.
 *
 * Nothing here is scraped at runtime: the live site returns 403 to automated
 * fetches, and review copy changes rarely enough that editing it in wp-admin
 * is the simpler answer.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * The seeded testimonials, newest first.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_default_testimonials() {
	return array(
		array(
			'author' => 'Nicole W.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2026-04-30',
			'text'   => "I needed light colored dining room chairs that had become dingy and a few rugs cleaned, but I was needing to be careful because most of the chairs were a performance crypton fabric with special instructions that only water could be used to clean and it couldn't be too hot. The owner went above and beyond to understand the cleaning code, get the best products to use, and forming the best strategy to balance risk management but improving the state of the chairs. I understood he couldn't guarantee results, but felt that he worked hard and long to ensure the best outcome possible. In the end, we did see a big improvement in the chairs, and while they don't look brand new, it's much better. Communication was also easy throughout the process, and price was very reasonable after I checked with other companies as well. I'd recommend and use him again.",
		),
		array(
			'author' => 'Ashton S.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2026-01-16',
			'text'   => "Charles was the utmost in professionalism. My home has 25yo carpets that have seen better days. I had very low expectations on the outcome and Charles used his expertise to give me what I needed. One room was so soiled, he recommended that the carpet be replaced and he didn't charge me for it. I appreciate the clear communication and trustworthiness of his business practices. I'd definitely recommend Charles in the future.",
		),
		array(
			'author' => 'Willie M.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2025-11-28',
			'text'   => "We've been using New Era Steam Cleaning &amp; Restoration for years, and Charles never disappoints! With a child and pets, our carpets see plenty of accidents, but every time he comes, they look brand new and smell amazing. Reliable, professional, and always friendly&mdash;highly recommend!",
		),
		array(
			'author' => 'M P.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2025-10-25',
			'text'   => 'New Era was professional and efficient. They showed up during the agreed arrival window, and did the job thoroughly and quickly. At the end of the job, the owner walked me through the job, and pointed out areas where he had to use extra effort to clean the carpet. I am very happy with the job!',
		),
		array(
			'author' => 'Amy N.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2025-07-26',
			'text'   => 'Easy communication. Job well done. Would highly recommend! Shower floor now looks much better!',
		),
		array(
			'author' => 'Nicole B.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2025-06-13',
			'text'   => "Forget the rest. CALL NEW ERA STEAM. He will address all your questions. Clear communication. Competitive prices. Made everything I needed easy and fast. Thank you so very much. I wasn't sure what I needed for moving out of my house and it came out better than expected. Everyone was happy.",
		),
		array(
			'author' => 'Brenda W.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2025-05-06',
			'text'   => 'Charles did an excellent job! He was on time and got the job done fast. We will use his service again! The carpet looked and smelled great.',
		),
		array(
			'author' => 'Hookem H.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2025-01-06',
			'text'   => 'Selected Charles based on his large number of 5-star reviews. He communicated quickly and easily and came precisely at the scheduled time. He was quick and efficient and it is easy to see why he has so many 5-star reviews. He made our 33+ year old carpet look great and our house feels fresh and clean. He has our recommendation and we will use him again.',
		),
		array(
			'author' => 'Daneille T.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2024-10-28',
			'text'   => "New era was quick to respond to my request. I'm so grateful he was able to get our grout cleaned and carpets shampooed before the movers came. The before and after visually was a world of difference but the smell was even better. The previous owners had pets, so there was a heavy smell of dander in our home. I'm very happy with this service and would recommend him to anyone who needs cleaning and restoration. Lastly he was very nice and educated us on cleaning materials we use that may have been causing more wear on our tile.",
		),
		array(
			'author' => 'Richard W.',
			'source' => 'Yelp',
			'rating' => 5,
			'date'   => '2024-05-10',
			'text'   => 'Great job, honest and communicative throughout the project. We felt like we got what we wanted and a fair price. Highly recommend this company.',
		),
		array(
			'author' => 'Paul T.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2024-03-17',
			'text'   => 'Charles was excellent! He came, worked hard all day, and left me with a very clean house. The price was good and I am very pleased with the results.',
		),
		array(
			'author' => 'Kedar K.',
			'source' => 'Angi',
			'rating' => 4,
			'date'   => '2019-08-16',
			'text'   => 'The service overall with them was good. Scheduling was easy.',
		),
		array(
			'author' => 'Susan C.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-09-15',
			'text'   => 'Great &mdash; loved them!',
		),
		array(
			'author' => 'Kyle P.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-06-06',
			'text'   => "Charles came out and did an AMAZING job, he was early to the appointment and was very informative on ways to clean and maintain my carpets. Overall a fantastic job done by New Era and wouldn't recommend any company for the job other than them. Thanks, and we will be seeing you again!",
		),
		array(
			'author' => 'Samantha H.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-03-20',
			'text'   => "It went great. My previously gray carpet is now white. They were able to get stains out that I wasn't able to. I would highly recommend them.",
		),
		array(
			'author' => 'Toby P.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-03-07',
			'text'   => 'The technician, Charles, was pretty knowledgeable about cleaning the carpet and removing scratches on the wood trim. He was also a nice guy and did a great job.',
		),
		array(
			'author' => 'Daniel M.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-03-02',
			'text'   => 'It went splendid! He was really prompt and respectful.',
		),
		array(
			'author' => 'Jake Z.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-03-01',
			'text'   => "It went awesome. They got a bunch of stains up I didn't think would come up.",
		),
		array(
			'author' => 'Brian J.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-03-01',
			'text'   => 'He was very professional. He showed up right on time and was able to get the job done promptly, he even finished a few minutes early. I would definitely use him again.',
		),
		array(
			'author' => 'Melissa D.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-03-01',
			'text'   => 'They did a great job. He pre-treated the carpet then cleaned the carpet very well.',
		),
		array(
			'author' => 'Ryan B.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-02-22',
			'text'   => "I called New Era to clean our carpet and tile. He was extremely courteous and knowledgeable walking me through the process. The owner, Charles, came out (on time) to give me an estimate, honored his coupon prices and got straight to work. I couldn't be happier with the work that Charles provided. My carpets look and smell fresh and the tile looks just as good as the day it was installed! He earned my repeat business with his effort and attention to detail. I definitely recommend New Era steam cleaning!",
		),
		array(
			'author' => 'Brooke E.',
			'source' => 'Angi',
			'rating' => 5,
			'date'   => '2017-02-20',
			'text'   => "Charles was here within 2 hours of calling at 2am on a Sunday night. We had water throughout 3 rooms of our house. He was able to give us a very detailed estimate and exact game plan for the drying process. He was very detailed in explaining what he was doing and exactly why he was doing it. He even gave us a link to a company called IICRC, who generate the guidelines for water restoration so we could double check and make sure what he was stating was true. He explained the drying could take 3-5 days to be complete, he was done in 3! He was very reasonable on pricing since we decided to pay out of pocket instead of using our insurance. All around great guy, professional and honest. We've used him for years for carpet and tile cleaning, hopefully we won't have to call for water damage again, but he will be the guy we call if it happens!",
		),
	);
}
