<?php
namespace MCP;

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
     * @param array $params
     * @return array ['content' => [[ 'type' => 'text', 'text' => string ]]]
     */
    public function messages(array $params): array
    {
        // 基本的にPythonのtransformers経由で呼び出す想定
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

        // Python 呼び出し：プロンプトを base64 で引数として渡す方式に簡易化
        $b64 = base64_encode($prompt);
        $max = intval($params['max_tokens'] ?? 200);
        $py  = 'import sys, base64; ' .
               'from transformers import pipeline, logging; ' .
               'logging.set_verbosity_error(); ' .
               'prompt = base64.b64decode(sys.argv[1]).decode("utf-8"); ' .
               'print(pipeline("text-generation", model="distilgpt2")(prompt, max_length=' . $max . ', truncation=True)[0]["generated_text"])';
        $cmd = 'python3 -c ' . escapeshellarg($py) . ' ' . escapeshellarg($b64);
        $output = shell_exec($cmd);
        $text   = trim($output ?? '');

        return [
            'content' => [ ['type' => 'text', 'text' => $text] ]
        ];
    }
} 