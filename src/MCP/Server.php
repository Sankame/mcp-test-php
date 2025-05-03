<?php
namespace MCP;

class Server
{
    private StdIOConnection $connection;
    private Logger $logger;
    private bool $initialized = false;
    private array $tools = [];

    /**
     * コンストラクタ: STDIO接続とロガー初期化
     */
    public function __construct()
    {
        $this->connection = new StdIOConnection();
        $logPath = __DIR__ . '/../../tmp/mcp.log';
        $this->logger = new Logger($logPath);
    }

    /**
     * ツールを登録する
     * @param string   $name
     * @param string   $description
     * @param array    $inputSchema
     * @param callable $handler
     */
    public function registerTool(string $name, string $description, array $inputSchema, callable $handler): void
    {
        $this->tools[$name] = [
            'name'         => $name,
            'description'  => $description,
            'input_schema' => $inputSchema,
            'handler'      => $handler,
        ];
    }

    /**
     * メインループを開始する
     */
    public function run(): void
    {
        while (true) {
            $message = $this->connection->readNextMessage();
            if ($message === null) {
                // 接続クローズ
                break;
            }

            $response = $this->processMessage($message);
            if ($response !== null) {
                $this->connection->sendMessage(json_encode($response, JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * 処理可能メソッド一覧
     * @return array
     */
    private function allowedMethods(): array
    {
        return ['initialize', 'notifications/initialized', 'ping'];
    }

    /**
     * メッセージを処理し、レスポンスを返す
     * @param string $message JSON-RPC リクエスト文字列
     * @return array|null JSON-RPC レスポンス or null
     */
    private function processMessage(string $message): ?array
    {
        $request = json_decode($message, true);
        if ($request === null) {
            return null;
        }
        $method = $request['method'] ?? '';
        $id     = $request['id'] ?? null;

        if (!$this->initialized && !in_array($method, $this->allowedMethods(), true)) {
            throw new \Exception("Method {$method} is not allowed");
        }

        switch ($method) {
            case 'initialize':
                $this->logger->info('RPC: initialize');
                return [
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'result'  => [
                        'protocolVersion' => '2025-03-26',
                        'serverInfo'      => ['name' => 'MCP PHP Server', 'version' => '1.0.0'],
                        'instructions'    => 'Optional instructions for the client',
                    ],
                ];

            case 'notifications/initialized':
                $this->logger->info('RPC: notifications/initialized');
                $this->initialized = true;
                return null;

            case 'ping':
                $this->logger->info('RPC: ping');
                return ['jsonrpc' => '2.0', 'id' => $id, 'result' => 'pong'];

            case 'tools/list':
                $this->logger->info('RPC: tools/list');
                $toolsList = array_map(fn($t) => [
                    'name'         => $t['name'],
                    'description'  => $t['description'],
                    'input_schema' => $t['input_schema'],
                ], $this->tools);
                return [
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'result'  => ['tools' => $toolsList],
                ];

            case 'tools/call':
                $this->logger->info('RPC: tools/call');
                $toolName = $request['params']['name'] ?? '';
                $toolArgs = $request['params']['args'] ?? [];
                if (!isset($this->tools[$toolName])) {
                    throw new \Exception("Tool {$toolName} not found");
                }
                $handler = $this->tools[$toolName]['handler'];
                $result  = call_user_func($handler, $toolArgs);
                return [
                    'jsonrpc' => '2.0',
                    'id'      => $id,
                    'result'  => ['content' => $result],
                ];

            default:
                return null;
        }
    }
} 