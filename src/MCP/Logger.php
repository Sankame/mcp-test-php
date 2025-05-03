<?php
namespace MCP;

class Logger
{
    private $file;

    /**
     * コンストラクタ: ログファイルを開く
     * @param string $path ログファイルのパス
     */
    public function __construct(string $path)
    {
        $dir = dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->file = fopen($path, 'a');
    }

    /**
     * INFO レベルのメッセージを書き込む
     * @param string $message
     */
    public function info(string $message): void
    {
        fwrite($this->file, "[INFO] {$message}\n");
        fflush($this->file);
    }
} 