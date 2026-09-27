<?php

declare(strict_types=1);


use Metin2Website\Setup\EnvWriter;

/**
 * @return array<class-string, callable(\Metin2Website\Application): object>
 */
return [
    \Metin2Website\Http\Controller\HealthController::class => static fn ($app) => new \Metin2Website\Http\Controller\HealthController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->cmsDb, $app->db,
    ),
    \Metin2Website\Http\Controller\SeoController::class => static fn ($app) => new \Metin2Website\Http\Controller\SeoController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->seo, $app->news, $app->events,
    ),
    \Metin2Website\Http\Controller\HomeController::class => static fn ($app) => new \Metin2Website\Http\Controller\HomeController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->news, $app->events, $app->players, $app->settings,
    ),
    \Metin2Website\Http\Controller\EventsController::class => static fn ($app) => new \Metin2Website\Http\Controller\EventsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->events,
    ),
    \Metin2Website\Http\Controller\NewsController::class => static fn ($app) => new \Metin2Website\Http\Controller\NewsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->news, $app->newsComments, $app->settings,
    ),
    \Metin2Website\Http\Controller\TicketController::class => static fn ($app) => new \Metin2Website\Http\Controller\TicketController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->tickets, $app->ticketUploads, $app->htmlSanitizer, $app->discord,
    ),
    \Metin2Website\Http\Controller\AuthController::class => static fn ($app) => new \Metin2Website\Http\Controller\AuthController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->accounts, $app->settings, $app->accountEmailService, $app->banService, $app->mailer, $app->referralService,
    ),
    \Metin2Website\Http\Controller\AccountController::class => static fn ($app) => new \Metin2Website\Http\Controller\AccountController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->players, $app->accounts, $app->accountEmailService, $app->itemShopOrders, $app->payments, $app->notifications, $app->gameProto, $app->mailer, $app->settings, $app->unstuckService, $app->referralService, $app->gameClock,
    ),
    \Metin2Website\Http\Controller\PasswordController::class => static fn ($app) => new \Metin2Website\Http\Controller\PasswordController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->accountEmailService, $app->mailer, $app->settings,
    ),
    \Metin2Website\Http\Controller\EmailVerificationController::class => static fn ($app) => new \Metin2Website\Http\Controller\EmailVerificationController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->accountEmailService, $app->mailer,
    ),
    \Metin2Website\Http\Controller\StatusController::class => static fn ($app) => new \Metin2Website\Http\Controller\StatusController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->players, $app->serverChannels, $app->settings,
    ),
    \Metin2Website\Http\Controller\DownloadsController::class => static fn ($app) => new \Metin2Website\Http\Controller\DownloadsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->downloads, $app->downloadUploads,
    ),
    \Metin2Website\Http\Controller\DonateController::class => static fn ($app) => new \Metin2Website\Http\Controller\DonateController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->cashPackages, $app->payments, $app->paymentCheckout, $app->cashCredits, $app->paymentGateways, $app->accountEmailService, $app->settings,
    ),
    \Metin2Website\Http\Controller\PaymentWebhookController::class => static fn ($app) => new \Metin2Website\Http\Controller\PaymentWebhookController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->paymentGateways, $app->paymentWebhookProcessor,
    ),
    \Metin2Website\Http\Controller\ItemShopController::class => static fn ($app) => new \Metin2Website\Http\Controller\ItemShopController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->itemShopCategories, $app->itemShopProducts, $app->itemShopPurchases, $app->itemTooltips, $app->settings, $app->accountEmailService,
    ),
    \Metin2Website\Http\Controller\RankingController::class => static fn ($app) => new \Metin2Website\Http\Controller\RankingController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->players, $app->guilds,
    ),
    \Metin2Website\Http\Controller\PlayerController::class => static fn ($app) => new \Metin2Website\Http\Controller\PlayerController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->players, $app->guilds, $app->unstuckService, $app->settings, $app->items, $app->gameClock,
    ),
    \Metin2Website\Http\Controller\GameIconController::class => static fn ($app) => new \Metin2Website\Http\Controller\GameIconController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->icons,
    ),
    \Metin2Website\Http\Controller\ThemeAssetController::class => static fn ($app) => new \Metin2Website\Http\Controller\ThemeAssetController(
        $app->theme, $app->auth, $app->csrf, $app->translator,
    ),
    \Metin2Website\Http\Controller\LocaleController::class => static fn ($app) => new \Metin2Website\Http\Controller\LocaleController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->locales,
    ),
    \Metin2Website\Http\Controller\CaptchaController::class => static fn ($app) => new \Metin2Website\Http\Controller\CaptchaController(
        $app->theme, $app->auth, $app->csrf, $app->translator,
    ),
    \Metin2Website\Http\Controller\SetupController::class => static fn ($app) => new \Metin2Website\Http\Controller\SetupController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->themeCatalog, new EnvWriter(), $app->needsAdminRecovery,
    ),
    \Metin2Website\Http\Controller\Admin\AdminAuthController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminAuthController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->settings, $app->adminTotp,
    ),
    \Metin2Website\Http\Controller\Admin\AdminLocaleController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminLocaleController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->locales,
    ),
    \Metin2Website\Http\Controller\Admin\AdminAccountSecurityController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminAccountSecurityController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->adminTotp, $app->settings,
    ),
    \Metin2Website\Http\Controller\Admin\AdminDashboardController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminDashboardController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->players, $app->paymentStats, $app->playerCensus,
    ),
    \Metin2Website\Http\Controller\Admin\AdminPopulationController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminPopulationController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->playerCensus,
    ),
    \Metin2Website\Http\Controller\Admin\AdminSettingsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminSettingsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->settings, $app->themeCatalog, $app->locales, $app->serverChannels, $app->unstuckService, $app->paymentGateways, $app->seoUploads,
    ),
    \Metin2Website\Http\Controller\Admin\AdminAccountsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminAccountsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->accounts, $app->players, $app->logs, $app->notificationService, $app->settings, $app->gameClock,
    ),
    \Metin2Website\Http\Controller\Admin\AdminCharactersController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminCharactersController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->players, $app->items, $app->guilds, $app->logs, $app->accounts, $app->settings, $app->unstuckService, $app->gameClock, new \Metin2Website\Service\LogEnricher(
            new \Metin2Website\Service\LogRowPresenter($app->translator),
            $app->players,
            $app->gameEconomyScan,
            $app->accounts,
            $app->guilds,
        ),
    ),
    \Metin2Website\Http\Controller\Admin\AdminUnstuckController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminUnstuckController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->settings, $app->unstuckService,
    ),
    \Metin2Website\Http\Controller\Admin\AdminReferralsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminReferralsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->referralRepo, $app->settings,
    ),
    \Metin2Website\Http\Controller\Admin\AdminGameProtoController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminGameProtoController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->gameProto, $app->protoFields, $app->mobDrops, $app->protoEnums, $app->shops,
    ),
    \Metin2Website\Http\Controller\Admin\AdminLogsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminLogsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->logs, new \Metin2Website\Service\LogEnricher(
            new \Metin2Website\Service\LogRowPresenter($app->translator),
            $app->players,
            $app->gameEconomyScan,
            $app->accounts,
            $app->guilds,
        ),
    ),
    \Metin2Website\Http\Controller\Admin\AdminGuildsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminGuildsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->guilds,
    ),
    \Metin2Website\Http\Controller\Admin\AdminGmsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminGmsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->gms, $app->accounts,
    ),
    \Metin2Website\Http\Controller\Admin\AdminAwardsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminAwardsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->awards, $app->accounts, $app->players, $app->gameProto, $app->notificationService,
    ),
    \Metin2Website\Http\Controller\Admin\AdminNewsHubController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminNewsHubController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->news, $app->newsComments, $app->settings, $app->htmlSanitizer, $app->newsUploads, $app->notificationService,
    ),
    \Metin2Website\Http\Controller\Admin\AdminStoreHubController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminStoreHubController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->itemShopCategories, $app->itemShopProducts, $app->itemShopOrders, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminNewsPostsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminNewsPostsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->news, $app->newsComments, $app->settings, $app->htmlSanitizer, $app->newsUploads, $app->notificationService, $app->discord,
    ),
    \Metin2Website\Http\Controller\Admin\AdminNewsCommentsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminNewsCommentsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->news, $app->newsComments, $app->settings, $app->htmlSanitizer, $app->newsUploads, $app->notificationService,
    ),
    \Metin2Website\Http\Controller\Admin\AdminNewsSettingsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminNewsSettingsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->news, $app->newsComments, $app->settings, $app->htmlSanitizer, $app->newsUploads, $app->notificationService,
    ),
    \Metin2Website\Http\Controller\Admin\AdminTicketsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminTicketsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->tickets, $app->ticketUploads, $app->htmlSanitizer, $app->notificationService,
    ),
    \Metin2Website\Http\Controller\Admin\AdminItemShopProductsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminItemShopProductsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->itemShopCategories, $app->itemShopProducts, $app->itemShopOrders, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminItemShopCategoriesController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminItemShopCategoriesController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->itemShopCategories, $app->itemShopProducts, $app->itemShopOrders, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminItemShopCategoryProductsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminItemShopCategoryProductsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->itemShopCategories, $app->itemShopProducts, $app->itemShopOrders, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminItemShopOrdersController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminItemShopOrdersController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->itemShopCategories, $app->itemShopProducts, $app->itemShopOrders, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminShopsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminShopsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->shops, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminRefineController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminRefineController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->refine, $app->gameProto,
    ),
    \Metin2Website\Http\Controller\Admin\AdminAdminsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminAdminsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, new \Metin2Website\Repository\AdminRepository($app->cmsDb, $app->adminRoles), $app->adminRoles, $app->adminTotp,
    ),
    \Metin2Website\Http\Controller\Admin\AdminRolesController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminRolesController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->adminRoles,
    ),
    \Metin2Website\Http\Controller\Admin\AdminAuditLogController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminAuditLogController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, new \Metin2Website\Repository\AdminAuditRepository($app->cmsDb), $app->adminAuditTargets,
    ),
    \Metin2Website\Http\Controller\Admin\AdminBansController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminBansController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->banRepo, $app->banService, $app->accounts,
    ),
    \Metin2Website\Http\Controller\Admin\AdminEconomyController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminEconomyController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->economy, $app->gameEconomyScan, $app->dropFiles, $app->players,
    ),
    \Metin2Website\Http\Controller\Admin\AdminDownloadsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminDownloadsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->downloads, $app->downloadUploads,
    ),
    \Metin2Website\Http\Controller\Admin\AdminBannersHubController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminBannersHubController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->banners, $app->bannerUploads,
    ),
    \Metin2Website\Http\Controller\Admin\AdminEventsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminEventsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->events, $app->eventService, $app->htmlSanitizer, $app->seoUploads,
    ),
    \Metin2Website\Http\Controller\Admin\AdminCommunityController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminCommunityController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->serverChannels, $app->settings, $app->logoUploads,
    ),
    \Metin2Website\Http\Controller\Admin\AdminPaymentMethodsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminPaymentMethodsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->settings,
    ),
    \Metin2Website\Http\Controller\Admin\AdminCashPackagesController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminCashPackagesController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->cashPackages, $app->settings,
    ),
    \Metin2Website\Http\Controller\Admin\AdminPaymentsController::class => static fn ($app) => new \Metin2Website\Http\Controller\Admin\AdminPaymentsController(
        $app->theme, $app->auth, $app->csrf, $app->translator, $app->adminAuth, $app->adminTheme, $app->acl, $app->adminAudit, $app->payments, $app->paymentEvents, $app->paymentWebhookProcessor, $app->cashCredits, $app->paymentExpiry, $app->paymentStats,
    ),
];
