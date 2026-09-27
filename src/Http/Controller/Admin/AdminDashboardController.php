<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\AdminPaths;
use Metin2Website\Admin\Grid\Definitions\DashboardPlayersGrid;
use Metin2Website\Admin\Grid\GridRunner;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\PlayerRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\PaymentStatsService;
use Metin2Website\Service\PlayerCensusService;
use Metin2Website\Theme\ThemeEngine;

class AdminDashboardController extends AdminController
{
    public function __construct(
        ThemeEngine $theme,
        Auth $auth,
        Csrf $csrf,
        Translator $translator,
        AdminAuth $adminAuth,
        ThemeEngine $adminTheme,
        AclService $acl,
        AdminAuditService $auditLog,
        private PlayerRepository $players,
        private PaymentStatsService $paymentStats,
        private PlayerCensusService $census,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function index(): Response
    {
        $user = $this->adminAuth->user();
        $canStats = $this->acl->isAllowed($user, 'overview/dashboard/stats/view');
        $canPlayers = $this->acl->isAllowed($user, 'overview/dashboard/players/view');
        $canPayments = $this->acl->isAllowed($user, 'store/payments/view');
        $canPopulation = $this->acl->isAllowed($user, 'overview/population/view');

        $data = [
            'title' => $this->t('admin.dashboard.title'),
            'pageLead' => $this->t('admin.dashboard.lead'),
            'canStats' => $canStats,
            'canPlayers' => $canPlayers,
            'canPayments' => $canPayments,
            'canPopulation' => $canPopulation,
        ];

        if ($canStats) {
            $spec = DashboardPlayersGrid::definition()->spec();
            $query = $this->gridQuery($spec);
            $range = $query->filter('range', '5m');
            $minutes = PlayerRepository::rangeMinutes($range);
            $data['playerCount'] = $this->players->countActiveSinceMinutes($minutes);
            $data['accountCount'] = $this->players->countAccountsActiveSinceMinutes($minutes);
            $totals = $this->census->totals();
            $data['totalAccounts'] = $totals['account_count'];
            $data['totalCharacters'] = $totals['character_count'];
        }

        if ($canPopulation) {
            $data['populationHref'] = AdminPaths::population();
        }

        if ($canPayments) {
            $finance = $this->paymentStats->dashboardSnapshot((string) ($_GET['finance'] ?? '7d'));
            $data['finance'] = $finance;
            $data['financeRange'] = $finance['range'];
            $data['financeRangeOptions'] = PaymentStatsService::RANGE_OPTIONS;
            $data['paymentsHref'] = AdminPaths::storePayments();
        }

        if ($canPlayers) {
            $spec = DashboardPlayersGrid::definition()->spec();
            $query = $this->gridQuery($spec);
            $data['grid'] = GridRunner::fetch(
                $spec,
                $query,
                fn ($q) => $this->players->countActiveForGrid($q),
                fn ($q) => $this->players->listActiveForGrid($q),
            );
        }

        return $this->adminView('dashboard', 'pages/dashboard.twig', $data);
    }
}
