import os
from flask import Flask, render_template, request, redirect

# Define the path to your public folder
# This ensures Flask looks into 'public/static' for CSS/JS
app = Flask(__name__, 
            static_folder='public/static', 
            static_url_path='/static')

# ... (Keep the COINS and get_crypto_prices logic from the previous snippet) ...

@app.route('/', methods=['GET', 'POST'])
def index():
    # ... (Keep the route logic the same) ...
    return render_template('index.html', coins=COINS, prices=prices, error=error, kes_rate=KES_RATE)

if __name__ == '__main__':
    app.run(debug=True)
