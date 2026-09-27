<?php

declare(strict_types=1);

namespace Mt2Cms\Tests\Unit\Game;

use Mt2Cms\Game\GameProfile;
use PHPUnit\Framework\TestCase;

final class GameProfileTest extends TestCase
{
    public function testLoadDoesNotRequireProtoOrClientDumps(): void
    {
        $dir = sys_get_temp_dir() . '/mt2cms-game-profile-' . bin2hex(random_bytes(4));
        mkdir($dir . '/schema', 0777, true);
        copy(BASE_DIR . '/game/config.json', $dir . '/config.json');
        copy(BASE_DIR . '/game/schema/item.json', $dir . '/schema/item.json');
        copy(BASE_DIR . '/game/schema/mob.json', $dir . '/schema/mob.json');

        $profile = GameProfile::load($dir);

        self::assertSame('en', $profile->locale());
        self::assertNotSame([], $profile->columns(GameProfile::KIND_ITEM));
        self::assertSame($dir . '/db/item_proto.txt', $profile->path('item_proto'));
        self::assertSame($dir . '/client/itemdesc.txt', $profile->path('itemdesc'));
        self::assertFileDoesNotExist($profile->path('item_proto'));
        self::assertFileDoesNotExist($profile->path('itemdesc'));
        self::assertFalse($profile->protoFilesReady(GameProfile::KIND_ITEM));
        self::assertFalse($profile->protoFilesReady(GameProfile::KIND_MOB));
    }

    public function testProtoFilesReadyRequiresBothProtoAndNames(): void
    {
        $dir = sys_get_temp_dir() . '/mt2cms-game-profile-' . bin2hex(random_bytes(4));
        mkdir($dir . '/schema', 0777, true);
        mkdir($dir . '/db', 0777, true);
        copy(BASE_DIR . '/game/config.json', $dir . '/config.json');
        copy(BASE_DIR . '/game/schema/item.json', $dir . '/schema/item.json');
        copy(BASE_DIR . '/game/schema/mob.json', $dir . '/schema/mob.json');
        file_put_contents($dir . '/db/item_proto.txt', "VNUM\n");
        file_put_contents($dir . '/db/item_names_en.txt', "VNUM\n");

        $profile = GameProfile::load($dir);

        self::assertTrue($profile->protoFilesReady(GameProfile::KIND_ITEM));
        self::assertFalse($profile->protoFilesReady(GameProfile::KIND_MOB));

        file_put_contents($dir . '/db/mob_proto.txt', "VNUM\n");
        file_put_contents($dir . '/db/mob_names_en.txt', "VNUM\n");

        self::assertTrue($profile->protoFilesReady(GameProfile::KIND_MOB));
    }
}
