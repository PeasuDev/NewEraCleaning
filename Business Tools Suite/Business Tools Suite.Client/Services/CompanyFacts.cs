namespace Business_Tools_Suite.Client.Services;

/// <summary>
/// Single source of truth for company-wide marketing facts that need to stay in
/// sync across the public site. Centralizing these means the home page, the about
/// page, and any future copy all read from one place.
/// </summary>
public static class CompanyFacts
{
    /// <summary>The calendar year the advertised experience figure was anchored.</summary>
    private const int ExperienceAnchorYear = 2026;

    /// <summary>Years of experience advertised as of <see cref="ExperienceAnchorYear"/>.</summary>
    private const int ExperienceAtAnchor = 25;

    /// <summary>
    /// Years of industry experience to advertise. Starts at 25+ in 2026 and
    /// automatically increases by one each calendar year. Never reports less than
    /// the anchored value.
    /// <para>
    /// <b>This is not the age of the company.</b> New Era was founded in 2014, so
    /// this figure is deliberately larger than the business is old: it describes
    /// the trade experience the technicians bring, which predates the company.
    /// The copy says "hands-on experience" and "technicians with N+ years in the
    /// trade" for that reason. Confirmed with the owner on 7 Sep 2026 — if the
    /// two ever need to agree, change the copy, not this number.
    /// </para>
    /// </summary>
    public static int YearsOfExperience =>
        ExperienceAtAnchor + Math.Max(0, DateTime.Now.Year - ExperienceAnchorYear);
}
