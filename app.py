from flask import Flask, render_template, jsonify, request
import urllib.request
import json

app = Flask(__name__)

WHATSAPP_NUMBER = "254790675708"
MARKUP = 1.05  # 5% above market price

COINS = [
    {"id": "bitcoin", "symbol": "BTC", "name": "Bitcoin"},
    {"id": "ethereum", "symbol": "ETH", "name": "Ethereum"},
    {"id": "tether", "symbol": "USDT", "name": "Tether"},
    {"id": "binancecoin", "symbol": "BNB", "name": "BNB"},
    {"id": "solana", "symbol": "SOL", "name": "Solana"},
    {"id": "ripple", "symbol": "XRP", "name": "XRP"},
    {"id": "cardano", "symbol": "ADA", "name": "Cardano"},
    {"id": "dogecoin", "symbol": "DOGE", "name": "Dogecoin"},
    {"id": "polkadot", "symbol": "DOT", "name": "Polkadot"},
    {"id": "litecoin", "symbol": "LTC", "name": "Litecoin"},
    {"id": "tron", "symbol": "TRX", "name": "TRON"},
    {"id": "chainlink", "symbol": "LINK", "name": "Chainlink"},
]


@app.route("/")
def index():
    return render_template("index.html", coins=COINS)


@app.route("/api/prices")
def prices():
    coin_ids = ",".join(c["id"] for c in COINS)
    url = f"https://api.coingecko.com/api/v3/simple/price?ids={coin_ids}&vs_currencies=kes"
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        with urllib.request.urlopen(req, timeout=10) as response:
            data = json.loads(response.read())
        result = {}
        for coin in COINS:
            market_price = data.get(coin["id"], {}).get("kes", 0)
            result[coin["id"]] = {
                "market": market_price,
                "our_price": round(market_price * MARKUP, 2),
            }
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route("/order", methods=["POST"])
def order():
    coin_id = request.form.get("coin_id")
    coin_name = request.form.get("coin_name")
    coin_symbol = request.form.get("coin_symbol")
    amount_kes = request.form.get("amount_kes")
    coin_amount = request.form.get("coin_amount")
    price = request.form.get("price")

    message = (
        f"Hello! I want to buy crypto:\n\n"
            f"Coin: {coin_name} ({coin_symbol})\n"
            f"Amount: {coin_amount} {coin_symbol}\n"
            f"Total: KES {amount_kes}\n"
            f"Rate: KES {price} per {coin_symbol}\n\n"
            f"Please confirm and send payment details."
    )

    import urllib.parse
    encoded = urllib.parse.quote(message)
    wa_url = f"https://wa.me/{WHATSAPP_NUMBER}?text={encoded}"
    return jsonify({"redirect": wa_url})


if __name__ == "__main__":
    app.run(debug=True, port=5000)
