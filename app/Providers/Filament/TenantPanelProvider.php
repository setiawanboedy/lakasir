<?php

namespace App\Providers\Filament;

use App\Filament\Tenant\Pages\EditProfile;
use App\Filament\Tenant\Pages\TenantLogin;
use App\Filament\Tenant\Resources\CategoryResource;
use App\Filament\Tenant\Resources\GalleryResource;
use App\Filament\Tenant\Resources\MemberResource;
use App\Filament\Tenant\Resources\PermissionResource;
use App\Filament\Tenant\Resources\ProductResource;
use App\Filament\Tenant\Resources\RoleResource;
use App\Filament\Tenant\Resources\UserResource;
use App\Tenant;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;

class TenantPanelProvider extends PanelProvider
{
    public static $abortRequest;

    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('tenant')
            ->colors([
                'primary' => Color::hex('#FF6600'),
            ])
            ->spa()
            ->authGuard('web')
            ->path('/member')
            ->login(TenantLogin::class)
            ->navigation(function (NavigationBuilder $navigationBuilder) {
                /** @var \App\Models\User $user */
                $user = Filament::auth()->user();

                return $navigationBuilder
                    ->items([
                        ...Pages\Dashboard::getNavigationItems(),
                        NavigationItem::make()->label('Master'),
                        ...($user?->can('read member') ? MemberResource::getNavigationItems() : []),
                        ...($user?->can('read category') ? CategoryResource::getNavigationItems() : []),
                        ...($user?->can('read product') ? ProductResource::getNavigationItems() : []),
                        NavigationItem::make()->label('User'),
                        ...($user?->can('read user') ? UserResource::getNavigationItems() : []),
                        ...($user?->can('read role') ? RoleResource::getNavigationItems() : []),
                        ...($user?->can('read permission') ? PermissionResource::getNavigationItems() : []),
                        NavigationItem::make()->label('Gallery'),
                        ...GalleryResource::getNavigationItems(),
                    ]);

            })
            ->profile(EditProfile::class)
            ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources')
            ->discoverPages(in: app_path('Filament/Tenant/Pages'), for: 'App\\Filament\\Tenant\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Tenant/Widgets'), for: 'App\\Filament\\Tenant\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        $url = request()->getHost();
        if (in_array($url, config('tenancy.central_domains'))) {
            return $panel;
        }
        $domain = explode('.'.config('tenancy.central_domains')[0], $url);
        $domain = explode('.', $domain[0]);
        if (! in_array($domain[0], ['', 'localhost', config('tenancy.central_domains')[0]])) {
            if ($domain[0] === 'www') {
                $domain[0] = $domain[1];
            }
            $tenant = Tenant::find($domain[0]);
            if (! $tenant) {
                abort(404);
            }
            tenancy()->initialize($domain[0]);
            $about = $tenant?->user?->about;
            $subdomain = $tenant?->domains()->where('domain', $url)->first()?->domain;
            $panel
                ->brandName($about->shop_name ?? 'Your Brand')
                ->brandLogo($about->photo ?? null)
                ->domain($subdomain);

            $db = app(DatabaseTenancyBootstrapper::class);
            $db->bootstrap($tenant);

        }

        return $panel;
    }
}
