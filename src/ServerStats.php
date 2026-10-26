<?php
/**
 * Server numbers for the banner, cached on disk so viewers never log in to
 * ServerQuery themselves (TeamSpeak flood-bans chatty clients). One request
 * refreshes at a time; the others keep using the last good answer.
 */

declare(strict_types=1);

final class ServerStats
{
    private const FRESH = 30;    // seconds an answer stays fresh
    private const STALE = 600;   // keep showing an older answer this long if the server can't be reached

    public static function get(array $secret, string $cacheDir, string $timezone): array
    {
        $file = $cacheDir . '/dbts-stats.json';
        $cached = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        if (is_array($cached) && time() - (int) ($cached['at'] ?? 0) < self::FRESH) return $cached;

        $lock = @fopen($file . '.lock', 'c');
        if ($lock && !flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return is_array($cached) ? $cached : ['online' => false, 'at' => time()];
        }
        try {
            $stats = self::query($secret);
            $stats['peak'] = self::peakToday($cacheDir, $timezone, $stats['users']);
            @file_put_contents($file, json_encode($stats), LOCK_EX);
            return $stats;
        } catch (Throwable $e) {
            error_log('[DynamicBannerTS] ' . $e->getMessage());
            if (is_array($cached) && !empty($cached['online']) && time() - (int) $cached['at'] < self::STALE) return $cached;
            $offline = ['online' => false, 'at' => time()];
            @file_put_contents($file, json_encode($offline), LOCK_EX); // don't retry a dead server on every view
            return $offline;
        } finally {
            if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
        }
    }

    private static function query(array $secret): array
    {
        $q = new TsQuery((string) $secret['host'], (int) ($secret['query_port'] ?? 10011));
        $q->open();
        try {
            $q->run('login ' . TsQuery::escape((string) $secret['username']) . ' ' . TsQuery::escape((string) $secret['password']));
            $q->run('use port=' . (int) ($secret['voice_port'] ?? 9987));
            $info = $q->run('serverinfo');
        } finally {
            $q->close();
        }
        $users = (int) ($info['virtualserver_clientsonline'] ?? 0) - (int) ($info['virtualserver_queryclientsonline'] ?? 0);
        return [
            'online'   => true,
            'name'     => (string) ($info['virtualserver_name'] ?? ''),
            'users'    => max(0, $users),
            'slots'    => (int) ($info['virtualserver_maxclients'] ?? 0),
            'channels' => (int) ($info['virtualserver_channelsonline'] ?? 0),
            'uptime'   => (int) ($info['virtualserver_uptime'] ?? 0),
            'ping'     => (float) ($info['virtualserver_total_ping'] ?? 0),
            'loss'     => 100 * (float) ($info['virtualserver_total_packetloss_total'] ?? 0),
            'visits'   => (int) ($info['virtualserver_client_connections'] ?? 0),
            'at'       => time(),
        ];
    }

    /** Highest user count seen today, reset at local midnight. */
    private static function peakToday(string $cacheDir, string $timezone, int $users): int
    {
        $file = $cacheDir . '/dbts-peak.json';
        $day = (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format('Y-m-d');
        $saved = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        $peak = ($saved['day'] ?? '') === $day ? max($users, (int) $saved['peak']) : $users;
        if (($saved['day'] ?? '') !== $day || $peak !== (int) ($saved['peak'] ?? -1)) {
            @file_put_contents($file, json_encode(['day' => $day, 'peak' => $peak]), LOCK_EX);
        }
        return $peak;
    }
}
