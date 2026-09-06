using Microsoft.AspNetCore.Components.Authorization;
using Microsoft.AspNetCore.Components.WebAssembly.Hosting;
using Business_Tools_Suite.Client.Services;

var builder = WebAssemblyHostBuilder.CreateDefault(args);

builder.Services.AddAuthorizationCore();
builder.Services.AddCascadingAuthenticationState();

// TEMPORARY: Authentication bypassed so the admin portal is accessible without login.
// To re-enable auth, remove the bypass line and restore AddAuthenticationStateDeserialization().
//builder.Services.AddAuthenticationStateDeserialization();
builder.Services.AddSingleton<AuthenticationStateProvider, BypassAuthenticationStateProvider>();

builder.Services.AddSingleton<PlaceholderDataService>();

// Live reviews are fetched from the server endpoint so the API key never
// ships to the browser. A preconfigured HttpClient uses the app's base address.
builder.Services.AddScoped(sp => new HttpClient
{
    BaseAddress = new Uri(builder.HostEnvironment.BaseAddress)
});
builder.Services.AddScoped<IReviewProvider, ClientLiveReviewProvider>();

// Quote requests are posted to the server endpoint for the same reason: the mail
// account's app password must never ship to the browser.
builder.Services.AddScoped<IQuoteSubmissionService, ClientQuoteSubmissionService>();

await builder.Build().RunAsync();
