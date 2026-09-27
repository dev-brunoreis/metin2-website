<?php

declare(strict_types=1);

use Metin2Website\Admin\AdminRuntime;
use Metin2Website\Application;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Game\Display;
use Metin2Website\Game\GameClock;
use Metin2Website\Game\Drop\GroupTextParser;
use Metin2Website\Game\GameProfile;
use Metin2Website\Game\ItemDescCatalog;
use Metin2Website\Game\ItemIconCatalog;
use Metin2Website\Game\ItemStats;
use Metin2Website\Game\Proto\ProtoEnums;
use Metin2Website\Game\Proto\ProtoFormFields;
use Metin2Website\Game\Proto\ProtoIndexCache;
use Metin2Website\Game\Proto\ProtoSchemas;
use Metin2Website\Mail\SymfonyMailer;
use Metin2Website\Payment\GatewayRegistry;
use Metin2Website\Payment\PayPalGateway;
use Metin2Website\Repository\AccountEmailRepository;
use Metin2Website\Repository\AccountRepository;
use Metin2Website\Repository\AclRepository;
use Metin2Website\Repository\AdminAuditRepository;
use Metin2Website\Repository\AdminRepository;
use Metin2Website\Repository\AdminRoleRepository;
use Metin2Website\Repository\AdminTotpRepository;
use Metin2Website\Repository\BanRepository;
use Metin2Website\Repository\EconomyRepository;
use Metin2Website\Repository\GameEconomyScanRepository;
use Metin2Website\Repository\CashPackageRepository;
use Metin2Website\Repository\DownloadRepository;
use Metin2Website\Repository\BannerRepository;
use Metin2Website\Repository\EmailTokenRepository;
use Metin2Website\Repository\EventRepository;
use Metin2Website\Repository\GmRepository;
use Metin2Website\Repository\GuildRepository;
use Metin2Website\Repository\ItemAwardRepository;
use Metin2Website\Repository\ItemRepository;
use Metin2Website\Repository\ItemShopCategoryRepository;
use Metin2Website\Repository\ItemShopOrderRepository;
use Metin2Website\Repository\ItemShopProductRepository;
use Metin2Website\Repository\LogRepository;
use Metin2Website\Repository\NewsCommentRepository;
use Metin2Website\Repository\NewsRepository;
use Metin2Website\Repository\NotificationRepository;
use Metin2Website\Repository\PaymentEventRepository;
use Metin2Website\Repository\PaymentRepository;
use Metin2Website\Repository\PlayerRepository;
use Metin2Website\Repository\ProtoNameRepository;
use Metin2Website\Repository\ReferralRepository;
use Metin2Website\Repository\RefineRepository;
use Metin2Website\Repository\ServerChannelRepository;
use Metin2Website\Repository\SettingsRepository;
use Metin2Website\Repository\ShopRepository;
use Metin2Website\Repository\TicketRepository;
use Metin2Website\Repository\UnstuckRepository;
use Metin2Website\Service\AccountEmailService;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\AdminAuditTargetService;
use Metin2Website\Service\BanService;
use Metin2Website\Service\CashCreditService;
use Metin2Website\Service\DiscordWebhookService;
use Metin2Website\Service\DownloadUploadService;
use Metin2Website\Service\BannerUploadService;
use Metin2Website\Service\LogoUploadService;
use Metin2Website\Service\SeoImageUploadService;
use Metin2Website\Service\SeoService;
use Metin2Website\Service\BannerSeedService;
use Metin2Website\Service\EventSeedService;
use Metin2Website\Service\NewsSeedService;
use Metin2Website\Service\ImageVariantService;
use Metin2Website\Service\DropFileService;
use Metin2Website\Service\EventService;
use Metin2Website\Service\GameIconService;
use Metin2Website\Service\GameProtoService;
use Metin2Website\Service\ItemShopPurchaseService;
use Metin2Website\Service\ItemTooltipBuilder;
use Metin2Website\Service\MobDropService;
use Metin2Website\Service\NewsUploadService;
use Metin2Website\Service\NotificationService;
use Metin2Website\Service\PaymentCheckoutService;
use Metin2Website\Service\PaymentExpiryService;
use Metin2Website\Service\PaymentStatsService;
use Metin2Website\Service\PlayerCensusService;
use Metin2Website\Service\PaymentWebhookProcessor;
use Metin2Website\Service\ReferralService;
use Metin2Website\Service\SettingsService;
use Metin2Website\Service\TicketUploadService;
use Metin2Website\Service\UnstuckService;
use Metin2Website\Support\Database;
use Metin2Website\Support\HtmlSanitizer;
use Metin2Website\Support\Log;
use Metin2Website\I18n\Translator;

/**
 * Wire installed-app services onto Application public props.
 * Call assertAppKey / cmsDb / assertSchemaCurrent before this.
 *
 * @return callable(Application): void
 */
return static function (Application $app): void {
    $adminRepo = new AdminRepository($app->cmsDb);
    $app->needsAdminRecovery = $adminRepo->count() === 0;
    $app->adminAuth = new AdminAuth($adminRepo);
    $app->adminRoles = new AdminRoleRepository($app->cmsDb);
    $app->adminTotp = new AdminTotpRepository($app->cmsDb);
    $app->acl = new AclService(new AclRepository($app->cmsDb), $app->adminRoles);

    $app->settingsRepo = new SettingsRepository($app->cmsDb);
    $app->settings = new SettingsService($app->settingsRepo, $app->themeCatalog);
    $app->htmlSanitizer = new HtmlSanitizer();
    $app->seo = new SeoService($app->settings, $app->htmlSanitizer);
    $app->newsUploads = new NewsUploadService(BASE_DIR . '/public');
    $app->logoUploads = new LogoUploadService(BASE_DIR . '/public');
    $app->seoUploads = new SeoImageUploadService(BASE_DIR . '/public');
    $app->ticketUploads = new TicketUploadService(BASE_DIR . '/var/uploads/tickets');
    $app->news = new NewsRepository($app->cmsDb);
    $app->newsComments = new NewsCommentRepository($app->cmsDb);
    $app->tickets = new TicketRepository($app->cmsDb);
    $app->itemShopCategories = new ItemShopCategoryRepository($app->cmsDb);
    $app->itemShopProducts = new ItemShopProductRepository($app->cmsDb);
    $app->itemShopOrders = new ItemShopOrderRepository($app->cmsDb);
    $app->accountEmails = new AccountEmailRepository($app->cmsDb);
    $app->emailTokens = new EmailTokenRepository($app->cmsDb);
    $app->banRepo = new BanRepository($app->cmsDb);
    $app->economy = new EconomyRepository($app->cmsDb);
    $app->unstuckRepo = new UnstuckRepository($app->cmsDb);
    $app->events = new EventRepository($app->cmsDb);
    $app->serverChannels = new ServerChannelRepository($app->cmsDb);
    $app->downloads = new DownloadRepository($app->cmsDb);
    $app->downloadUploads = new DownloadUploadService(BASE_DIR . '/var/downloads');
    $app->banners = new BannerRepository($app->cmsDb);
    $app->bannerUploads = new BannerUploadService(
        BASE_DIR . '/public',
        new ImageVariantService(),
    );
    (new BannerSeedService(
        $app->banners,
        $app->bannerUploads,
        $app->settingsRepo,
    ))->seedIfNeeded();
    (new NewsSeedService(
        $app->news,
        $adminRepo,
        $app->settingsRepo,
        $app->htmlSanitizer,
    ))->seedIfNeeded();
    (new EventSeedService(
        $app->events,
        $app->settingsRepo,
        $app->htmlSanitizer,
    ))->seedIfNeeded();
    $app->cashPackages = new CashPackageRepository($app->cmsDb);
    $app->payments = new PaymentRepository($app->cmsDb);
    $app->paymentEvents = new PaymentEventRepository($app->cmsDb);
    $app->notifications = new NotificationRepository($app->cmsDb);
    $app->notificationService = new NotificationService($app->notifications);
    $app->mailer = new SymfonyMailer(
        $app->settings->mailFromAddress(),
        $app->settings->mailFromName(),
    );
    $app->paypal = new PayPalGateway($app->settings);
    $app->paymentGateways = new GatewayRegistry();
    $app->paymentGateways->register($app->paypal);

    $defaultLocale = $app->settings->defaultLocale();
    $app->translator = new Translator(BASE_DIR . '/lang', $app->locales->resolve($defaultLocale));
    $app->gameProfile = GameProfile::load();
    $app->protoSchemas = new ProtoSchemas($app->gameProfile);
    $app->protoEnums = new ProtoEnums($app->gameProfile);
    $app->itemStats = new ItemStats($app->protoEnums);
    $app->icons = new GameIconService(
        $app->gameProfile->path('icon_root'),
        BASE_DIR . '/var/cache/icons',
        $app->gameProfile,
        new ItemIconCatalog($app->gameProfile->path('item_list')),
    );
    $activeTheme = $app->settings->activeTheme();
    $app->db = new Database();
    $app->gameClock = new GameClock($app->db);
    $app->theme = $app->createThemeEngine($activeTheme, $app->settings->registrationEnabled(), false, $app->gameClock);
    $app->theme->setSeoService($app->seo);
    $app->discord = new DiscordWebhookService($app->settings);
    $app->eventService = new EventService($app->events, $app->discord);
    $app->adminTheme = $app->createThemeEngine('admin', true, true, $app->gameClock);
    $app->gameEconomyScan = new GameEconomyScanRepository($app->db);
    $app->accounts = new AccountRepository($app->db);
    $app->referralRepo = new ReferralRepository($app->cmsDb, $app->accounts);
    $app->players = new PlayerRepository($app->db);
    $app->items = new ItemRepository(
        $app->db,
        new ItemDescCatalog($app->gameProfile->path('itemdesc')),
        $app->itemStats,
    );
    $app->guilds = new GuildRepository($app->db);

    $themeWindowMinutes = $app->settings->onlineWindowMinutes();
    $themeDownloads = $app->downloads->listPublic();
    $app->theme->setGlobals([
        'has_news' => $app->news->countPublished() > 0,
        'captchaEnabled' => $app->settings->captchaPublicEnabled(),
        'discord_invite_url' => $app->settings->discordInviteUrl(),
        'site_title' => $app->settings->siteTitle(),
        'site_logo' => $app->settings->siteLogo(),
        'footer_text' => $app->settings->footerText(),
        'social_links' => $app->settings->socialLinksEnabled(),
        'news_show_views' => $app->settings->newsShowViews(),
        'theme_players_online' => $app->players->countActiveSinceMinutes($themeWindowMinutes),
        'theme_accounts_online' => $app->players->countAccountsActiveSinceMinutes($themeWindowMinutes),
        'theme_window_minutes' => $themeWindowMinutes,
        'theme_top_players' => $app->players->listRanking(1, 10),
        'theme_upcoming_events' => $app->events->upcomingPublished(4),
        'theme_client_download' => $themeDownloads[0] ?? null,
        'site_banners' => $app->banners->listEnabled(),
        'banner_settings' => $app->settings->bannerSettings(),
        'banners_seeded' => $app->settingsRepo->get('banners_seeded') === '1',
        'layout_columns' => $app->settings->layoutColumns(),
        'layout_sidebar' => $app->settings->layoutSidebar(),
    ]);

    $app->gms = new GmRepository($app->db);
    $app->awards = new ItemAwardRepository($app->db);
    $app->shops = new ShopRepository($app->db);
    $app->refine = new RefineRepository($app->db);
    $app->logs = new LogRepository($app->db);
    $app->gameProto = new GameProtoService(
        $app->gameProfile,
        $app->protoSchemas,
        new ProtoNameRepository($app->db),
        new ProtoIndexCache(BASE_DIR . '/var/cache'),
    );
    $groupParser = new GroupTextParser();
    $app->mobDrops = new MobDropService(
        $app->gameProfile,
        $app->gameProto,
        $groupParser,
    );
    $app->dropFiles = new DropFileService(
        $app->gameProfile,
        $groupParser,
    );
    $app->protoFields = new ProtoFormFields($app->translator, $app->protoEnums);
    $app->auth = new Auth($app->accounts);
    $app->banService = new BanService($app->banRepo, $app->accounts, $app->notificationService);
    $app->unstuckService = new UnstuckService($app->unstuckRepo, $app->players, $app->settings, $app->gameClock);
    $app->referralService = new ReferralService(
        $app->referralRepo,
        $app->accounts,
        $app->players,
        $app->settings,
    );
    $app->accountEmailService = new AccountEmailService(
        $app->accounts,
        $app->accountEmails,
        $app->emailTokens,
        $app->mailer,
        $app->settings,
    );
    $app->cashCredits = new CashCreditService(
        $app->payments,
        $app->accounts,
        $app->discord,
        $app->notificationService,
    );
    $app->paymentExpiry = new PaymentExpiryService(
        $app->payments,
        $app->paymentGateways,
        $app->notificationService,
    );
    $app->paymentStats = new PaymentStatsService($app->payments);
    $app->playerCensus = new PlayerCensusService(
        $app->accounts,
        $app->players,
        new Display($app->translator),
        $app->translator,
    );
    $app->paymentCheckout = new PaymentCheckoutService(
        $app->cashPackages,
        $app->payments,
        $app->paymentGateways,
        $app->settings,
        $app->notificationService,
        $app->paymentExpiry,
    );
    $app->paymentWebhookProcessor = new PaymentWebhookProcessor(
        $app->paymentEvents,
        $app->payments,
        $app->paymentGateways,
        $app->cashCredits,
    );
    $auditEntries = new AdminAuditRepository($app->cmsDb);
    $app->adminAudit = new AdminAuditService(
        $auditEntries,
        $app->adminAuth,
    );
    $app->adminAuditTargets = new AdminAuditTargetService(
        $app->accounts,
        $app->players,
        $app->guilds,
        $auditEntries,
    );
    $app->itemShopPurchases = new ItemShopPurchaseService(
        $app->itemShopProducts,
        $app->itemShopOrders,
        $app->accounts,
        $app->awards,
        $app->notificationService,
    );
    $app->itemTooltips = new ItemTooltipBuilder(
        $app->gameProto,
        $app->itemStats,
        $app->protoEnums,
        new ItemDescCatalog($app->gameProfile->path('itemdesc')),
    );

    $accountId = $app->auth->id();
    $unread = 0;

    if ($accountId !== null) {
        try {
            $app->paymentExpiry->expireDue();
            $unread = $app->notifications->countUnread($accountId);
        } catch (\Throwable $e) {
            Log::error('payments', 'Pending payment expiry or notification count failed', $e);
        }
    }

    $app->theme->setGlobals(['notification_unread' => $unread]);
    $app->attachAdminNavCounts();
    AdminRuntime::bind($app->settings);
};
