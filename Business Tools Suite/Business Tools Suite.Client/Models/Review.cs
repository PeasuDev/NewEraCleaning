namespace Business_Tools_Suite.Client.Models;

/// <summary>
/// A customer review gathered from an external source (Google, Yelp, etc.).
/// </summary>
public record Review(string Author, string Source, int Rating, DateOnly Date, string Text);
