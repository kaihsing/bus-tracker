公車動態查詢系統 Bus Tracker
一個基於交通部 TDX 平台的 即時公車動態查詢系統，提供路線到站時間查詢、車輛進度條視覺化，以及收藏常用站牌功能。

A simple real‑time bus tracker powered by Taiwan MOTC TDX, with route ETA query, vehicle progress bar, and favorite stops.

前端為單一頁面 Bus.html，後端為輕量 api.php，適合部署在 NAS 或共享虛擬主機環境。

功能 Features
路線查詢：輸入縣市與路線編號，查詢全線各站即時到站資訊（去程 / 回程）。

車輛進度視覺化：將 5 分鐘內即將到站的車輛，以進度條顯示在起點與終點之間的位置。

收藏站牌：可將常用站牌（可選填路線）加入收藏，集中查看到站資訊。

自動刷新：在對應頁籤開啟時，每 30 秒自動刷新路線與收藏站牌資料。

本機收藏：使用瀏覽器 localStorage 儲存收藏站牌，不需資料庫。

Route search: Query ETA for all stops of a route (both directions) by city and route name.

Vehicle progress view: Visualize buses arriving within 5 minutes on a progress bar between origin and destination.

Favorite stops: Save frequently used stops (optionally with route) and view ETAs in one place.

Auto refresh: Automatically refresh route and favorites every 30 seconds while the tab is active.

Local favorites: Favorites are stored in browser localStorage, no server‑side DB required.

目前支援城市 Currently supported cities：

台北市 Taipei (Taipei)

新北市 New Taipei (NewTaipei)

基隆市 Keelung (Keelung)

系統架構 Architecture
面向 Aspect	說明 Description
前端 Frontend	單一 Bus.html，純 HTML + CSS + 原生 JavaScript。
後端 Backend	api.php，負責向 TDX 取得 OAuth token 並做檔案快取。
資料來源 Data	MOTC TDX Bus APIs：StopOfRoute、EstimatedTimeOfArrival、RealTimeNearStop。
快取 Caching	前端記憶體快取路線站點 / 終點站；後端檔案快取 access token。
使用者資料 User	收藏站牌存於瀏覽器 localStorage，無後端使用者資料。
自動更新 Auto‑refresh	每 30 秒重查路線與收藏站牌（在對應頁籤開啟時）。
前置需求 Prerequisites
PHP 7+（或相容版本，能執行 api.php）。

可以對外提供 HTTPS / HTTP 的 NAS 或共享虛擬主機。

已註冊 TDX（運輸資料流通服務平臺）帳號並建立 Application，取得 Client ID / Client Secret。

You need:

PHP 7+ runtime (to run api.php).

A NAS or shared hosting that serves static HTML and PHP over HTTP/HTTPS.

A TDX developer account and an application with Client ID / Client Secret.

環境變數設定 Environment variables
api.php 會從環境變數讀取 TDX 的憑證，若未設定會回傳錯誤；請在 NAS / 虛擬主機上設定下列環境變數。

建議的環境變數名稱（請依實際程式中使用的名稱調整）：

bash
# 例：在 Linux / NAS Shell、或虛擬主機的環境設定中
export TDX_CLIENT_ID="your_client_id_here"
export TDX_CLIENT_SECRET="your_client_secret_here"
For shared hosting, you can set it in:

控制台的「環境變數 / Environment Variables」介面，或

.htaccess / vhost 設定（視主機商而定），例如：

text
# .htaccess 範例（視主機支援情況）
SetEnv TDX_CLIENT_ID your_client_id_here
SetEnv TDX_CLIENT_SECRET your_client_secret_here
api.php 會：

讀取 TDX_CLIENT_ID、TDX_CLIENT_SECRET，若缺少則回傳 { success: false, error: 'Missing TDX credentials in environment' }。

若本地快取檔 .tdx_token_cache.json 有未過期的 token，直接回傳。

否則呼叫 https://tdx.transportdata.tw/auth/realms/TDXConnect/protocol/openid-connect/token 取得新 token 並寫入快取檔。

部署教學：Synology / QNAP NAS 部署
以下以 Synology 為例，其他 NAS 大同小異。

1. 準備 Web 根目錄
啟用 Web Station / Web 服務。

在 Web 根目錄（例如 /volume1/web）下建立資料夾，例如 bus-tracker。

將專案檔案放入：

Bus.html

api.php

其他 .gitignore、.gitattributes 可選擇性保留。

2. 設定 PHP 與權限
確認 Web Station 已啟用 PHP，且該虛擬主機使用支援 curl/openssl 的 PHP profile。

確認 bus-tracker 資料夾及 .tdx_token_cache.json（會由 api.php 自動建立）具有 Web 伺服器帳號的讀寫權限，以便寫入 token 快取。

3. 設定環境變數
在 Synology：

可透過 Web Station 的「PHP 設定」加入自訂 env，或在反向代理 / vhost 設定中加入 SetEnv。

或在 NAS 的 shell 設定中匯入，但需確保 Web 服務有繼承到。

範例（若使用 Apache / httpd）：

text
SetEnv TDX_CLIENT_ID your_client_id_here
SetEnv TDX_CLIENT_SECRET your_client_secret_here
4. 修改前端 API_URL
在 Bus.html 裡面有一行註記「⚠️ 修改這裡：填入你的 NAS 網址」：

js
// ⚠️ 修改這裡：填入你的 NAS 網址
const API_URL = 'https://你的網域或IP/bus-tracker/api.php?action=token';
請改成你的實際網址，例如：

js
const API_URL = 'https://hobbes.diskstation.me/Transit/api.php?action=token';
注意：如果你將 api.php 放在其他路徑（例如 /Transit/api.php），請同步調整此 URL。

5. 測試
用瀏覽器開啟：https://你的網域/bus-tracker/Bus.html。

選擇縣市、輸入路線編號（例如：307），按「查詢」。

若能看到站牌列表與進度條，即部署成功。

開啟「收藏站牌」頁籤，新增站牌並確認可以顯示即時 ETA。

部署教學：共享虛擬主機 Shared hosting
將專案上傳到主機提供的 Web 根目錄（例如 public_html/bus-tracker）：

Bus.html

api.php。

透過主機控制台設定 PHP 版本（7+）並開啟 curl。

在控制台的「Environment Variables」或 .htaccess 裡設定：

text
SetEnv TDX_CLIENT_ID your_client_id_here
SetEnv TDX_CLIENT_SECRET your_client_secret_here
修改 Bus.html 中的 API_URL，指向你的主機，例如：

js
const API_URL = 'https://yourdomain.com/bus-tracker/api.php?action=token';
用瀏覽器開啟 https://yourdomain.com/bus-tracker/Bus.html 測試功能。

使用方式 Usage
開啟 Bus.html。

路線查詢：

選擇城市（台北 / 新北 / 基隆）。

輸入路線編號（例如：307、棕1）。

按「查詢」，待結果載入。

收藏站牌：

切換到「收藏站牌」頁籤。

選擇城市，輸入站牌名稱（例：台北車站），可選填路線編號。

按「新增」，之後系統會自動顯示該站牌的最新到站資訊。

Open Bus.html in your browser.

Route search: select city, enter route name, click Search.

Favorites: switch to Favorites tab, fill city + stop name (+ route optional), then click Add to save.

注意事項 Notes
本專案使用 TDX 免費 API，請遵守 TDX 使用條款與流量限制。

若出現「請求過於頻繁」錯誤，代表 TDX 回傳 HTTP 429，請稍後再試。

收藏資料存放於瀏覽器本機，換裝置或清除瀏覽資料後會消失。

api.php 使用檔案快取 token，若權限不足可能導致無法寫入 .tdx_token_cache.json 而取得 token 失敗。