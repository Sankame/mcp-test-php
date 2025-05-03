<?php
namespace MCP;

class Client
{
    private $process;
    private array $pipes = [];

    /**
     * コンストラクタ: サーバースクリプトを proc_open で起動する
     * @param string $serverScript Ruby版サーバーに相当する PHPスクリプトへのパス
     * @throws \Exception プロセス起動失敗時
     */
    public function __construct(string $serverScript)
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        $command = 'php ' . escapeshellarg($serverScript);
        $pipes = [];
        $this->process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($this->process)) {
            throw new \Exception('Failed to start MCP server process');
        }
        $this->pipes = $pipes;
    }

    /**
     * JSON-RPC リクエストを送信し、レスポンスを配列として返す
     * @param array $request JSON-RPC リクエスト構造体
     * @return array レスポンスの result 部分
     * @throws \Exception 通信失敗・JSONエラー・サーバーエラー時
     */
    public function sendRequest(array $request): array
    {
        $json = json_encode($request, JSON_UNESCAPED_UNICODE) . "\n";
        fwrite($this->pipes[0], $json);

        $line = fgets($this->pipes[1]);
        if ($line === false) {
            throw new \Exception('No response from MCP server');
        }

        $result = json_decode($line, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response: ' . json_last_error_msg());
        }
        if (isset($result['error'])) {
            $err = $result['error'];
            throw new \Exception("Server error: {$err['message']} ({$err['code']})");
        }

        return $result['result'];
    }

    /**
     * MCP サーバーへの初期化リクエストと通知を実行
     * @return array initialize の結果
     */
    public function initializeConnection(): array
    {
        $response = $this->sendRequest([
            'jsonrpc' => '2.0',
            'method'  => 'initialize',
            'params'  => [
                'protocolVersion' => '2025-03-26',
                'clientInfo'      => ['name' => 'MCP PHP Client', 'version' => '1.0.0']
            ],
            'id'      => self::generateUuid()
        ]);

        // initialized 通知
        fwrite($this->pipes[0], json_encode([
            'jsonrpc' => '2.0',
            'method'  => 'notifications/initialized'
        ], JSON_UNESCAPED_UNICODE) . "\n");

        return $response;
    }

    /**
     * ping メソッド
     * @return string 'pong'
     */
    public function ping(): string
    {
        $response = $this->sendRequest([
            'jsonrpc' => '2.0',
            'method'  => 'ping',
            'id'      => self::generateUuid()
        ]);
        return $response;
    }

    /**
     * ツール一覧取得
     * @return array tools/list の結果
     */
    public function listTools(): array
    {
        $response = $this->sendRequest([
            'jsonrpc' => '2.0',
            'method'  => 'tools/list',
            'params'  => new \stdClass(),
            'id'      => self::generateUuid()
        ]);
        return $response;
    }

    /**
     * ツール呼び出し
     * @param string $name ツール名
     * @param array  $args 引数
     * @return mixed ツールの実行結果
     */
    public function callTool(string $name, array $args = []): mixed
    {
        $response = $this->sendRequest([
            'jsonrpc' => '2.0',
            'method'  => 'tools/call',
            'params'  => ['name' => $name, 'args' => $args],
            'id'      => self::generateUuid()
        ]);

        // JSON-RPC result の中の content を返す
        return $response['content'] ?? $response;
    }

    /**
     * プロセスおよびパイプをクローズ
     */
    public function close(): void
    {
        if (is_resource($this->process)) {
            fclose($this->pipes[0]);
            fclose($this->pipes[1]);
            fclose($this->pipes[2]);
            proc_terminate($this->process);
            proc_close($this->process);
        }
    }

    /**
     * UUID v4 を生成
     * @return string
     */
    private static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        $hex = bin2hex($data);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }
} 