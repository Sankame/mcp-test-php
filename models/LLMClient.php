<?php
// models/LLMClient.php

class LLMClient
{
    private string $model;

    /**
     * コンストラクタ
     * @param string $model モデル名またはパス
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * メッセージをLLMに送信し、応答を取得する
     * @param array $params [
     *   'system' => string,
     *   'messages' => array,
     *   'max_tokens' => int,
     *   'tools' => array,
     * ]
     * @return array ['content' => [ ['type'=>'text','text'=>string], ... ]]
     */
    public function messages(array $params): array
    {
        // プロンプト組み立て
        $system = $params['system'] ?? '';
        $messages = $params['messages'] ?? [];
        $prompt = '';
        if ($system !== '') {
            $prompt .= "SYSTEM: {$system}\n";
        }
        foreach ($messages as $msg) {
            $role = strtoupper($msg['role']);
            $content = $msg['content'];
            if (is_array($content)) {
                $content = json_encode($content, JSON_UNESCAPED_UNICODE);
            }
            $prompt .= "{$role}: {$content}\n";
        }
        $prompt .= "ASSISTANT:";

        // gpt4all CLI を実行
        $max = intval($params['max_tokens'] ?? 200);
        $cmd = 'gpt4all --model ' . escapeshellarg($this->model) .
               ' --prompt ' . escapeshellarg($prompt) .
               ' --n_predict ' . $max;
        $output = shell_exec($cmd);
        $text = trim($output ?? '');

        return [
            'content' => [
                ['type' => 'text', 'text' => $text]
            ]
        ];
    }
} 