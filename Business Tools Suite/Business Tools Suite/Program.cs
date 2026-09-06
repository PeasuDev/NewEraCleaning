using Business_Tools_Suite.Client.Pages;
using Business_Tools_Suite.Components;
using Business_Tools_Suite.Components.Account;
using Business_Tools_Suite.Data;
using Microsoft.AspNetCore.Components.Authorization;
using Microsoft.AspNetCore.Identity;
using Microsoft.EntityFrameworkCore;

var builder = WebApplication.CreateBuilder(args);

// Add services to the container.
builder.Services.AddRazorComponents()
    .AddInteractiveServerComponents()
    .AddInteractiveWebAssemblyComponents()
    .AddAuthenticationStateSerialization();

builder.Services.AddCascadingAuthenticationState();
builder.Services.AddScoped<IdentityRedirectManager>();

// TEMPORARY: Authentication bypassed so the admin portal is accessible without login.
// To re-enable auth, remove the bypass line and restore the IdentityRevalidatingAuthenticationStateProvider registration.
//builder.Services.AddScoped<AuthenticationStateProvider, IdentityRevalidatingAuthenticationStateProvider>();
builder.Services.AddScoped<AuthenticationStateProvider, Business_Tools_Suite.Client.Services.BypassAuthenticationStateProvider>();

var authenticationBuilder = builder.Services.AddAuthentication(options =>
    {
        options.DefaultScheme = IdentityConstants.ApplicationScheme;
        options.DefaultSignInScheme = IdentityConstants.ExternalScheme;
    });
authenticationBuilder.AddIdentityCookies();

// Enable "Sign in with Google" when credentials are configured. Provide the
// values via configuration or user-secrets:
//   Authentication:Google:ClientId / Authentication:Google:ClientSecret
var googleClientId = builder.Configuration["Authentication:Google:ClientId"];
var googleClientSecret = builder.Configuration["Authentication:Google:ClientSecret"];
if (!string.IsNullOrWhiteSpace(googleClientId) && !string.IsNullOrWhiteSpace(googleClientSecret))
{
    authenticationBuilder.AddGoogle(googleOptions =>
    {
        googleOptions.ClientId = googleClientId;
        googleOptions.ClientSecret = googleClientSecret;
    });
}

var connectionString = builder.Configuration.GetConnectionString("DefaultConnection") ?? throw new InvalidOperationException("Connection string 'DefaultConnection' not found.");
builder.Services.AddDbContext<ApplicationDbContext>(options =>
    options.UseSqlServer(connectionString));
builder.Services.AddDatabaseDeveloperPageExceptionFilter();

builder.Services.AddIdentityCore<ApplicationUser>(options =>
    {
        options.SignIn.RequireConfirmedAccount = true;
        options.Stores.SchemaVersion = IdentitySchemaVersions.Version3;
    })
    .AddEntityFrameworkStores<ApplicationDbContext>()
    .AddSignInManager()
    .AddDefaultTokenProviders();

builder.Services.AddSingleton<IEmailSender<ApplicationUser>, IdentityNoOpEmailSender>();

// Restrict portal access to a configured allow-list of email accounts.
builder.Services.Configure<Business_Tools_Suite.LoginAllowListOptions>(
    builder.Configuration.GetSection(Business_Tools_Suite.LoginAllowListOptions.SectionName));

// Shared placeholder data service for dashboard pages (also used during prerendering).
builder.Services.AddSingleton<Business_Tools_Suite.Client.Services.PlaceholderDataService>();

// Server-side Yelp Fusion review integration. The API key is read from
// configuration/user-secrets and never reaches the browser. Set the values via:
//   Yelp:ApiKey / Yelp:BusinessId
builder.Services.Configure<Business_Tools_Suite.Services.YelpReviewOptions>(
    builder.Configuration.GetSection(Business_Tools_Suite.Services.YelpReviewOptions.SectionName));
builder.Services.AddHttpClient("Yelp", client =>
{
    client.BaseAddress = new Uri("https://api.yelp.com/");
});
builder.Services.AddSingleton<Business_Tools_Suite.Services.ServerYelpReviewProvider>();
builder.Services.AddSingleton<Business_Tools_Suite.Client.Services.IReviewProvider>(sp =>
    sp.GetRequiredService<Business_Tools_Suite.Services.ServerYelpReviewProvider>());

// Outbound email for public quote requests. The SMTP credentials are read from
// configuration/user-secrets and never reach the browser. Set the values via:
//   QuoteMail:UserName / QuoteMail:Password / QuoteMail:ToAddress
// User-secrets only load in Development; when the app is started without a launch
// profile, supply the same values as environment variables (QuoteMail__UserName).
builder.Services.Configure<Business_Tools_Suite.Services.QuoteMailOptions>(
    builder.Configuration.GetSection(Business_Tools_Suite.Services.QuoteMailOptions.SectionName));
builder.Services.AddScoped<
    Business_Tools_Suite.Client.Services.IQuoteSubmissionService,
    Business_Tools_Suite.Services.SmtpQuoteSubmissionService>();

var app = builder.Build();

// Configure the HTTP request pipeline.
if (app.Environment.IsDevelopment())
{
    app.UseWebAssemblyDebugging();
    app.UseMigrationsEndPoint();
}
else
{
    app.UseExceptionHandler("/Error", createScopeForErrors: true);
    // The default HSTS value is 30 days. You may want to change this for production scenarios, see https://aka.ms/aspnetcore-hsts.
    app.UseHsts();
}
app.UseStatusCodePagesWithReExecute("/not-found", createScopeForStatusCodePages: true);
app.UseHttpsRedirection();

app.UseAntiforgery();

app.MapStaticAssets();
app.MapRazorComponents<App>()
    .AddInteractiveServerRenderMode()
    .AddInteractiveWebAssemblyRenderMode()
    .AddAdditionalAssemblies(typeof(Business_Tools_Suite.Client._Imports).Assembly);

// Add additional endpoints required by the Identity /Account Razor components.
app.MapAdditionalIdentityEndpoints();

// Live reviews endpoint consumed by the WebAssembly client. Runs the
// authenticated Yelp Fusion request server-side so the API key stays private.
app.MapGet("/api/reviews/live", async (
    Business_Tools_Suite.Client.Services.IReviewProvider reviewProvider,
    CancellationToken cancellationToken) =>
{
    var reviews = await reviewProvider.GetLiveReviewsAsync(cancellationToken);
    return Results.Ok(reviews);
});

// Quote form submissions posted by the WebAssembly client. The mail credentials stay
// on the server, so the browser posts the request here instead of sending it itself.
// Antiforgery is disabled because this is a JSON POST from the WASM runtime rather
// than a rendered form post.
app.MapPost("/api/quote", async (
    Business_Tools_Suite.Client.Models.QuoteRequest request,
    Business_Tools_Suite.Client.Services.IQuoteSubmissionService quoteSubmission,
    Business_Tools_Suite.Client.Services.PlaceholderDataService dataService,
    CancellationToken cancellationToken) =>
{
    // The browser's copy of the validation rules can be bypassed, so re-check here.
    var validationError = Business_Tools_Suite.Services.QuoteRequestValidator.Validate(request);

    if (validationError is not null)
    {
        return Results.Text(validationError, "text/plain", statusCode: StatusCodes.Status400BadRequest);
    }

    // Keep the server-side queue in step with the interactive-server path, which
    // records the request through the same service.
    dataService.AddQuoteRequest(request);

    var result = await quoteSubmission.SubmitAsync(request, cancellationToken);

    return result.Sent
        ? Results.Ok()
        : Results.Text(
            result.ErrorMessage ?? "We couldn't send your request. Please call us instead.",
            "text/plain",
            statusCode: StatusCodes.Status502BadGateway);
}).DisableAntiforgery();

app.Run();
