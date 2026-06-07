using Microsoft.AspNetCore.Components.WebAssembly.Hosting;
using Business_Tools_Suite.Client.Services;

var builder = WebAssemblyHostBuilder.CreateDefault(args);

builder.Services.AddAuthorizationCore();
builder.Services.AddCascadingAuthenticationState();
builder.Services.AddAuthenticationStateDeserialization();
builder.Services.AddSingleton<PlaceholderDataService>();

await builder.Build().RunAsync();
