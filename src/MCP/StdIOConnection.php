<?php
namespace MCP;

class StdIOConnection
{
    public function __construct()
    {
        // PHPでは標準出力の自動フラッシュ設定不要
    }

    /**
     * 標準入力から次のJSON-RPCメッセージを読み取る
     * @return string|null メッセージ文字列または null
     */
    public function readNextMessage(): ?string
    {
        $line = fgets(STDIN);
        if ($line === false) {
            return null;
        }
        return rtrim($line, "\r\n");
    }

    /**
     * 標準出力へJSON-RPCレスポンスを送信する
     * @param string $message JSON文字列
     */
    public function sendMessage(string $message): void
    {
        fwrite(STDOUT, $message . "\n");
    }
} 