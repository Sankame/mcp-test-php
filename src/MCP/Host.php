<?php
namespace MCP;

use MCP\Client;
use MCP\StdIOConnection;
use Dotenv\Dotenv;

require_once __DIR__ . '/../../vendor/autoload.php';

class Host
{
    private Client $client;
    private LLMClient $llm;

    public function __construct(string $serverScript)
    {
        // 環境変数読み込み
        Dotenv::createImmutable(__DIR__ . '/../../')->safeLoad();

        $this->client = new Client($serverScript);
        $this->llm = new LLMClient(getenv('LLM_MODEL') ?: '');
    }

    public function connectToServer(): void
    {
        $this->client->initializeConnection();
    }

    public function chatLoop(): void
    {
        echo "MCP Client Started!\n";
        echo "Type your queries 'exit' to exit." . "\n";

        while (true) {
            $inputRaw = fgets(STDIN);
            if ($inputRaw === false) {
                break;
            }
            // ANSIエスケープシーケンスや制御文字を除去してサニタイズ
            $sanitized = preg_replace('/\x1b\[[0-9;]*[A-Za-z]/', '', $inputRaw);
            $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', '', $sanitized);
            $input = trim($sanitized);
            if (strtolower($input) === 'exit') {
                break;
            }

            $output = $this->processQuery($input);
            echo $output . "\n";
        }
    }

    private function processQuery(string $query): string
    {
        // シンプルなツール前処理: "dice" と数字が含まれる場合は直接サイコロツールを呼び出す
        if (preg_match('/dice.*?(\d+)/i', $query, $m)) {
            $sides = (int)$m[1];
            $result = $this->client->callTool('dice', ['sides' => $sides]);
            return "Dice rolled (1-{$sides}): {$result}";
        }

        $messages = [
            ['role' => 'user', 'content' => $query]
        ];

        // ツール一覧取得
        $response = $this->client->listTools();
        $availableTools = array_map(fn($tool) => [
            'name' => $tool['name'],
            'description' => $tool['description'],
            'input_schema' => $tool['input_schema']
        ], $response['tools']);

        // LLM 呼び出し
        $response = $this->llm->messages([
            'model' => getenv('LLM_MODEL') ?: 'gpt4all',
            'system' => 'Respond only in Japanese.',
            'messages' => $messages,
            'max_tokens' => 1000,
            'tools' => $availableTools
        ]);

        $finalText = [];
        $assistantMessage = [];
        foreach ($response['content'] as $content) {
            if ($content['type'] === 'text') {
                $finalText[] = $content['text'];
                $assistantMessage[] = $content;
            } elseif ($content['type'] === 'tool_use') {
                $toolName = $content['name'];
                $toolArgs = $content['input'];
                $toolResult = $this->client->callTool(name: $toolName, args: $toolArgs);
                $finalText[] = "[Calling tool {$toolName} with args " . json_encode($toolArgs) . "]";

                $assistantMessage[] = $content;
                $messages[] = ['role' => 'assistant', 'content' => $assistantMessage];
                $messages[] = ['role' => 'user', 'content' => [['type' => 'tool_result', 'tool_use_id' => $content['id'], 'content' => $toolResult]]];

                $response = $this->llm->messages([
                    'model' => getenv('LLM_MODEL') ?: 'gpt4all',
                    'messages' => $messages,
                    'max_tokens' => 1000,
                    'tools' => $availableTools
                ]);

                $finalText[] = $response['content'][0]['text'];
            }
        }

        return implode("\n", $finalText);
    }

    public function close(): void
    {
        $this->client->close();
    }

    public function ask(string $query): string
    {
        return $this->processQuery($query);
    }
} 