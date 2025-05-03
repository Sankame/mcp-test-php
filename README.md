# PHP版MCP (Model Context Protocol)

学習用にシンプル実装された PHP 版 Model Context Protocol (MCP) クライアント/サーバー実装です。

## ディレクトリ構成

```
.
├── host.php             # クライアントエントリポイント
├── client.php           # HTTPクライアント
├── src/                 # コアクラス群（PSR-4 autoload）
│   └── MCP/
│       ├── Host.php
│       ├── Client.php
│       ├── Server.php
│       ├── StdIOConnection.php
│       ├── Logger.php
│       └── LLMClient.php
├── tools/
│   └── dice-server.php  # Dice ツールサーバー
├── composer.json        # Composer 設定
├── composer.lock        # Composer ロックファイル
├── vendor/              # Composer 依存
└── README.md            # 本ファイル
```

## 前提条件

- PHP 8.0 以上がインストールされていること
- Python 3.8 以上および pip がインストールされていること

## セットアップ手順

1. PHP 依存パッケージをインストール
   ```bash
   composer install
   ```
   ※ `vendor/` ディレクトリと `autoload.php` が生成されることを確認してください。
2. Python パッケージをインストール
   ```bash
   pip3 install --upgrade pip
   pip3 install transformers torch
   ```

## ツールサーバーの起動

RPC ベースのツールサーバーを立ち上げます。

```bash
php tools/dice-server.php
```

## ホストの起動

ツールサーバーとは別ターミナルでホストを起動します。
ホスト内ではクライアントからツールサーバーに情報を渡します。

```bash
php host.php
```

## 実行例

```bash
php host.php
Type your queries 'exit' to exit.
> dice 6
Dice rolled (1-6): 4
> exit
```

## 補足

- モデル名を変更する場合は `models/LLMClient.php` 内の設定を調整してください。
- ツールを追加する場合は `tools/` 以下にクラスを実装し、`src/Server.php` で登録してください。
