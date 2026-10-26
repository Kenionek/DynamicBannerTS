<?php
/**
 * Minimal TeamSpeak 3 ServerQuery client: connect, run a command, read one
 * result row. Plain sockets with short timeouts, no framework needed.
 */

declare(strict_types=1);

final class TsQuery
{
    /** @var resource|null */
    private $socket = null;

    public function __construct(private string $host, private int $port, private int $timeout = 3)
    {
    }

    public static function escape(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '/' => '\\/', ' ' => '\\s', '|' => '\\p', "\x07" => '\\a',
            "\x08" => '\\b', "\f" => '\\f', "\n" => '\\n', "\r" => '\\r', "\t" => '\\t', "\v" => '\\v']);
    }

    public static function unescape(string $value): string
    {
        return strtr($value, ['\\\\' => '\\', '\\/' => '/', '\\s' => ' ', '\\p' => '|', '\\a' => "\x07",
            '\\b' => "\x08", '\\f' => "\f", '\\n' => "\n", '\\r' => "\r", '\\t' => "\t", '\\v' => "\v"]);
    }

    /** Opens the connection and checks the ServerQuery greeting. */
    public function open(): void
    {
        $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$socket) throw new RuntimeException("cannot reach {$this->host}:{$this->port}");
        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;
        $greeting = (string) fgets($socket) . (string) fgets($socket);
        if (!str_contains($greeting, 'TS3')) {
            $this->close();
            throw new RuntimeException('not a TeamSpeak ServerQuery port');
        }
    }

    /** Runs $command and returns its first result row as [key => value]. */
    public function run(string $command): array
    {
        if (!$this->socket) throw new RuntimeException('not connected');
        fwrite($this->socket, $command . "\n");
        $payload = '';
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 65536);
            if ($line === false) throw new RuntimeException('timed out waiting for the server');
            $line = ltrim($line, "\r"); // replies end in "\n\r", so the \r opens the next line
            if (str_starts_with($line, 'error ')) {
                if (!preg_match('/\bid=0\b/', $line)) throw new RuntimeException('query refused: ' . trim($line));
                break;
            }
            $payload .= $line;
        }
        $row = [];
        $first = explode('|', trim($payload))[0];
        foreach (preg_split('/\s+/', $first, -1, PREG_SPLIT_NO_EMPTY) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $row[$key] = self::unescape($value);
        }
        return $row;
    }

    public function close(): void
    {
        if ($this->socket) {
            @fwrite($this->socket, "quit\n");
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
