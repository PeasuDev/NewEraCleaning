namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Describes a single cleaning service offered by New Era Steam Cleaning &amp;
/// Restoration. Drives the services grid, the per-service detail pages
/// (<c>/services/{Slug}</c>), and the quote form's service picker.
/// </summary>
public sealed record ServiceItem(
    string Slug,
    string Name,
    string Icon,
    string Tagline,
    string Summary,
    IReadOnlyList<string> Highlights,
    string LongDescription);

/// <summary>
/// Central, read-only catalog of the company's services. Keeping this in one
/// place means the home page, the services index, the detail pages, and the
/// quote form all stay in sync.
/// </summary>
public static class ServiceCatalog
{
    /// <summary>All services in display order.</summary>
    public static IReadOnlyList<ServiceItem> All { get; } = new List<ServiceItem>
    {
        new(
            "carpet-cleaning",
            "Carpet Cleaning",
            "bi-stars",
            "Recapture your carpet's youth.",
            "Skilled technicians deep-clean and refresh tired carpets, lifting dirt, stains, and allergens for a like-new look.",
            new[]
            {
                "Furniture moved before and replaced after, with protectors where needed",
                "Spots pre-treated with the appropriate agent for stubborn stains",
                "Optional pre-shampoo with gentle agitation to release soil (add-on)",
                "Powerscrub hot-water steam extraction via truck-mounted systems",
                "Insured, bonded, and backed by our 10-day no-hassle guarantee",
            },
            "Let's face it — dirty carpet is the worst. But that's why we're here. Our skilled technicians are ready to help you recapture your carpet's youth. Here's what you can expect: most furniture (sofas, chairs, and tables) is moved before cleaning and put back afterward, with furniture protectors used where needed. Spots are pre-treated with the appropriate cleaning agent to lift stubborn stains that a typical clean might leave behind, and if desired we can pre-shampoo the carpet with gentle agitation to release and suspend soil (an optional add-on). We then perform Powerscrub steam extraction with detergent using portable equipment and/or truck-mounted systems, removing most soils and detergents to leave your carpet looking bright, smelling fresh, and feeling clean. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
        new(
            "carpet-repairs",
            "Carpet Repairs",
            "bi-tools",
            "Restore damaged carpet seamlessly.",
            "From frays and burns to re-stretching and patching, we restore damaged carpet so it looks and feels seamless again.",
            new[]
            {
                "Open, raveled, or failing seams re-seamed",
                "Burns and melted areas patched",
                "Pulled yarns and missing tufts repaired",
                "Ripples and buckles re-stretched flat",
                "Deteriorated or ruined padding replaced",
            },
            "Carpet is awesome when it's brand new, but we all know that life happens. If your carpet is in need of some TLC, our skilled technicians can lend a helping hand — often for a fraction of the cost of replacement. Some of the common problems our technicians encounter and repair are open or raveled seams, loosening installations, burns and melted areas, pulled yarns and missing tufts, bleached spots and discolorations, ripples and buckles, and deteriorated or ruined padding. We carefully color-match and blend repairs so the finished result looks seamless. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
        new(
            "commercial-carpet-cleaning",
            "Commercial Carpet Cleaning",
            "bi-building-fill",
            "No job too large or too small.",
            "High-traffic commercial spaces need extra care. Our experienced crew services offices and storefronts of any size.",
            new[]
            {
                "Continuous maintenance and cleansing programs",
                "Flexible after-hours and weekend scheduling",
                "High-traffic commercial extraction equipment",
                "No job too large or too small — offices, retail, medical, and more",
                "Insured, bonded, and backed by our 10-day no-hassle guarantee",
            },
            "Commercial carpet receives vastly more traffic than the typical residential carpet. Choosing the right carpet for the space matters, but once it's installed, retaining its beauty and durability relies on continuous maintenance and cleansing programs. Our experienced crew is available to service commercial spaces of every size — no job is too large or too small. We look forward to partnering with you to revitalize your commercial carpet and improve the overall indoor environment of your space, with after-hours and weekend availability and recurring maintenance plans to keep it looking its best year-round. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
        new(
            "pet-odor-removal",
            "Pet Odor Removal",
            "bi-heart-fill",
            "Fresh, clean, and odor-free.",
            "Targeted treatments neutralize stubborn pet odors and stains at the source, leaving your home fresh and clean.",
            new[]
            {
                "Pre-treatment with urine stain remover and enzyme disinfectant",
                "Enzymes break down uric acid that ordinary cleaners leave behind",
                "Steam extraction to rinse away stains and detergents",
                "Both-sides steam cleaning and pad replacement when needed",
                "Insured, bonded, and backed by our 10-day no-hassle guarantee",
            },
            "We love our pets, but let's be honest — accidents happen, and it's when the odors hang around that it becomes a real problem. You should be playing on the floor with your pets and inviting people over without worrying for a second about 'that smell.' Our standard pet treatment starts with a pre-treatment using a urine stain remover and enzyme disinfectant. The enzyme is crucial: both dog and cat urine contain uric acid, and most household cleaners can't break it down — so the uric acid stays behind, and when exposed to humidity its salts re-crystallize and release the smell again (which your pet's sensitive nose can still detect). We then perform steam extraction with detergent using portable and/or truck-mounted systems to remove most stains and detergents, leaving your carpet bright, fresh, and clean. If we really need to pull out the big guns, we also offer steam cleaning for both sides of the carpet plus pad replacement. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
        new(
            "tile-stone-grout",
            "Tile, Stone & Grout Cleaning",
            "bi-grid-3x3-gap-fill",
            "Time to call in the big guns.",
            "Built-up soils erode hard surfaces over time. We deep-clean tile, natural stone, and grout to protect and restore them.",
            new[]
            {
                "Stone cleaning and restoration",
                "Ceramic and porcelain tile cleaning",
                "Grout cleaning, sealing, and color staining",
                "Stone repair, polishing, and sealing process",
                "Insured, bonded, and backed by our 10-day no-hassle guarantee",
            },
            "Soils build up on hard surfaces like tile, natural stone, and grout, and that buildup can lead to erosion. When that happens, it's time to call in the big guns. Our skilled technicians examine your surface and determine the best products, equipment, and methods to restore it to its original glory. Our most common hard-surface services are stone cleaning and restoration, ceramic and porcelain tile cleaning, and grout cleaning, sealing, and color staining. A typical stone restoration involves repairing and resurfacing with diamond abrasive pads and honing powders to remove scratches, marks, stains, and light erosion; polishing to bring back that beautiful shine and luster; and sealing, which penetrates to form an invisible barrier that's resistant to water, dirt, and grease. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
        new(
            "upholstery-cleaning",
            "Upholstery Cleaning",
            "bi-droplet-half",
            "Revive your favorite furniture.",
            "Sofas, chairs, and fabrics get a gentle yet thorough steam clean that revives color and removes embedded grime.",
            new[]
            {
                "Fabric-appropriate cleaning methods for every piece",
                "Removes embedded dust, body oils, and odors",
                "Brightens and revives faded colors",
                "Protects your furniture investment with regular care",
                "Insured, bonded, and backed by our 10-day no-hassle guarantee",
            },
            "Furniture can be a significant financial investment. To protect that investment and keep your furniture looking its best, manufacturers recommend frequent maintenance — vacuuming, immediate attention to spills and stains, and a yearly visit from your local upholstery cleaning professional. If you've been keeping up with all of that, gold star! And if you've avoided lifting those cushions for fear of what's underneath, that's okay too — you're not alone, and we're here to help. Our upholstery specialists assess each fabric and use the appropriate method to gently but thoroughly steam-clean sofas, chairs, and more, reviving color and removing embedded grime so your favorite pieces look and smell fresh. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
        new(
            "water-damage-restoration",
            "Water Damage Restoration",
            "bi-water",
            "Fast action when water strikes.",
            "When water strikes, fast action matters. We extract, dry, and restore to minimize damage and prevent mold.",
            new[]
            {
                "Advanced moisture detection to find hidden water and contamination",
                "Rapid water extraction and complete structural drying",
                "Mold inspection on every water-damage job",
                "Containment, contamination removal, and mold prevention",
                "Insured, bonded, and backed by our 10-day no-hassle guarantee",
            },
            "Water damage is a phrase that can strike fear into the heart, and it's often deceptive — even a small amount of undetected moisture can cause incredible damage, from unpleasant odors at best to irreversible flooring damage and mold at worst. If you know or suspect your space is dealing with moisture, give us a call. Our experienced technicians use advanced moisture-detection measures to accurately identify moisture sources and any areas of contamination, then remove excess water and completely dry your space back to its original condition. On every water-damage job our technicians also perform a mold inspection — they're prepared to evaluate mold growth, contain damage, remove contamination, and dry materials to ensure mold won't return. Our technicians are insured and bonded, and every job is backed by a 10-day, no-hassle guarantee."),
    };

    /// <summary>Finds a service by its URL slug, or <c>null</c> if not found.</summary>
    public static ServiceItem? FindBySlug(string? slug) =>
        All.FirstOrDefault(s => string.Equals(s.Slug, slug, StringComparison.OrdinalIgnoreCase));

    /// <summary>Service display names, used to populate the quote form's picker.</summary>
    public static IReadOnlyList<string> Names { get; } = All.Select(s => s.Name).ToList();
}
