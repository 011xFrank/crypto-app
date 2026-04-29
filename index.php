<?php
// Fetch live crypto prices from CoinGecko (free demo API)
// KES/USD rate (approximate)
define('KES_RATE', 129.50);
define('MARKUP', 1.05); // 5% above market
define('WHATSAPP_NUMBER', '254790675708');

$coins = [
    'bitcoin'       => ['name' => 'Bitcoin',      'symbol' => 'BTC',  'icon' => 'https://assets.coingecko.com/coins/images/1/standard/bitcoin.png'],
    'ethereum'      => ['name' => 'Ethereum',     'symbol' => 'ETH',  'icon' => 'https://assets.coingecko.com/coins/images/279/standard/ethereum.png'],
    'tether'        => ['name' => 'Tether',       'symbol' => 'USDT', 'icon' => 'https://assets.coingecko.com/coins/images/325/standard/Tether.png'],
    'binancecoin'   => ['name' => 'BNB',          'symbol' => 'BNB',  'icon' => 'https://assets.coingecko.com/coins/images/825/standard/bnb-icon2_2x.png'],
    'solana'        => ['name' => 'Solana',       'symbol' => 'SOL',  'icon' => 'https://assets.coingecko.com/coins/images/4128/standard/solana.png'],
    'ripple'        => ['name' => 'XRP',          'symbol' => 'XRP',  'icon' => 'https://assets.coingecko.com/coins/images/44/standard/xrp-symbol-white-128.png'],
    'usd-coin'      => ['name' => 'USD Coin',     'symbol' => 'USDC', 'icon' => 'https://assets.coingecko.com/coins/images/6319/standard/usdc.png'],
    'dogecoin'      => ['name' => 'Dogecoin',     'symbol' => 'DOGE', 'icon' => 'https://assets.coingecko.com/coins/images/5/standard/dogecoin.png'],
    'cardano'       => ['name' => 'Cardano',      'symbol' => 'ADA',  'icon' => 'https://assets.coingecko.com/coins/images/975/standard/cardano.png'],
    'tron'          => ['name' => 'TRON',         'symbol' => 'TRX',  'icon' => 'https://assets.coingecko.com/coins/images/1094/standard/tron-logo.png'],
    'polkadot'      => ['name' => 'Polkadot',     'symbol' => 'DOT',  'icon' => 'https://assets.coingecko.com/coins/images/12171/standard/polkadot.png'],
    'litecoin'      => ['name' => 'Litecoin',     'symbol' => 'LTC',  'icon' => 'https://assets.coingecko.com/coins/images/2/standard/litecoin.png'],
];

$coinIds = implode(',', array_keys($coins));

// Fetch prices with simple file_get_contents (no API key required for demo)
$apiUrl = "https://api.coingecko.com/api/v3/simple/price?ids={$coinIds}&vs_currencies=usd&include_24hr_change=true";

$prices = [];
$error = null;

$context = stream_context_create([
    'http' => [
        'timeout' => 8,
        'header'  => "User-Agent: CryptoShop/1.0\r\nAccept: application/json\r\n",
    ]
]);

$response = @file_get_contents($apiUrl, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data) {
        foreach ($coins as $id => $info) {
            if (isset($data[$id]['usd'])) {
                $usdPrice = $data[$id]['usd'];
                $change24h = $data[$id]['usd_24h_change'] ?? 0;
                $kesPriceMarket = $usdPrice * KES_RATE;
                $kesPriceOurs   = $kesPriceMarket * MARKUP;
                $prices[$id] = [
                    'usd'        => $usdPrice,
                    'kes_market' => $kesPriceMarket,
                    'kes_ours'   => $kesPriceOurs,
                    'change24h'  => $change24h,
                ];
            }
        }
    }
} else {
    $error = "Could not fetch live prices. Showing estimated rates.";
    // Fallback static prices (USD)
    $fallback = [
        'bitcoin' => 95000, 'ethereum' => 3200, 'tether' => 1.0,
        'binancecoin' => 580, 'solana' => 145, 'ripple' => 0.52,
        'usd-coin' => 1.0, 'dogecoin' => 0.16, 'cardano' => 0.42,
        'tron' => 0.08, 'polkadot' => 6.5, 'litecoin' => 95,
    ];
    foreach ($coins as $id => $info) {
        $usd = $fallback[$id] ?? 1;
        $prices[$id] = [
            'usd'        => $usd,
            'kes_market' => $usd * KES_RATE,
            'kes_ours'   => $usd * KES_RATE * MARKUP,
            'change24h'  => 0,
        ];
    }
}

// Handle buy form submission
$orderDetails = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['coin'], $_POST['amount'])) {
    $coinId  = htmlspecialchars(trim($_POST['coin']));
    $amount  = (float) $_POST['amount'];
    $wallet  = htmlspecialchars(trim($_POST['wallet'] ?? ''));
    $name    = htmlspecialchars(trim($_POST['buyer_name'] ?? ''));

    if (isset($coins[$coinId]) && isset($prices[$coinId]) && $amount > 0) {
        $coinInfo    = $coins[$coinId];
        $pricePerCoin = $prices[$coinId]['kes_ours'];
        $totalKES    = $amount * $pricePerCoin;

        $msg  = "Hello! I want to buy crypto.\n\n";
        $msg .= "Name: {$name}\n";
        $msg .= "Coin: {$coinInfo['name']} ({$coinInfo['symbol']})\n";
        $msg .= "Amount: {$amount} {$coinInfo['symbol']}\n";
        $msg .= "Total: KES " . number_format($totalKES, 2) . "\n";
        if ($wallet) {
            $msg .= "Wallet: {$wallet}\n";
        }
        $msg .= "\nPlease confirm and send payment details.";

        $waUrl = "https://wa.me/" . WHATSAPP_NUMBER . "?text=" . rawurlencode($msg);

        header("Location: " . $waUrl);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoKE &mdash; Buy Crypto in Kenya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="container header__inner">
        <div class="logo">
            <span class="logo__icon">&#9889;</span>
            <span class="logo__text">CryptoKE</span>
        </div>
        <nav class="header__nav">
            <a href="#rates">Live Rates</a>
            <a href="#buy" class="btn btn--sm">Buy Now</a>
        </nav>
    </div>
</header>

<!-- Hero -->
<section class="hero">
    <div class="container hero__inner">
        <p class="hero__badge">Fast &amp; Secure Crypto in Kenya</p>
        <h1 class="hero__title">Buy Crypto Instantly<br><span>Paid via M-Pesa</span></h1>
        <p class="hero__sub">Get any cryptocurrency at our guaranteed rates — delivered to your wallet within minutes. All prices in <strong>Kenyan Shillings (KES)</strong>.</p>
        <div class="hero__ctas">
            <a href="#buy" class="btn btn--primary">Start Buying</a>
            <a href="#how" class="btn btn--outline">How It Works</a>
        </div>
        <div class="hero__stats">
            <div class="stat"><span class="stat__val">12+</span><span class="stat__label">Coins Available</span></div>
            <div class="stat"><span class="stat__val">~5 min</span><span class="stat__label">Avg Delivery</span></div>
            <div class="stat"><span class="stat__val">24/7</span><span class="stat__label">Support</span></div>
        </div>
    </div>
    <div class="hero__bg"></div>
</section>

<!-- Live Rates -->
<section class="section rates" id="rates">
    <div class="container">
        <div class="section__header">
            <h2>Live Rates</h2>
            <p>Prices updated in real-time &middot; 5% service fee included</p>
        </div>
        <?php if ($error): ?>
        <div class="alert"><?= $error ?></div>
        <?php endif; ?>
        <div class="rates__grid">
            <?php foreach ($coins as $id => $info): ?>
            <?php
                $p = $prices[$id] ?? null;
                if (!$p) continue;
                $changeClass = $p['change24h'] >= 0 ? 'up' : 'down';
                $changeSign  = $p['change24h'] >= 0 ? '+' : '';
                $priceDisplay = $p['kes_ours'] >= 1000
                    ? 'KES ' . number_format($p['kes_ours'], 2)
                    : 'KES ' . number_format($p['kes_ours'], 4);
            ?>
            <div class="rate-card" data-coin="<?= $id ?>" onclick="selectCoin('<?= $id ?>')">
                <div class="rate-card__top">
                    <img src="<?= $info['icon'] ?>" alt="<?= $info['symbol'] ?>" class="rate-card__icon" loading="lazy">
                    <div>
                        <div class="rate-card__name"><?= $info['name'] ?></div>
                        <div class="rate-card__symbol"><?= $info['symbol'] ?></div>
                    </div>
                    <div class="rate-card__change <?= $changeClass ?>">
                        <?= $changeSign . number_format($p['change24h'], 2) ?>%
                    </div>
                </div>
                <div class="rate-card__price"><?= $priceDisplay ?></div>
                <div class="rate-card__sub">per 1 <?= $info['symbol'] ?></div>
                <button class="btn btn--buy" onclick="selectCoin('<?= $id ?>'); event.stopPropagation();">Buy <?= $info['symbol'] ?></button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section how" id="how">
    <div class="container">
        <div class="section__header">
            <h2>How It Works</h2>
            <p>Three simple steps to get your crypto</p>
        </div>
        <div class="how__steps">
            <div class="step">
                <div class="step__num">1</div>
                <h3>Choose &amp; Calculate</h3>
                <p>Pick your coin, enter the amount you want to buy, and see the total in KES instantly.</p>
            </div>
            <div class="step__arrow">&#8594;</div>
            <div class="step">
                <div class="step__num">2</div>
                <h3>Confirm on WhatsApp</h3>
                <p>You'll be redirected to WhatsApp with your order pre-filled. We confirm your order and send payment instructions.</p>
            </div>
            <div class="step__arrow">&#8594;</div>
            <div class="step">
                <div class="step__num">3</div>
                <h3>Pay &amp; Receive</h3>
                <p>Send payment via M-Pesa or bank transfer. Your crypto lands in your wallet within minutes.</p>
            </div>
        </div>
    </div>
</section>

<!-- Buy Form -->
<section class="section buy" id="buy">
    <div class="container">
        <div class="section__header">
            <h2>Place Your Order</h2>
            <p>Fill in the form and you'll be taken to WhatsApp to complete the purchase</p>
        </div>
        <div class="buy__wrap">
            <form method="POST" action="#buy" class="buy-form" id="buyForm">
                <div class="form-group">
                    <label for="coin">Select Cryptocurrency</label>
                    <div class="select-wrap">
                        <select name="coin" id="coin" required onchange="updateTotal()">
                            <option value="">-- Choose a coin --</option>
                            <?php foreach ($coins as $id => $info): ?>
                            <?php $p = $prices[$id] ?? null; if (!$p) continue; ?>
                            <option value="<?= $id ?>"
                                data-price="<?= $p['kes_ours'] ?>"
                                data-symbol="<?= $info['symbol'] ?>">
                                <?= $info['name'] ?> (<?= $info['symbol'] ?>) &mdash; KES <?= number_format($p['kes_ours'], 2) ?>/coin
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="amount">Amount to Buy</label>
                    <div class="input-with-tag">
                        <input type="number" name="amount" id="amount" min="0.000001" step="any"
                               placeholder="e.g. 0.01" required oninput="updateTotal()">
                        <span class="input-tag" id="symbolTag">COIN</span>
                    </div>
                </div>

                <div class="form-group total-box" id="totalBox" style="display:none;">
                    <div class="total-box__label">Total Cost</div>
                    <div class="total-box__value" id="totalValue">KES 0.00</div>
                    <div class="total-box__note">Includes 5% service fee</div>
                </div>

                <div class="form-group">
                    <label for="buyer_name">Your Name</label>
                    <input type="text" name="buyer_name" id="buyer_name" placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label for="wallet">Your Wallet Address <span class="optional">(Optional)</span></label>
                    <input type="text" name="wallet" id="wallet" placeholder="Your crypto wallet address">
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    <span class="btn__wa-icon">&#128172;</span>
                    Continue to WhatsApp
                </button>
                <p class="form-note">You will be redirected to WhatsApp to complete the order. No payment is charged here.</p>
            </form>

            <div class="buy__info">
                <div class="info-card">
                    <h4>Why Buy With Us?</h4>
                    <ul>
                        <li>&#10003; Transparent pricing — all fees shown upfront</li>
                        <li>&#10003; Fast delivery — typically under 10 minutes</li>
                        <li>&#10003; M-Pesa &amp; bank transfer accepted</li>
                        <li>&#10003; 24/7 WhatsApp support</li>
                        <li>&#10003; No hidden charges</li>
                    </ul>
                </div>
                <div class="info-card info-card--wa">
                    <div class="wa-box">
                        <div class="wa-box__icon">&#128172;</div>
                        <div>
                            <div class="wa-box__title">WhatsApp Support</div>
                            <div class="wa-box__num">+254 790 675 708</div>
                        </div>
                    </div>
                    <a href="https://wa.me/<?= WHATSAPP_NUMBER ?>?text=Hello!+I+need+help+buying+crypto." target="_blank" class="btn btn--wa btn--full">
                        Chat With Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container footer__inner">
        <div class="logo">
            <span class="logo__icon">&#9889;</span>
            <span class="logo__text">CryptoKE</span>
        </div>
        <p class="footer__note">Prices sourced from CoinGecko &middot; KES rate: <?= KES_RATE ?>/USD &middot; +5% service fee applied</p>
        <p class="footer__disc">Cryptocurrency trading carries risk. Only invest what you can afford to lose.</p>
    </div>
</footer>

<script>
    const coinPrices = <?= json_encode(array_map(fn($p) => $p['kes_ours'], $prices)) ?>;

    function selectCoin(id) {
        const select = document.getElementById('coin');
        select.value = id;
        updateTotal();
        document.getElementById('buy').scrollIntoView({ behavior: 'smooth' });
        document.getElementById('amount').focus();
    }

    function updateTotal() {
        const select = document.getElementById('coin');
        const amountEl = document.getElementById('amount');
        const totalBox = document.getElementById('totalBox');
        const totalVal = document.getElementById('totalValue');
        const symbolTag = document.getElementById('symbolTag');

        const selectedOpt = select.options[select.selectedIndex];
        const price = parseFloat(selectedOpt.dataset.price) || 0;
        const symbol = selectedOpt.dataset.symbol || 'COIN';
        const amount = parseFloat(amountEl.value) || 0;

        symbolTag.textContent = symbol;

        if (price > 0 && amount > 0) {
            const total = price * amount;
            totalVal.textContent = 'KES ' + total.toLocaleString('en-KE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            totalBox.style.display = 'block';
        } else {
            totalBox.style.display = 'none';
        }

        // Highlight selected rate card
        document.querySelectorAll('.rate-card').forEach(c => c.classList.remove('active'));
        const card = document.querySelector(`.rate-card[data-coin="${select.value}"]`);
        if (card) card.classList.add('active');
    }
</script>
</body>
</html>
