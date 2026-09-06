<?php
/**
 * The service catalog.
 *
 * Ported from Business Tools Suite.Client/Services/ServiceCatalog.cs. This is
 * the fallback used until the `newera_service` posts are imported; once they
 * exist, newera_get_services() reads them instead so the owner can edit the
 * copy in wp-admin.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * The seeded service catalog, in display order.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_default_services() {
	return array(
		array(
			'slug'        => 'carpet-cleaning',
			'name'        => 'Carpet Cleaning',
			'icon'        => 'bi-stars',
			'tagline'     => "Recapture your carpet's youth.",
			'local_title' => "What you get beyond the clean",
			'local_body'  => "We clean with Prochem products and truck-mounted equipment &mdash; a name near the front of professional carpet and fabric care since the mid 1990s. Every product we use is baby and pet safe, and organic products are available on request; if you would rather know exactly what is going on your floor before we start, ask and we will talk it through. Each job finishes with aftercare guidance and a walk-through of the work with you, in Sugar Land and across the wider Houston area. If you need us quickly, say so when you call and we will work to find an appointment that suits you.",
			'summary'     => 'Skilled technicians deep-clean and refresh tired carpets, lifting dirt, stains, and allergens for a like-new look.',
			'highlights'  => array(
				'Furniture moved before and replaced after, with protectors where needed',
				'Spots pre-treated with the appropriate agent for stubborn stains',
				'Optional pre-shampoo with gentle agitation to release soil (add-on)',
				'Powerscrub hot-water steam extraction via truck-mounted systems',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "Let's face it &mdash; dirty carpet is the worst. But that's why we're here. Our skilled technicians are ready to help you recapture your carpet's youth. Here's what you can expect: most furniture (sofas, chairs, and tables) is moved before cleaning and put back afterward, with furniture protectors used where needed. Spots are pre-treated with the appropriate cleaning agent to lift stubborn stains that a typical clean might leave behind, and if desired we can pre-shampoo the carpet with gentle agitation to release and suspend soil (an optional add-on). We then perform Powerscrub steam extraction with detergent using portable equipment and/or truck-mounted systems, removing most soils and detergents to leave your carpet looking bright, smelling fresh, and feeling clean. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		array(
			'slug'        => 'carpet-repairs',
			'name'        => 'Carpet Repairs',
			'icon'        => 'bi-tools',
			'tagline'     => 'Restore damaged carpet seamlessly.',
			'local_title' => "Repair is usually cheaper than replacement",
			'local_body'  => "Wear and tear does not mean the carpet has to go. We handle carpet stretching, seam repair and patching throughout Fort Bend and Harris County, generally for a fraction of what replacing a floor costs. Ripples and buckles, open or raveled seams, burns, pulled yarns, bleached spots and failing padding are all everyday work for our technicians, and repairs are color-matched and blended so the result does not advertise itself. If you are not sure whether a floor is worth saving, send us a photo and we will tell you honestly.",
			'summary'     => 'From frays and burns to re-stretching and patching, we restore damaged carpet so it looks and feels seamless again.',
			'highlights'  => array(
				'Open, raveled, or failing seams re-seamed',
				'Burns and melted areas patched',
				'Pulled yarns and missing tufts repaired',
				'Ripples and buckles re-stretched flat',
				'Deteriorated or ruined padding replaced',
			),
			'description' => "Carpet is awesome when it's brand new, but we all know that life happens. If your carpet is in need of some TLC, our skilled technicians can lend a helping hand &mdash; often for a fraction of the cost of replacement. Some of the common problems our technicians encounter and repair are open or raveled seams, loosening installations, burns and melted areas, pulled yarns and missing tufts, bleached spots and discolorations, ripples and buckles, and deteriorated or ruined padding. We carefully color-match and blend repairs so the finished result looks seamless. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		array(
			'slug'        => 'commercial',
			'name'        => 'Commercial Carpet Cleaning',
			'icon'        => 'bi-building-fill',
			'tagline'     => 'No job too large or too small.',
			'local_title' => "Where we work, and when",
			'local_body'  => "Our commercial carpet cleaning service area includes Sugar Land, Katy, Richmond, Missouri City, Fulshear and the surrounding Houston area. No job is too large or too small &mdash; offices, retail units, medical suites and everything between. Because a commercial floor cannot be out of action during business hours, the work is scheduled after hours or on weekends and the drying planned around when you open rather than when we finish. For spaces that take heavy footfall, a continuous maintenance and cleansing program holds a carpet's appearance and durability far more cheaply than periodic rescue cleans.",
			'summary'     => 'High-traffic commercial spaces need extra care. Our experienced crew services offices and storefronts of any size.',
			'highlights'  => array(
				'Continuous maintenance and cleansing programs',
				'Flexible after-hours and weekend scheduling',
				'High-traffic commercial extraction equipment',
				'No job too large or too small &mdash; offices, retail, medical, and more',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "Commercial carpet receives vastly more traffic than the typical residential carpet. Choosing the right carpet for the space matters, but once it's installed, retaining its beauty and durability relies on continuous maintenance and cleansing programs. Our experienced crew is available to service commercial spaces of every size &mdash; no job is too large or too small. We look forward to partnering with you to revitalize your commercial carpet and improve the overall indoor environment of your space, with after-hours and weekend availability and recurring maintenance plans to keep it looking its best year-round. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		array(
			'slug'        => 'pet-odor-removal',
			'name'        => 'Pet Odor Removal',
			'icon'        => 'bi-heart-fill',
			'tagline'     => 'Fresh, clean, and odor-free.',
			'local_title' => "Why the smell keeps coming back",
			'local_body'  => "Both dog and cat urine contain uric acid, and most household cleaners cannot break it down. You remove the other components when you clean up, but the uric acid stays &mdash; and when it is exposed to humidity the salts re-form into crystals and release the smell all over again. On the Gulf Coast, that is most of the year. Even when it is undetectable to you, your pet's far more sensitive nose can still find it, which is often why an animal keeps returning to the same spot. The enzyme pre-treatment is the step that actually breaks the acid down, and where contamination has gone deep we can steam clean both sides of the carpet and replace the pad.",
			'summary'     => 'Targeted treatments neutralize stubborn pet odors and stains at the source, leaving your home fresh and clean.',
			'highlights'  => array(
				'Pre-treatment with urine stain remover and enzyme disinfectant',
				'Enzymes break down uric acid that ordinary cleaners leave behind',
				'Steam extraction to rinse away stains and detergents',
				'Both-sides steam cleaning and pad replacement when needed',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "We love our pets, but let's be honest &mdash; accidents happen, and it's when the odors hang around that it becomes a real problem. You should be playing on the floor with your pets and inviting people over without worrying for a second about &ldquo;that smell.&rdquo; Our standard pet treatment starts with a pre-treatment using a urine stain remover and enzyme disinfectant. The enzyme is crucial: both dog and cat urine contain uric acid, and most household cleaners can't break it down &mdash; so the uric acid stays behind, and when exposed to humidity its salts re-crystallize and release the smell again (which your pet's sensitive nose can still detect). We then perform steam extraction with detergent using portable and/or truck-mounted systems to remove most stains and detergents, leaving your carpet bright, fresh, and clean. If we really need to pull out the big guns, we also offer steam cleaning for both sides of the carpet plus pad replacement. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		/*
		 * Tile-and-grout and stone restoration were one page on the old site and
		 * in the Blazor build. They are different jobs, bought by different
		 * customers, searched for in different words — so they are two services
		 * here.
		 *
		 * This one keeps the original `tile-stone-and-grout` slug, which matches
		 * its name. That URL has ranked since 2014 and preserving it is worth
		 * more than a tidy slug; the stone work moves to a genuinely new URL,
		 * where there is no history to lose.
		 *
		 * Both names carry "stone" because both jobs touch it: this page cleans
		 * stone, the other resurfaces and polishes it. The copy on each draws
		 * the line.
		 */
		array(
			'slug'        => 'tile-stone-and-grout',
			'name'        => 'Tile, Stone & Grout Cleaning',
			'icon'        => 'bi-grid-3x3-gap-fill',
			'tagline'     => 'Time to call in the big guns.',
			'local_title' => "When cleaning it yourself stops working",
			'local_body'  => "Tile and stone are durable and easy to stay on top of &mdash; right up until they are not, and the stains stay put and the grout no longer looks fresh. Built-up soil does more than look bad: it erodes the surface and can compromise the integrity of the tile or stone, which is how cracks and chips start. Our technicians examine the surface first and choose the products, equipment and method to suit it, rather than applying one process to everything. Our <a href=\"%JOB_PHOTOS%\">job photos</a> page shows the kind of difference it makes.",
			'summary'     => 'Built-up soil dulls tile and darkens grout. We deep-clean both, then seal the grout so it stays clean.',
			'highlights'  => array(
				'Ceramic and porcelain tile deep-cleaned',
				'Grout cleaning, sealing, and color staining',
				'High-pressure hot-water extraction on hard surfaces',
				'Sealing that resists water, dirt, and grease',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "Soil builds up on hard surfaces, and grout is the worst of it &mdash; porous, usually unsealed, and sitting just below the tile where an ordinary mop pushes dirt in rather than lifting it out. Our technicians examine the surface first and choose the products, equipment, and method that suit it, then deep-clean ceramic and porcelain tile using high-pressure hot-water extraction that pulls soil out instead of moving it around. Grout is cleaned back toward its original color, and where it has gone patchy or permanently discolored it can be color-stained to an even finish. We finish by sealing, which penetrates the grout to form an invisible barrier resistant to water, dirt, and grease &mdash; the step that decides whether your floor still looks clean a year from now. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		array(
			'slug'        => 'stone-travertine-restoration',
			'name'        => 'Natural Stone & Travertine Restoration',
			'icon'        => 'bi-gem',
			'tagline'     => 'Bring the shine back to natural stone.',
			'local_title' => "The stone we restore, and how",
			'local_body'  => "Marble, granite, travertine, slate and other natural stone. Restoration is one of our most requested services, and to anyone who has not seen it done it looks a little like magic &mdash; we will admit it more or less is. The process runs in three stages: repairing and resurfacing with diamond abrasive pads and honing powders to lift scratches, marks, stains and light erosion; polishing to bring back the shine and luster; and sealing, which penetrates to form an invisible barrier resistant to water, dirt and grease. Plenty of Houston-area homes have travertine and marble that has never been restored since it was laid. Our <a href=\"%JOB_PHOTOS%\">job photos</a> page has before-and-after examples.",
			'summary'     => 'Travertine, marble, and limestone scratch, etch, and dull with use. We hone, polish, and seal them back to their original finish.',
			'highlights'  => array(
				'Travertine, marble, limestone, and slate restoration',
				'Diamond abrasive honing to lift scratches, marks, and etching',
				'Polishing to restore the original shine and luster',
				'Hole and chip filling on travertine',
				'Penetrating sealer for lasting protection',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "Natural stone is softer than it looks. Travertine, marble, and limestone etch wherever something acidic gets spilled, scratch under grit tracked in from outside, and lose their polish first along the paths people walk most. Cleaning alone will not bring that back &mdash; the surface itself has to be resurfaced. A typical restoration starts with repair, filling the holes and chips travertine develops as its natural voids open up. We then hone the stone with progressively finer diamond abrasive pads and honing powders, which removes scratches, marks, staining, and light erosion by taking the surface back to a fresh, even face. Polishing follows, bringing back the shine and luster the stone had the day it was installed. Finally we seal it, penetrating the stone to form an invisible barrier that resists water, dirt, and grease so the finish lasts. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		array(
			'slug'        => 'upholstery',
			'name'        => 'Upholstery Cleaning',
			'icon'        => 'bi-droplet-half',
			'tagline'     => 'Revive your favorite furniture.',
			'local_title' => "The yearly visit manufacturers actually recommend",
			'local_body'  => "Furniture is a serious investment, and manufacturers are consistent about how to protect it: vacuum often, deal with spills straight away, and book a yearly visit from a professional upholstery cleaner. If you have kept up with all of that, gold star &mdash; you are the client everyone hopes for. If you have been avoiding lifting the cushions because of what might be under them, you are in much larger company, and that is genuinely fine. We match the method to the fabric, and the products we use are baby and pet safe.",
			'summary'     => 'Sofas, chairs, and fabrics get a gentle yet thorough steam clean that revives color and removes embedded grime.',
			'highlights'  => array(
				'Fabric-appropriate cleaning methods for every piece',
				'Removes embedded dust, body oils, and odors',
				'Brightens and revives faded colors',
				'Protects your furniture investment with regular care',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "Furniture can be a significant financial investment. To protect that investment and keep your furniture looking its best, manufacturers recommend frequent maintenance &mdash; vacuuming, immediate attention to spills and stains, and a yearly visit from your local upholstery cleaning professional. If you've been keeping up with all of that, gold star! And if you've avoided lifting those cushions for fear of what's underneath, that's okay too &mdash; you're not alone, and we're here to help. Our upholstery specialists assess each fabric and use the appropriate method to gently but thoroughly steam-clean sofas, chairs, and more, reviving color and removing embedded grime so your favorite pieces look and smell fresh. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
		array(
			'slug'        => 'water-damage-restoration',
			'name'        => 'Water Damage Restoration',
			'icon'        => 'bi-water',
			'tagline'     => 'Fast action when water strikes.',
			'local_title' => "Call as soon as you suspect it",
			'local_body'  => "Water damage is deceptive &mdash; even a small amount of undetected moisture does real harm, from unpleasant odors at best to irreversible flooring damage and mold at worst. That is why the call matters more than being certain: if you think your space is dealing with moisture, give us a call and we will work to get someone out quickly. Our technicians use advanced moisture detection to find the source and any contamination, remove the excess water and dry the space back to its original condition rather than merely dry to the touch. Every water damage job includes a mold inspection &mdash; we evaluate growth, contain the damage, remove contamination and dry materials so it does not return.",
			'summary'     => 'When water strikes, fast action matters. We extract, dry, and restore to minimize damage and prevent mold.',
			'highlights'  => array(
				'Advanced moisture detection to find hidden water and contamination',
				'Rapid water extraction and complete structural drying',
				'Mold inspection on every water-damage job',
				'Containment, contamination removal, and mold prevention',
				'Insured, bonded, and backed by our 10-day no-hassle guarantee',
			),
			'description' => "Water damage is a phrase that can strike fear into the heart, and it's often deceptive &mdash; even a small amount of undetected moisture can cause incredible damage, from unpleasant odors at best to irreversible flooring damage and mold at worst. If you know or suspect your space is dealing with moisture, give us a call. Our experienced technicians use advanced moisture-detection measures to accurately identify moisture sources and any areas of contamination, then remove excess water and completely dry your space back to its original condition. On every water-damage job our technicians also perform a mold inspection &mdash; they're prepared to evaluate mold growth, contain damage, remove contamination, and dry materials to ensure mold won't return. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee.",
		),
	);
}
