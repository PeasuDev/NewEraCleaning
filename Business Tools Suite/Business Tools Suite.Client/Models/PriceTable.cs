namespace Business_Tools_Suite.Client.Models;

/// <summary>
/// A titled group of coupon price rows shown on the public Pricing page and
/// edited from the admin Pricing page. Mutable so the admin form can bind to it.
/// </summary>
public sealed class PriceTable
{
    public PriceTable()
    {
    }

    public PriceTable(string title, List<PriceRow> rows)
    {
        Title = title;
        Rows = rows;
    }

    /// <summary>Heading shown above the table (e.g. "Carpet Cleaning").</summary>
    public string Title { get; set; } = "";

    /// <summary>The service rows that make up this table.</summary>
    public List<PriceRow> Rows { get; set; } = new();

    /// <summary>Creates an independent copy so edits don't mutate live data until saved.</summary>
    public PriceTable Clone() => new(Title, Rows.Select(r => r.Clone()).ToList());
}

/// <summary>
/// A single row of the coupon price tables: a service, its description, and the
/// price or per-incident charge.
/// </summary>
public sealed class PriceRow
{
    public PriceRow()
    {
    }

    public PriceRow(string service, string description, string price)
    {
        Service = service;
        Description = description;
        Price = price;
    }

    /// <summary>Service name shown in the row header.</summary>
    public string Service { get; set; } = "";

    /// <summary>Optional description of what the service includes.</summary>
    public string Description { get; set; } = "";

    /// <summary>Price or per-incident charge displayed in the amount column.</summary>
    public string Price { get; set; } = "";

    /// <summary>Creates an independent copy of this row.</summary>
    public PriceRow Clone() => new(Service, Description, Price);
}
