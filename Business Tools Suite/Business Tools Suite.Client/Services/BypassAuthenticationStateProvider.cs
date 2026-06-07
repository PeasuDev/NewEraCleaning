using System.Security.Claims;
using Microsoft.AspNetCore.Components.Authorization;

namespace Business_Tools_Suite.Client.Services;

// TEMPORARY: Bypasses authentication so the New Era Cleaning admin portal can be
// accessed without logging in. This always reports an authenticated user, which
// satisfies the [Authorize] attributes, AuthorizeRouteView, and AuthorizeView used
// throughout the admin pages.
//
// To re-add authentication later, remove this class and restore the original
// AuthenticationStateProvider registrations in both Program.cs files:
//   - Client: builder.Services.AddAuthenticationStateDeserialization();
//   - Server: builder.Services.AddScoped<AuthenticationStateProvider, IdentityRevalidatingAuthenticationStateProvider>();
public sealed class BypassAuthenticationStateProvider : AuthenticationStateProvider
{
    private static readonly Task<AuthenticationState> AuthenticatedState =
        Task.FromResult(CreateAuthenticatedState());

    public override Task<AuthenticationState> GetAuthenticationStateAsync() => AuthenticatedState;

    private static AuthenticationState CreateAuthenticatedState()
    {
        var identity = new ClaimsIdentity(
            new[]
            {
                new Claim(ClaimTypes.Name, "Admin (auth bypassed)"),
            },
            authenticationType: "BypassAuth");

        return new AuthenticationState(new ClaimsPrincipal(identity));
    }
}
