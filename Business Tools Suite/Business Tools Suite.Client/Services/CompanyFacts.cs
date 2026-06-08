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
    /// </summary>
    public static int YearsOfExperience =>
        ExperienceAtAnchor + Math.Max(0, DateTime.Now.Year - ExperienceAnchorYear);
}
