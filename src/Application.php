<?php

declare(strict_types=1);

namespace Metin2Website;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Metin2Website\Admin\AdminPaths;
use Metin2Website\Admin\AdminSections;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Auth\SessionConfig;
use Metin2Website\Auth\SessionGuard;
use Metin2Website\Http\Controller\SetupController;
use Metin2Website\Http\AdminRoutes;
use Metin2Website\Http\PublicRoutes;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Locales;
use Metin2Website\I18n\Translator;
use Metin2Website\Support\Database;
use Metin2Website\Support\Env;
use Metin2Website\Support\Money;
use Metin2Website\Repository\AccountRepository;
use Metin2Website\Repository\AdminAuditRepository;
use Metin2Website\Repository\AdminRepository;
use Metin2Website\Game\Proto\ProtoIndexCache;
use Metin2Website\Repository\GmRepository;
use Metin2Website\Repository\GuildRepository;
use Metin2Website\Repository\ItemAwardRepository;
use Metin2Website\Repository\ItemRepository;
use Metin2Website\Repository\LogRepository;
use Metin2Website\Repository\NewsCommentRepository;
use Metin2Website\Repository\NewsRepository;
use Metin2Website\Repository\PlayerRepository;
use Metin2Website\Repository\ProtoNameRepository;
use Metin2Website\Repository\RefineRepository;
use Metin2Website\Repository\SettingsRepository;
use Metin2Website\Repository\ItemShopCategoryRepository;
use Metin2Website\Repository\ItemShopOrderRepository;
use Metin2Website\Repository\ItemShopProductRepository;
use Metin2Website\Repository\ShopRepository;
use Metin2Website\Repository\TicketRepository;
use Metin2Website\Game\GameClock;
use Metin2Website\Game\GameProfile;
use Metin2Website\Game\ItemDescCatalog;
use Metin2Website\Game\ItemIconCatalog;
use Metin2Website\Game\ItemStats;
use Metin2Website\Game\Proto\ProtoEnums;
use Metin2Website\Game\Proto\ProtoFormFields;
use Metin2Website\Game\Proto\ProtoSchemas;
use Metin2Website\Repository\AclRepository;
use Metin2Website\Repository\AdminRoleRepository;
use Metin2Website\Repository\AdminTotpRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\AdminAuditTargetService;
use Metin2Website\Service\DropFileService;
use Metin2Website\Service\GameIconService;
use Metin2Website\Service\GameProtoService;
use Metin2Website\Service\ItemShopPurchaseService;
use Metin2Website\Service\ItemTooltipBuilder;
use Metin2Website\Service\MobDropService;
use Metin2Website\Service\NewsUploadService;
use Metin2Website\Service\LogoUploadService;
use Metin2Website\Service\SeoImageUploadService;
use Metin2Website\Service\SeoService;
use Metin2Website\Service\SettingsService;
use Metin2Website\Service\TicketUploadService;
use Metin2Website\Repository\BanRepository;
use Metin2Website\Service\BanService;
use Metin2Website\Repository\EconomyRepository;
use Metin2Website\Repository\GameEconomyScanRepository;
use Metin2Website\Repository\ReferralRepository;
use Metin2Website\Service\ReferralService;
use Metin2Website\Repository\UnstuckRepository;
use Metin2Website\Service\UnstuckService;
use Metin2Website\Service\DiscordWebhookService;
use Metin2Website\Repository\EventRepository;
use Metin2Website\Service\EventService;
use Metin2Website\Mail\MailerInterface;
use Metin2Website\Mail\SymfonyMailer;
use Metin2Website\Payment\GatewayRegistry;
use Metin2Website\Payment\PayPalGateway;
use Metin2Website\Repository\AccountEmailRepository;
use Metin2Website\Repository\CashPackageRepository;
use Metin2Website\Repository\DownloadRepository;
use Metin2Website\Repository\BannerRepository;
use Metin2Website\Repository\EmailTokenRepository;
use Metin2Website\Repository\NotificationRepository;
use Metin2Website\Repository\PaymentEventRepository;
use Metin2Website\Repository\PaymentRepository;
use Metin2Website\Repository\ServerChannelRepository;
use Metin2Website\Service\AccountEmailService;
use Metin2Website\Service\CashCreditService;
use Metin2Website\Service\DownloadUploadService;
use Metin2Website\Service\BannerUploadService;
use Metin2Website\Service\NotificationService;
use Metin2Website\Service\PaymentCheckoutService;
use Metin2Website\Service\PaymentExpiryService;
use Metin2Website\Service\PaymentStatsService;
use Metin2Website\Service\PlayerCensusService;
use Metin2Website\Service\PaymentWebhookProcessor;
use Metin2Website\Setup\CmsSchema;
use Metin2Website\Setup\EnvWriter;
use Metin2Website\Setup\MigrationRunner;
use Metin2Website\Setup\SetupInstaller;
use Metin2Website\Setup\ThemeCatalog;
use Metin2Website\Support\CmsVersion;
use Metin2Website\Support\HtmlSanitizer;
use Metin2Website\Support\Log;
use Metin2Website\Theme\AdminAclTwigExtension;
use Metin2Website\Theme\ThemeEngine;

use function FastRoute\simpleDispatcher;

class Application
{
    use \Metin2Website\Http\ControllerMap;
    private bool $installed;

    /** True when APP_INSTALLED is set but the admins table has no rows. */
    public bool $needsAdminRecovery = false;

    /** DI container — public for controller_factories.php */
    public Database $db;
    public Database $cmsDb;
    public Auth $auth;
    public AdminAuth $adminAuth;
    public Csrf $csrf;
    public Locales $locales;
    public Translator $translator;
    public ThemeEngine $theme;
    public ThemeEngine $adminTheme;
    public AccountRepository $accounts;
    public PlayerRepository $players;
    public ItemRepository $items;
    public GuildRepository $guilds;
    public GmRepository $gms;
    public ItemAwardRepository $awards;
    public ShopRepository $shops;
    public RefineRepository $refine;
    public LogRepository $logs;
    public GameProtoService $gameProto;
    public MobDropService $mobDrops;
    public DropFileService $dropFiles;
    public ?GameIconService $icons = null;
    public GameProfile $gameProfile;
    public ProtoSchemas $protoSchemas;
    public ProtoEnums $protoEnums;
    public ItemStats $itemStats;
    public ProtoFormFields $protoFields;
    public SettingsRepository $settingsRepo;
    public SettingsService $settings;
    public GameClock $gameClock;
    public ThemeCatalog $themeCatalog;
    public NewsRepository $news;
    public NewsCommentRepository $newsComments;
    public TicketRepository $tickets;
    public ItemShopCategoryRepository $itemShopCategories;
    public ItemShopProductRepository $itemShopProducts;
    public ItemShopOrderRepository $itemShopOrders;
    public ItemShopPurchaseService $itemShopPurchases;
    public ItemTooltipBuilder $itemTooltips;
    public HtmlSanitizer $htmlSanitizer;
    public NewsUploadService $newsUploads;
    public LogoUploadService $logoUploads;
    public SeoImageUploadService $seoUploads;
    public SeoService $seo;
    public TicketUploadService $ticketUploads;
    public AdminAuditService $adminAudit;
    public AdminAuditTargetService $adminAuditTargets;
    public AclService $acl;
    public AdminRoleRepository $adminRoles;
    public AdminTotpRepository $adminTotp;
    public MailerInterface $mailer;
    public AccountEmailRepository $accountEmails;
    public EmailTokenRepository $emailTokens;
    public AccountEmailService $accountEmailService;
    public BanRepository $banRepo;
    public BanService $banService;
    public UnstuckRepository $unstuckRepo;
    public UnstuckService $unstuckService;
    public ReferralRepository $referralRepo;
    public ReferralService $referralService;
    public ServerChannelRepository $serverChannels;
    public DownloadRepository $downloads;
    public DownloadUploadService $downloadUploads;
    public BannerRepository $banners;
    public BannerUploadService $bannerUploads;
    public CashPackageRepository $cashPackages;
    public PaymentRepository $payments;
    public PaymentEventRepository $paymentEvents;
    public PaymentWebhookProcessor $paymentWebhookProcessor;
    public NotificationRepository $notifications;
    public NotificationService $notificationService;
    public PayPalGateway $paypal;
    public GatewayRegistry $paymentGateways;
    public CashCreditService $cashCredits;
    public PaymentCheckoutService $paymentCheckout;
    public PaymentExpiryService $paymentExpiry;
    public PaymentStatsService $paymentStats;
    public PlayerCensusService $playerCensus;
    public EventRepository $events;
    public EventService $eventService;
    public DiscordWebhookService $discord;
    public EconomyRepository $economy;
    public GameEconomyScanRepository $gameEconomyScan;

    public function __construct()
    {
        self::loadConfigs();
        $this->configureSession();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        SessionGuard::enforceIdleTimeout(SessionConfig::forRequestUri($uri));

        $this->installed = $this->isInstalled();
        $this->locales = new Locales(BASE_DIR . '/lang');
        $this->themeCatalog = new ThemeCatalog(BASE_DIR . '/themes');
        $this->csrf = new Csrf();

        if (!$this->installed) {
            $this->bootstrapSetup();

            return;
        }

        $this->bootstrapInstalled();
    }

    public function run(): void
    {
        try {
            if (!$this->installed) {
                $this->runSetupOnly();

                return;
            }

            if ($this->needsAdminRecovery) {
                $this->runAdminRecoverySetup();

                return;
            }

            if (SetupInstaller::hasCompleteSession()) {
                if ($this->normalizeUri() === '/setup') {
                    $this->runSetupCompleteRoutes();

                    return;
                }

                SetupInstaller::clearCompleteSession();
            }

            $this->dispatch(function (RouteCollector $r): void {
                PublicRoutes::register($r);
                AdminRoutes::register($r);
            }, true);
        } catch (\Throwable $e) {
            Log::error('app', 'Unhandled exception', $e);
            Response::html('Internal Server Error', 500)->send();
        }
    }

    private function runSetupOnly(): void
    {
        $this->runSetupRoutes('/setup');
    }

    private function runAdminRecoverySetup(): void
    {
        $this->runSetupRoutes('/setup?step=admin');
    }

    /** Success screen after wizard finishes (APP_INSTALLED already true). */
    private function runSetupCompleteRoutes(): void
    {
        try {
            $this->dispatch(function (RouteCollector $r): void {
                $r->addRoute('GET', '/setup', [SetupController::class, 'show']);
                $r->addRoute('POST', '/setup', [SetupController::class, 'submit']);
            }, false);
        } catch (\Throwable $e) {
            Log::error('app', 'Unhandled exception during setup complete', $e);
            Response::html('Internal Server Error', 500)->send();
        }
    }

    private function runSetupRoutes(string $redirectTarget): void
    {
        try {
            $uri = $this->normalizeUri();

            if ($uri !== '/setup' && $uri !== '/setup/test-connection') {
                Response::redirect($redirectTarget)->send();

                return;
            }

            $this->dispatch(function (RouteCollector $r): void {
                $r->addRoute('GET', '/setup', [SetupController::class, 'show']);
                $r->addRoute('POST', '/setup', [SetupController::class, 'submit']);
                $r->addRoute('POST', '/setup/test-connection', [SetupController::class, 'testConnection']);
            }, false);
        } catch (\Throwable $e) {
            Log::error('app', 'Unhandled exception during setup', $e);
            Response::html('Internal Server Error', 500)->send();
        }
    }

    /**
     * @param \Closure(RouteCollector): void $registerRoutes
     */
    private function dispatch(callable $registerRoutes, bool $allowSetup404): void
    {
        $dispatcher = simpleDispatcher($registerRoutes);

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->normalizeUri();

        if ($allowSetup404 && str_starts_with($uri, '/setup')) {
            Response::notFound($this->theme->render('player', [
                'title' => $this->translator->get('http.not_found'),
                'player' => null,
                'notFound' => true,
                'auth' => [
                    'check' => $this->auth->check(),
                    'login' => $this->auth->login(),
                    'user' => $this->auth->user(),
                ],
                'csrf' => $this->csrf->token(),
                'flash' => null,
            ]))->send();

            return;
        }

        $routeInfo = $dispatcher->dispatch($method, $uri);

        match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => Response::notFound($this->theme->render('player', [
                'title' => $this->translator->get('http.not_found'),
                'player' => null,
                'notFound' => true,
                'auth' => $this->authContext(),
                'csrf' => $this->csrf->token(),
                'flash' => null,
            ]))->send(),
            Dispatcher::METHOD_NOT_ALLOWED => Response::html(
                $this->translator->get('http.method_not_allowed'),
                405,
            )->send(),
            Dispatcher::FOUND => $this->invoke($routeInfo[1], $routeInfo[2])->send(),
        };
    }

    private function bootstrapSetup(): void
    {
        (require __DIR__ . '/bootstrap/setup_services.php')($this);
    }

    private function bootstrapInstalled(): void
    {
        $this->assertAppKey();
        $this->cmsDb = Database::forCms();
        $this->assertSchemaCurrent();
        (require __DIR__ . '/bootstrap/installed_services.php')($this);
    }

    public function createThemeEngine(string $activeTheme, bool $registrationEnabled, bool $isAdmin, ?GameClock $gameClock = null): ThemeEngine
    {
        $engine = new ThemeEngine(
            BASE_DIR . '/themes',
            $activeTheme,
            $this->translator,
            $this->locales->available(),
            $this->icons ?? null,
            isset($this->settings) ? $this->settings->moneyFormat() : Money::FORMAT_DOT,
            $gameClock,
        );

        $globals = ['registration_enabled' => $registrationEnabled];

        if ($isAdmin) {
            $admin = $this->adminAuth->check() ? $this->adminAuth->user() : null;
            $sections = $this->acl->filterSections($admin, AdminSections::all());
            $sections = AdminSections::withoutUnavailableProto($sections, $this->gameProfile);
            $globals['admin_sections'] = $sections;
            $globals['admin_pinned_nav'] = AdminSections::pinnedNavItem($sections);
            $globals['cms_version'] = CmsVersion::read();
            $engine->addExtension(new AdminAclTwigExtension($this->acl, $this->adminAuth));
        }

        $engine->setGlobals($globals);

        return $engine;
    }

    public function attachAdminNavCounts(): void
    {
        if (!$this->adminAuth->check()) {
            return;
        }

        $pendingComments = $this->newsComments->countPending();
        $openTickets = $this->tickets->countOpen();

        $globals = [
            'admin_nav_counts' => [
                'news' => $pendingComments,
                'tickets' => $openTickets,
            ],
        ];

        if ($pendingComments > 0) {
            $admin = $this->adminAuth->user();
            $sections = $this->acl->filterSections($admin, AdminSections::all());
            $sections = AdminSections::withoutUnavailableProto($sections, $this->gameProfile);
            $sections = AdminSections::withChildPath(
                $sections,
                'news',
                AdminPaths::contentNews('comments'),
            );
            $globals['admin_sections'] = $sections;
            $globals['admin_pinned_nav'] = AdminSections::pinnedNavItem($sections);
        }

        $this->adminTheme->setGlobals($globals);
    }

    /**
     * @return array{check: bool, login: string|null, user: array<string, mixed>|null}
     */
    private function authContext(): array
    {
        return [
            'check' => $this->auth->check(),
            'login' => $this->auth->login(),
            'user' => $this->auth->user(),
        ];
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<string, string> $vars
     */
    private function invoke(array $handler, array $vars): Response
    {
        [$class, $method] = $handler;
        $controller = $this->resolveController($class);

        return $controller->{$method}(...$this->routeArguments($controller, $method, $vars));
    }

    /**
     * FastRoute always yields string captures; coerce to the action parameter types.
     *
     * @param array<string, string> $vars
     * @return list<mixed>
     */
    private function routeArguments(object $controller, string $method, array $vars): array
    {
        $args = [];

        foreach ((new \ReflectionMethod($controller, $method))->getParameters() as $param) {
            $name = $param->getName();

            if (!array_key_exists($name, $vars)) {
                break;
            }

            $args[] = $this->castRouteArgument($param, $vars[$name]);
        }

        return $args;
    }

    private function castRouteArgument(\ReflectionParameter $param, string $value): mixed
    {
        $type = $param->getType();

        if (!$type instanceof \ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => (int) $value,
            'float' => (float) $value,
            default => $value,
        };
    }

    private function isInstalled(): bool
    {
        $value = self::getEnv()->get('APP_INSTALLED', '');

        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
    }

    private function normalizeUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        return rawurldecode($uri);
    }

    public static function getEnv(): Env
    {
        return Env::getInstance();
    }

    public static function loadConfigs(): void
    {
        Env::load();
    }

    private function configureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $config = SessionConfig::forRequestUri($uri);

        $sessionDir = dirname(__DIR__) . '/var/sessions';

        if (!is_dir($sessionDir)) {
            mkdir($sessionDir, 0750, true);
        }

        ini_set('session.save_path', $sessionDir);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.name', $config->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $config->path,
            'secure' => SessionConfig::isSecureRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function assertSchemaCurrent(): void
    {
        $runner = new MigrationRunner($this->cmsDb);
        $current = $runner->readCurrentVersion();
        $latest = MigrationRunner::latestVersion();

        if ($current >= $latest) {
            return;
        }

        Log::error(
            'app',
            sprintf(
                'Database schema is out of date (current=%d, latest=%d). Run: php bin/migrate.php',
                $current,
                $latest,
            ),
        );
        $this->serviceUnavailable();
    }

    private function assertAppKey(): void
    {
        if (\Metin2Website\Support\AppCrypto::hasValidKey()) {
            return;
        }

        Log::error('app', 'APP_KEY is missing or invalid. Run: php bin/migrate.php');
        $this->serviceUnavailable();
    }

    private function serviceUnavailable(): void
    {
        Response::html('Service temporarily unavailable', 503)->send();
        exit;
    }
}
