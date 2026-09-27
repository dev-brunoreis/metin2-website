<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\Grid\Definitions\PlayersGrid;
use Metin2Website\Admin\Grid\GridRunner;
use Metin2Website\Admin\LogCatalog;
use Metin2Website\Auth\AdminAuth;
use Metin2Website\Auth\Auth;
use Metin2Website\Auth\Csrf;
use Metin2Website\Game\GameClock;
use Metin2Website\Game\InventoryLayout;
use Metin2Website\Http\Response;
use Metin2Website\I18n\Translator;
use Metin2Website\Repository\AccountRepository;
use Metin2Website\Repository\GuildRepository;
use Metin2Website\Repository\ItemRepository;
use Metin2Website\Repository\LogRepository;
use Metin2Website\Repository\PlayerRepository;
use Metin2Website\Service\AclService;
use Metin2Website\Service\AdminAuditService;
use Metin2Website\Service\LogEnricher;
use Metin2Website\Service\SettingsService;
use Metin2Website\Theme\ThemeEngine;
use Metin2Website\Service\UnstuckService;

class AdminCharactersController extends AdminController
{
    /** @var array<string, string> */
    private const CHARACTER_TAB_TEMPLATES = [
        'logs' => 'components/character-logs.twig',
        'items' => 'components/character-items.twig',
        'guild' => 'components/character-guild.twig',
        'marriage' => 'components/character-marriage.twig',
    ];

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
        private ItemRepository $items,
        private GuildRepository $guilds,
        private LogRepository $logs,
        private AccountRepository $accounts,
        private SettingsService $settings,
        private UnstuckService $unstuck,
        private GameClock $gameClock,
        private LogEnricher $logEnricher,
    ) {
        parent::__construct($theme, $auth, $csrf, $translator, $adminAuth, $adminTheme, $auditLog, $acl);
    }

    public function index(): Response
    {
        $spec = PlayersGrid::definition()->spec();
        $query = $this->gridQuery($spec);
        $minutes = $this->settings->onlineWindowMinutes();
        $grid = GridRunner::fetch(
            $spec,
            $query,
            fn ($q) => $this->players->countForGrid($q),
            fn ($q) => $this->gameClock->markOnline($this->players->listForGrid($q), $minutes),
            ['onlineWindowMinutes' => $minutes],
        );

        return $this->adminView('characters', 'pages/characters.twig', [
            'title' => $this->t('admin.characters.title'),
            'pageLead' => $this->t('admin.characters.lead'),
            'grid' => $grid,
        ]);
    }

    public function show(string $id): Response
    {
        if ($guard = $this->denyUnlessAdmin()) {
            return $guard;
        }

        $character = $this->players->findForAdmin((int) $id);

        if ($character === null) {
            $this->flash('error', $this->t('admin.characters.not_found'));

            return $this->redirect('/admin/game/characters');
        }

        $playerId = (int) $character['id'];
        $accountId = (int) ($character['account_id'] ?? 0);
        $name = (string) $character['name'];
        $tab = $this->requestedTab(['data', 'logs', 'items', 'guild', 'marriage'], 'data');
        $data = [
            'title' => $this->t('admin.characters.view_title', ['name' => $name]),
            'pageLead' => $this->t('admin.characters.view_lead'),
            'character' => $character,
            'activeTab' => $tab,
            'characterLogs' => [],
            'characterItems' => [],
            'characterItemLayout' => null,
            'safebox' => null,
            'safeboxLayout' => null,
            'guild' => null,
            'marriage' => null,
            'unstuckAvailable' => $this->unstuck->hasPositionColumns(),
            'unstuckOffline' => $this->unstuck->isOffline($playerId),
            'onlineWindowMinutes' => $this->settings->onlineWindowMinutes(),
            'characterOnline' => $this->gameClock->isRecentlyActive(
                (string) ($character['last_play'] ?? ''),
                $this->settings->onlineWindowMinutes(),
            ),
        ];

        if ($tab === 'logs') {
            $data['characterLogs'] = $this->decorateLogs($this->logs->listForCharacter($playerId, $name), 'player');
        }

        if ($tab === 'items') {
            $characterItems = $this->items->forCharacter($playerId);
            $safebox = $accountId > 0 ? $this->items->safeboxForAccount($accountId) : null;
            $data['characterItems'] = $characterItems;
            $data['characterItemLayout'] = InventoryLayout::forCharacter($characterItems);
            $data['safebox'] = $safebox;
            $data['safeboxLayout'] = $safebox !== null
                ? InventoryLayout::forAccount($safebox['items'], (int) $safebox['size'])
                : null;
        }

        if ($tab === 'guild') {
            $data['guild'] = $this->guilds->profileForPlayer($playerId);
        }

        if ($tab === 'marriage') {
            $data['marriage'] = $this->players->findMarriageForPlayer($playerId);
        }

        if ($this->wantsTabPartial()) {
            $template = self::CHARACTER_TAB_TEMPLATES[$tab] ?? null;

            if ($template === null) {
                return Response::notFound();
            }

            return $this->adminFragment($template, $data);
        }

        return $this->adminView('characters', 'pages/character.twig', $data);
    }

    public function unstuck(string $id): Response
    {
        if ($redirect = $this->requireAdminResource('game/characters/unstuck')) {
            return $redirect;
        }

        if (!$this->assertCsrf()) {
            $this->flash('error', $this->t('auth.invalid_csrf'));

            return $this->redirect('/admin/game/characters/' . (int) $id);
        }

        $character = $this->players->findForAdmin((int) $id);

        if ($character === null) {
            $this->flash('error', $this->t('admin.characters.not_found'));

            return $this->redirect('/admin/game/characters');
        }

        $playerId = (int) $character['id'];
        $accountId = (int) ($character['account_id'] ?? 0);

        try {
            $this->unstuck->unstuck($playerId, $accountId, true);
            $this->audit('character.unstuck', 'player', $playerId, [
                'name' => (string) ($character['name'] ?? ''),
                'account_id' => $accountId,
            ]);
            $this->flash('success', $this->t('admin.unstuck.done'));
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $this->flash('error', $this->t($e->getMessage()));
        }

        return $this->redirect('/admin/game/characters/' . $playerId);
    }

    public function showOwnedItem(string $id): Response
    {
        if ($guard = $this->denyUnlessAdmin()) {
            return $guard;
        }

        $itemId = (int) $id;

        if ($itemId < 1) {
            $this->flash('error', $this->t('admin.owned_items.not_found'));

            return $this->redirect('/admin/game/characters');
        }

        $tab = $this->requestedTab(['data', 'logs'], 'data');
        $item = $this->items->findById($itemId);
        $itemLogs = [];

        if ($item === null || $tab === 'logs') {
            $itemLogs = $this->decorateLogs($this->logs->listForItem($itemId), 'item');
        }

        if ($item === null && $itemLogs === []) {
            $this->flash('error', $this->t('admin.owned_items.not_found'));

            return $this->redirect('/admin/game/characters');
        }

        $owner = null;
        $ownerKind = null;

        if ($item !== null) {
            $ownerId = (int) $item['owner_id'];
            $ownerKind = ItemRepository::isAccountWindow((string) $item['window']) ? 'account' : 'character';
            $owner = $ownerKind === 'account'
                ? $this->accounts->findById($ownerId)
                : $this->players->findById($ownerId);
        }

        $name = is_array($item) ? (string) ($item['name'] ?? '') : '';
        $title = $name !== ''
            ? $this->t('admin.owned_items.view_title', ['name' => $name, 'id' => (string) $itemId])
            : $this->t('admin.owned_items.view_title_id', ['id' => (string) $itemId]);
        $data = [
            'title' => $title,
            'pageLead' => $this->t('admin.owned_items.lead'),
            'itemId' => $itemId,
            'item' => $item,
            'owner' => $owner,
            'ownerKind' => $ownerKind,
            'itemLogs' => $itemLogs,
            'activeTab' => $tab,
        ];

        if ($this->wantsTabPartial()) {
            if ($tab !== 'logs') {
                return Response::notFound();
            }

            return $this->adminFragment('components/owned-item-logs.twig', $data);
        }

        return $this->adminView('characters', 'pages/owned-item.twig', $data);
    }

    /**
     * @param list<array{id: string, label: string, columns: list<string>, dateColumn: string|null, itemColumns?: list<string>, rows: list<array<string, mixed>>}> $groups
     * @return list<array{id: string, label: string, filterKey: string, columns: list<array{key: string, label: string}>, dateColumns: list<string>, itemColumns: list<string>, rows: list<array<string, mixed>>}>
     */
    private function decorateLogs(array $groups, string $filterFrom = 'player'): array
    {
        $decorated = [];

        foreach ($groups as $group) {
            $catalog = LogCatalog::get($group['id']) ?? ['playerColumns' => [], 'itemColumns' => []];
            $filterKey = $filterFrom === 'item'
                ? (string) (($catalog['itemColumns'][0] ?? '') ?: '')
                : (string) (($catalog['playerColumns'][0] ?? '') ?: '');
            $rows = $this->logEnricher->decorate($group['id'], $group['rows']);
            $decorated[] = [
                'id' => $group['id'],
                'label' => $group['label'],
                'filterKey' => $filterKey,
                'columns' => array_merge(
                    [['key' => '_summary', 'label' => $this->t('admin.logs.columns.summary')]],
                    $this->columnLabels($group['columns']),
                ),
                'dateColumns' => $this->dateColumns($group['columns']),
                'itemColumns' => $group['itemColumns'] ?? [],
                'rows' => $rows,
            ];
        }

        return $decorated;
    }

    /**
     * @param list<string> $columns
     * @return list<array{key: string, label: string}>
     */
    private function columnLabels(array $columns): array
    {
        $labels = [];

        foreach ($columns as $column) {
            $key = 'admin.logs.columns.' . $column;
            $labels[] = [
                'key' => $column,
                'label' => $this->translator->has($key) ? $this->t($key) : $column,
            ];
        }

        return $labels;
    }

    /**
     * @param list<string> $columns
     * @return list<string>
     */
    private function dateColumns(array $columns): array
    {
        $known = ['time', 'date', 'login_time', 'logout_time', 'start_time', 'end_time', 'first_seen', 'last_seen'];
        $found = array_values(array_intersect($columns, $known));

        if (in_array('date', $found, true) && in_array('time', $found, true)) {
            $found = array_values(array_diff($found, ['time']));
        }

        return $found;
    }
}
